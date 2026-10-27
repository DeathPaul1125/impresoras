<?php

namespace App\Support;

use App\Models\Configuracion;
use App\Models\Lectura;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de consumo reutilizadas por los dos dashboards y las exportaciones.
 * Todas parten del scope Lectura::filtrar($filtros) para respetar los mismos filtros.
 *
 * Regla de costo: (impresiones + copias) * precio_unitario. Los escaneos no cuestan.
 */
class Consumo
{
    /** Columnas por las que se puede ordenar las tablas de usuarios/unidades/dispositivos. */
    public const ORDENABLES = ['impresiones', 'copias', 'escaneados', 'costo_total'];

    public function __construct(
        private array $filtros = [],
        private ?float $precio = null,
    ) {
        $this->precio ??= Configuracion::precioUnitario();
    }

    public function precio(): float
    {
        return $this->precio;
    }

    private function base()
    {
        return Lectura::query()->filtrar($this->filtros);
    }

    /** SQL del costo agregado según el precio vigente. */
    private function costoSql(): string
    {
        return 'SUM((lecturas.impresiones + lecturas.copias) * '.$this->precio.')';
    }

    /** Tarjetas KPI: impresiones, copias, escaneos, costo, usuarios, promedio. */
    public function totales(): array
    {
        $r = $this->base()
            ->selectRaw('COALESCE(SUM(impresiones),0) impresiones')
            ->selectRaw('COALESCE(SUM(copias),0) copias')
            ->selectRaw('COALESCE(SUM(escaneados),0) escaneados')
            ->selectRaw('COUNT(DISTINCT cuenta_id) usuarios')
            ->first();

        $impresiones = (int) $r->impresiones;
        $copias = (int) $r->copias;
        $usuarios = (int) $r->usuarios;

        return [
            'impresiones' => $impresiones,
            'copias' => $copias,
            'escaneados' => (int) $r->escaneados,
            'usuarios' => $usuarios,
            'precio_unitario' => $this->precio,
            'costo_total' => ($impresiones + $copias) * $this->precio,
            'promedio_usuario' => $usuarios ? (int) round(($impresiones + $copias) / $usuarios) : 0,
        ];
    }

    public function porUsuario(string $orden = 'costo_total', string $dir = 'DESC', ?int $limite = null): Collection
    {
        $orden = in_array($orden, self::ORDENABLES, true) ? $orden : 'costo_total';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $q = $this->base()
            ->join('cuentas', 'cuentas.id', '=', 'lecturas.cuenta_id')
            ->leftJoin('unidades', 'unidades.id', '=', 'cuentas.unidad_id')
            ->groupBy('cuentas.id', 'cuentas.id_cuenta', 'cuentas.nombre_cuenta', 'unidades.codigo', 'unidades.nombre')
            ->selectRaw('cuentas.id_cuenta, cuentas.nombre_cuenta, unidades.codigo AS unidad, unidades.nombre AS unidad_nombre')
            ->selectRaw('SUM(lecturas.impresiones) impresiones, SUM(lecturas.copias) copias, SUM(lecturas.escaneados) escaneados')
            ->selectRaw($this->costoSql().' costo_total')
            ->selectRaw('MAX(lecturas.fecha_corte) ultimo_corte')
            ->orderBy($orden, $dir);

        return $limite ? $q->limit($limite)->get() : $q->get();
    }

    public function porUnidad(string $orden = 'costo_total', string $dir = 'DESC'): Collection
    {
        $orden = in_array($orden, self::ORDENABLES, true) ? $orden : 'costo_total';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $rows = $this->base()
            ->join('cuentas', 'cuentas.id', '=', 'lecturas.cuenta_id')
            ->leftJoin('unidades', 'unidades.id', '=', 'cuentas.unidad_id')
            ->groupBy('unidades.codigo', 'unidades.nombre')
            ->selectRaw('unidades.codigo AS unidad, unidades.nombre AS unidad_nombre')
            ->selectRaw('SUM(lecturas.impresiones) impresiones, SUM(lecturas.copias) copias, SUM(lecturas.escaneados) escaneados')
            ->selectRaw($this->costoSql().' costo_total')
            ->orderBy($orden, $dir)
            ->get();

        $totalCosto = (float) $rows->sum('costo_total') ?: 1;
        $rows->each(fn ($r) => $r->porcentaje = round($r->costo_total / $totalCosto * 100, 1));

        return $rows;
    }

    public function porDispositivo(string $orden = 'costo_total', string $dir = 'DESC'): Collection
    {
        $orden = in_array($orden, self::ORDENABLES, true) ? $orden : 'costo_total';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        return $this->base()
            ->join('dispositivos', 'dispositivos.id', '=', 'lecturas.dispositivo_id')
            ->leftJoin('modelos', 'modelos.id', '=', 'dispositivos.modelo_id')
            ->groupBy('dispositivos.id', 'dispositivos.nombre_host', 'dispositivos.direccion_ip', 'modelos.nombre')
            ->selectRaw('dispositivos.nombre_host, dispositivos.direccion_ip, modelos.nombre AS modelo')
            ->selectRaw('SUM(lecturas.impresiones) impresiones, SUM(lecturas.copias) copias, SUM(lecturas.escaneados) escaneados')
            ->selectRaw($this->costoSql().' costo_total')
            ->orderBy($orden, $dir)
            ->get();
    }

    /** Tendencia mensual con variación % respecto al mes anterior. */
    public function tendenciaMensual(): Collection
    {
        // SUBSTR sobre el DATETIME da 'YYYY-MM' y funciona igual en MySQL y SQLite.
        $rows = $this->base()
            ->join('cuentas', 'cuentas.id', '=', 'lecturas.cuenta_id')
            ->selectRaw('SUBSTR(fecha_corte, 1, 7) AS periodo')
            ->selectRaw('COUNT(DISTINCT lecturas.cuenta_id) usuarios')
            ->selectRaw('SUM(lecturas.impresiones + lecturas.copias) volumen')
            ->selectRaw($this->costoSql().' costo_total')
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $prev = null;
        foreach ($rows as $r) {
            $r->promedio_usuario = $r->usuarios ? (int) round($r->volumen / $r->usuarios) : 0;
            $r->variacion = ($prev && $prev > 0)
                ? round(($r->costo_total - $prev) / $prev * 100, 1)
                : null;
            $prev = (float) $r->costo_total;
        }

        return $rows;
    }
}
