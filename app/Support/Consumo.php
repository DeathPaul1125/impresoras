<?php

namespace App\Support;

use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consumo a partir de las lecturas SNMP guardadas (contadores_equipo, diario 6 pm).
 * El consumo de un día = lectura de ese día − lectura anterior de la misma impresora.
 * Reutilizado por los dashboards, las exportaciones y los reportes por correo.
 *
 * Regla de costo: (impresiones + copias) * precio_unitario. Los escaneos no cuestan.
 * Filtros: fecha_inicio, fecha_fin (Y-m-d), dispositivo (id), modelo (id).
 */
class Consumo
{
    public const CAMPOS = ['impresiones', 'copias', 'escaneos', 'total'];

    /** Columnas por las que se puede ordenar la tabla de impresoras. */
    public const ORDENABLES = ['ubicacion', 'impresiones', 'copias', 'escaneos', 'total', 'costo_total'];

    private ?Collection $diario = null;

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

    /** Impresoras incluidas según los filtros, ordenadas por ubicación. */
    public function dispositivos(): Collection
    {
        return Dispositivo::with('modelo')
            ->when($this->filtros['dispositivo'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->when($this->filtros['modelo'] ?? null, fn ($q, $id) => $q->where('modelo_id', $id))
            ->orderBy('ubicacion')
            ->get()
            ->keyBy('id');
    }

    /**
     * Consumo por día e impresora dentro del rango: filas {fecha, dispositivo_id, impresiones, copias, escaneos, total}.
     * Si un día falló la lectura, la siguiente diferencia cubre los días perdidos y se asigna a su fecha.
     */
    public function diario(): Collection
    {
        if ($this->diario) {
            return $this->diario;
        }

        $inicio = $this->filtros['fecha_inicio'] ?? null ?: '0000-01-01';
        $fin = $this->filtros['fecha_fin'] ?? null ?: '9999-12-31';

        // ponytail: carga todo el histórico ≤ fin (~2.5k filas/año con 7 impresoras); acotar por fecha si crece mucho.
        $lecturas = ContadorEquipo::whereIn('dispositivo_id', $this->dispositivos()->keys())
            ->when(! empty($this->filtros['fecha_fin']), fn ($q) => $q->where('leido_en', '<', Carbon::parse($fin)->addDay()->toDateString()))
            ->orderBy('dispositivo_id')->orderBy('leido_en')
            ->get();

        $filas = [];
        foreach ($lecturas->groupBy('dispositivo_id') as $id => $serie) {
            $previo = [];   // último valor conocido por campo (una lectura puede venir con algún OID vacío)
            foreach ($serie as $l) {
                $fecha = $l->leido_en->toDateString();
                foreach (self::CAMPOS as $c) {
                    if ($l->$c === null) {
                        continue;
                    }
                    if (isset($previo[$c]) && $fecha >= $inicio && $fecha <= $fin) {
                        $filas[$fecha][$id] ??= ['fecha' => $fecha, 'dispositivo_id' => $id] + array_fill_keys(self::CAMPOS, 0);
                        // ponytail: max(0) por si el contador se reinicia (cambio de equipo); ese día sale en 0.
                        $filas[$fecha][$id][$c] += max(0, $l->$c - $previo[$c]);
                    }
                    $previo[$c] = $l->$c;
                }
            }
        }
        ksort($filas);

        return $this->diario = collect($filas)->flatten(1)->map(fn ($f) => $this->conCosto((object) $f));
    }

    private function conCosto(object $f): object
    {
        $f->costo_total = ($f->impresiones + $f->copias) * $this->precio;

        return $f;
    }

    private function sumar(Collection $filas, array $base = []): object
    {
        $r = (object) $base;
        foreach (self::CAMPOS as $c) {
            $r->$c = (int) $filas->sum($c);
        }

        return $this->conCosto($r);
    }

    /** Tarjetas KPI. */
    public function totales(): array
    {
        $diario = $this->diario();
        $t = $this->sumar($diario);
        $dias = $diario->pluck('fecha')->unique()->count();

        return [
            'impresiones' => $t->impresiones,
            'copias' => $t->copias,
            'escaneos' => $t->escaneos,
            'total' => $t->total,
            'precio_unitario' => $this->precio,
            'costo_total' => $t->costo_total,
            'dias' => $dias,
            'promedio_diario' => $dias ? $t->costo_total / $dias : 0,
        ];
    }

    public function porDispositivo(string $orden = 'costo_total', string $dir = 'DESC'): Collection
    {
        $orden = in_array($orden, self::ORDENABLES, true) ? $orden : 'costo_total';
        $porId = $this->diario()->groupBy('dispositivo_id');

        $rows = $this->dispositivos()->map(fn (Dispositivo $d) => $this->sumar($porId->get($d->id, collect()), [
            'id' => $d->id,
            'ubicacion' => $d->ubicacion,
            'nivel' => $d->nivel,
            'nombre_host' => $d->nombre_host,
            'direccion_ip' => $d->direccion_ip,
            'modelo' => $d->modelo?->nombre,
        ]))->values();

        $totalCosto = (float) $rows->sum('costo_total') ?: 1;
        $rows->each(fn ($r) => $r->porcentaje = round($r->costo_total / $totalCosto * 100, 1));

        return $rows->sortBy($orden, SORT_REGULAR, strtoupper($dir) !== 'ASC')->values();
    }

    /** Total de todas las impresoras por día. */
    public function porDia(): Collection
    {
        return $this->diario()->groupBy('fecha')
            ->map(fn ($filas, $fecha) => $this->sumar($filas, ['fecha' => $fecha]))
            ->values();
    }

    /** Matriz día × impresora: [fecha => [dispositivo_id => fila]]. */
    public function porDiaDispositivo(): Collection
    {
        return $this->diario()->groupBy('fecha')->map(fn ($filas) => $filas->keyBy('dispositivo_id'));
    }

    /** Tendencia mensual con variación % del costo respecto al mes anterior. */
    public function tendenciaMensual(): Collection
    {
        $prev = null;

        return $this->diario()->groupBy(fn ($f) => substr($f->fecha, 0, 7))
            ->map(function ($filas, $periodo) use (&$prev) {
                $r = $this->sumar($filas, ['periodo' => $periodo]);
                $r->dias = $filas->pluck('fecha')->unique()->count();
                $r->variacion = $prev ? round(($r->costo_total - $prev) / $prev * 100, 1) : null;
                $prev = (float) $r->costo_total;

                return $r;
            })->values();
    }
}
