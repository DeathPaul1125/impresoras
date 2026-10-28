<?php

namespace App\Support;

use App\Models\Configuracion;
use App\Models\ContadorCuenta;
use App\Models\Cuenta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consumo por cuenta (clave) a partir de las lecturas del Command Center (contadores_cuenta).
 * Consumo = suma de diferencias entre lecturas consecutivas de cada cuenta en cada equipo dentro del rango.
 * Si un contador baja, la cuenta se reinició en la impresora: se suma el valor nuevo (lo hecho desde el reinicio).
 * Filtros: fecha_inicio, fecha_fin (Y-m-d), dispositivo (id), unidad (código).
 * Para un corte exacto: desde/hasta (fecha y hora) — cuenta lo leído después de "desde" y hasta "hasta" inclusive.
 */
class ConsumoCuentas
{
    private const CAMPOS = ['impresiones', 'copias', 'escaneos'];

    private ?Collection $filas = null;

    public function __construct(private array $filtros = [], private ?float $precio = null)
    {
        $this->precio ??= Configuracion::precioUnitario();
    }

    /** Filas {cuenta_id, dispositivo_id, impresiones, copias, escaneos} con el consumo del rango. */
    private function filas(): Collection
    {
        if ($this->filas) {
            return $this->filas;
        }

        $inicio = $this->filtros['fecha_inicio'] ?? null ?: '0000-01-01';
        $fin = $this->filtros['fecha_fin'] ?? null ?: '9999-12-31';
        $desde = isset($this->filtros['desde']) ? Carbon::parse($this->filtros['desde']) : null;
        $hasta = isset($this->filtros['hasta']) ? Carbon::parse($this->filtros['hasta']) : null;
        $dentro = fn (Carbon $t) => $desde || $hasta
            ? (! $desde || $t->gt($desde)) && (! $hasta || $t->lte($hasta))
            : $t->toDateString() >= $inicio && $t->toDateString() <= $fin;

        $lecturas = ContadorCuenta::query()
            ->when($this->filtros['dispositivo'] ?? null, fn ($q, $id) => $q->where('dispositivo_id', $id))
            ->when($this->filtros['unidad'] ?? null, fn ($q, $u) => $q->whereHas('cuenta.unidad', fn ($w) => $w->where('codigo', $u)))
            ->when($hasta, fn ($q) => $q->where('leido_en', '<=', $hasta))
            ->when(! $hasta && ! empty($this->filtros['fecha_fin']), fn ($q) => $q->where('leido_en', '<', Carbon::parse($fin)->addDay()->toDateString()))
            ->orderBy('leido_en')->get()
            ->groupBy(fn ($l) => $l->cuenta_id.'-'.$l->dispositivo_id);

        $filas = [];
        foreach ($lecturas as $serie) {
            $previa = null;
            foreach ($serie as $l) {
                if ($previa && $dentro($l->leido_en)) {
                    $k = $l->cuenta_id.'-'.$l->dispositivo_id;
                    $filas[$k] ??= ['cuenta_id' => $l->cuenta_id, 'dispositivo_id' => $l->dispositivo_id] + array_fill_keys(self::CAMPOS, 0);
                    foreach (self::CAMPOS as $c) {
                        $filas[$k][$c] += $l->$c >= $previa->$c ? $l->$c - $previa->$c : $l->$c;   // reinicio: cuenta desde cero
                    }
                }
                $previa = $l;
            }
        }

        return $this->filas = collect($filas)->values();
    }

    private function sumar(Collection $filas, array $base): object
    {
        $r = (object) $base;
        foreach (self::CAMPOS as $c) {
            $r->$c = (int) $filas->sum($c);
        }
        $r->costo_total = ($r->impresiones + $r->copias) * $this->precio;

        return $r;
    }

    /** Por cuenta, ordenado; solo cuentas con algún consumo. */
    public function porUsuario(string $orden = 'costo_total', string $dir = 'DESC'): Collection
    {
        $cuentas = Cuenta::with('unidad')->whereIn('id', $this->filas()->pluck('cuenta_id')->unique())->get()->keyBy('id');

        $rows = $this->filas()->groupBy('cuenta_id')->map(fn ($f, $id) => $this->sumar($f, [
            'id_cuenta' => $cuentas[$id]->id_cuenta ?? '',
            'nombre_cuenta' => $cuentas[$id]->nombre_cuenta ?? '',
            'unidad' => $cuentas[$id]->unidad?->codigo ?? '',
            'unidad_nombre' => $cuentas[$id]->unidad?->nombre ?? '',
        ]))->filter(fn ($r) => $r->impresiones + $r->copias + $r->escaneos > 0)->values();

        $orden = in_array($orden, ['nombre_cuenta', 'unidad', 'impresiones', 'copias', 'escaneos', 'costo_total'], true) ? $orden : 'costo_total';

        return $rows->sortBy($orden, SORT_NATURAL, strtoupper($dir) !== 'ASC')->values();
    }

    /** Por unidad, con % del costo. */
    public function porUnidad(): Collection
    {
        $rows = $this->porUsuario()->groupBy('unidad')->map(fn ($u, $codigo) => $this->sumar(collect($u), [
            'unidad' => $codigo ?: 'S/U',
            'unidad_nombre' => $u->first()->unidad_nombre ?: 'Sin unidad',
            'usuarios' => $u->count(),
        ]))->sortByDesc('costo_total')->values();

        $total = (float) $rows->sum('costo_total') ?: 1;
        $rows->each(fn ($r) => $r->porcentaje = round($r->costo_total / $total * 100, 1));

        return $rows;
    }

    /** [dispositivo_id => promedio de páginas B/N por usuario activo] */
    public function promedioPorUsuario(): array
    {
        return $this->filas()->groupBy('dispositivo_id')->map(function ($f) {
            $activos = $f->filter(fn ($r) => $r['impresiones'] + $r['copias'] > 0);

            return $activos->count() ? (int) round($activos->sum(fn ($r) => $r['impresiones'] + $r['copias']) / $activos->count()) : 0;
        })->all();
    }

    public function hayDatos(): bool
    {
        return $this->filas()->isNotEmpty();
    }
}
