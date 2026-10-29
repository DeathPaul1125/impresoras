<?php

namespace App\Support;

use App\Models\ConsumoMensual;
use App\Models\Dispositivo;
use Illuminate\Support\Collection;

/**
 * Historia mensual por impresora y usuario (tabla consumo_mensual, traída del sistema anterior).
 * B/N = impresiones + copias (lo que se factura); los escaneos no cuestan.
 * La comparación entre años usa solo los meses completos que ya tiene el año actual.
 * ponytail: se agrupa en PHP (~2.5 mil filas por año); pasar a SQL si crece a cientos de miles.
 */
class Historia
{
    private Collection $filas;

    public function __construct(public ?int $dispositivo = null)
    {
        $this->filas = ConsumoMensual::with('cuenta.unidad')
            ->when($dispositivo, fn ($q, $d) => $q->where('dispositivo_id', $d))
            ->get()
            ->each(function ($f) {
                $f->mes = $f->periodo->format('Y-m');
                $f->anio = (int) $f->periodo->format('Y');
                $f->bn = $f->impresiones + $f->copias;
            });
    }

    public function hayDatos(): bool
    {
        return $this->filas->isNotEmpty();
    }

    /** [año anterior, año actual] según el último mes con datos. */
    public function anios(): array
    {
        $ultimo = (int) ($this->filas->max('anio') ?: date('Y'));

        return [$ultimo - 1, $ultimo];
    }

    /** Meses comparables: hasta el último mes completo del año actual. */
    public function mesesComparables(): int
    {
        [, $actual] = $this->anios();
        $ultimo = (int) $this->filas->where('anio', $actual)->max(fn ($f) => (int) $f->periodo->format('n'));

        return $actual === (int) date('Y') ? max(1, min($ultimo, (int) date('n') - 1)) : max(1, $ultimo);
    }

    private function comparables(int $anio): Collection
    {
        $n = $this->mesesComparables();

        return $this->filas->filter(fn ($f) => $f->anio === $anio && (int) $f->periodo->format('n') <= $n);
    }

    private static function sumar(Collection $f, array $extra = []): object
    {
        return (object) ($extra + ['impresiones' => $f->sum('impresiones'), 'copias' => $f->sum('copias'), 'escaneos' => $f->sum('escaneos'),
            'bn' => $f->sum('bn'), 'usuarios' => $f->pluck('cuenta_id')->unique()->count()]);
    }

    /** Por mes (Y-m => totales y usuarios activos). */
    public function meses(): Collection
    {
        return $this->filas->groupBy('mes')->sortKeys()->map(fn ($f, $mes) => self::sumar($f, ['periodo' => $mes]));
    }

    public function comparativo(): array
    {
        [$a, $b] = $this->anios();

        return ['meses' => $this->mesesComparables(), 'anterior' => self::sumar($this->comparables($a)), 'actual' => self::sumar($this->comparables($b))];
    }

    /** Por impresora: B/N de cada mes y totales comparables de cada año. */
    public function porImpresora(): Collection
    {
        [$a, $b] = $this->anios();
        $porId = $this->filas->groupBy('dispositivo_id');

        return Dispositivo::orderBy('nivel')->get()->filter(fn ($d) => $porId->has($d->id))->map(function ($d) use ($porId, $a, $b) {
            $f = $porId[$d->id];
            $act = $this->comparables($b)->where('dispositivo_id', $d->id);

            return (object) ['id' => $d->id, 'ubicacion' => $d->ubicacion, 'nivel' => $d->nivel,
                'meses' => $f->groupBy('mes')->map->sum('bn'),
                'anterior' => $this->comparables($a)->where('dispositivo_id', $d->id)->sum('bn'),
                'actual' => $act->sum('bn'), 'copias' => $act->sum('copias')];
        })->sortByDesc('actual')->values();
    }

    /** Por unidad: año anterior vs. actual en los meses comparables. */
    public function porUnidad(): Collection
    {
        [$a, $b] = $this->anios();
        $ant = $this->comparables($a)->groupBy(fn ($f) => $f->cuenta?->unidad?->codigo ?? '?');

        return $this->comparables($b)->groupBy(fn ($f) => $f->cuenta?->unidad?->codigo ?? '?')
            ->map(fn ($f, $u) => (object) ['unidad' => $u, 'nombre' => $f->first()->cuenta?->unidad?->nombre,
                'anterior' => ($ant[$u] ?? collect())->sum('bn'), 'actual' => $f->sum('bn'), 'usuarios' => $f->pluck('cuenta_id')->unique()->count()])
            ->sortByDesc('actual')->values();
    }

    /** Usuarios de un año (todo lo registrado), de mayor a menor B/N. */
    public function usuarios(int $anio, ?int $limite = null): Collection
    {
        $u = $this->filas->where('anio', $anio)->groupBy('cuenta_id')->map(fn ($f) => self::sumar($f, [
            'clave' => $f->first()->cuenta?->id_cuenta, 'nombre' => $f->first()->cuenta?->nombre_cuenta,
            'unidad' => $f->first()->cuenta?->unidad?->codigo, 'meses' => $f->pluck('mes')->unique()->count(),
        ]))->sortByDesc('bn')->values();

        return $limite ? $u->take($limite) : $u;
    }

    /** Los 12 meses: [mes => ['anterior' => B/N|null, 'actual' => B/N|null]]. */
    public function serieAnual(): array
    {
        [$a, $b] = $this->anios();
        $m = $this->meses();
        $serie = [];
        foreach (range(1, 12) as $i) {
            $serie[$i] = ['anterior' => $m[sprintf('%d-%02d', $a, $i)]->bn ?? null, 'actual' => $m[sprintf('%d-%02d', $b, $i)]->bn ?? null];
        }

        return $serie;
    }

    /**
     * Meses que no se pueden leer solos.
     * tardia: [mes, mes siguiente] cuando un mes casi no tiene registro (< 20% del año anterior) y el siguiente
     * sale inflado (> 150%): el sistema anterior no leyó y sumó el consumo en la lectura siguiente.
     * parcial: el último mes del año actual si todavía no termina o quedó incompleto.
     */
    public function anomalias(): array
    {
        $s = $this->serieAnual();
        $tardias = [];
        foreach (range(1, 11) as $i) {
            [$x, $y] = [$s[$i], $s[$i + 1]];
            if ($x['actual'] !== null && $x['anterior'] && $y['actual'] && $y['anterior']
                && $x['actual'] < $x['anterior'] * 0.2 && $y['actual'] > $y['anterior'] * 1.5) {
                $tardias[] = [$i, $i + 1];
            }
        }
        $ultimo = max(array_keys(array_filter($s, fn ($v) => $v['actual'] !== null)) ?: [0]);

        return ['tardias' => $tardias, 'parcial' => $ultimo > $this->mesesComparables() ? $ultimo : null];
    }

    /** Qué parte del consumo del año hacen los usuarios del 10% superior: [usuarios, cuántos son el 10%, % del total]. */
    public function concentracion(int $anio): array
    {
        $u = $this->usuarios($anio)->pluck('bn');
        $k = (int) ceil($u->count() / 10);

        return [$u->count(), $k, $u->sum() ? round($u->take($k)->sum() / $u->sum() * 100, 1) : 0];
    }

    /** Hojas del Excel: mensual, impresora × mes, unidades, usuarios de cada año y detalle completo. */
    public function hojas(): array
    {
        [$a, $b] = $this->anios();
        $meses = $this->meses();
        $periodos = $meses->keys();
        $usuarios = fn ($anio) => ['titulo' => "Usuarios {$anio}", 'headings' => ['Clave', 'Usuario', 'Unidad', 'Impresiones', 'Copias', 'B/N', 'Escaneos', 'Meses con consumo'],
            'rows' => $this->usuarios($anio)->map(fn ($u) => [$u->clave, $u->nombre, $u->unidad, $u->impresiones, $u->copias, $u->bn, $u->escaneos, $u->meses])->all()];

        return [
            ['titulo' => 'Mensual', 'headings' => ['Mes', 'Impresiones', 'Copias', 'B/N (impr. + copias)', 'Escaneos', 'Usuarios activos'],
                'rows' => $meses->map(fn ($m) => [$m->periodo, $m->impresiones, $m->copias, $m->bn, $m->escaneos, $m->usuarios])->values()->all()],
            ['titulo' => 'Impresora por mes', 'headings' => ['Impresora', ...$periodos->all()],
                'rows' => $this->porImpresora()->map(fn ($i) => [$i->ubicacion, ...$periodos->map(fn ($p) => (int) ($i->meses[$p] ?? 0))->all()])->all()],
            ['titulo' => 'Unidades', 'headings' => ['Unidad', 'Nombre', "B/N {$a}", "B/N {$b}", 'Variación', "Usuarios {$b}"],
                'rows' => $this->porUnidad()->map(fn ($u) => [$u->unidad, $u->nombre, $u->anterior, $u->actual,
                    $u->anterior > 0 ? round(($u->actual - $u->anterior) / $u->anterior * 100, 1).'%' : '—', $u->usuarios])->all()],
            $usuarios($b),
            $usuarios($a),
            ['titulo' => 'Detalle', 'headings' => ['Mes', 'Impresora', 'Clave', 'Usuario', 'Impresiones', 'Copias', 'Escaneos'],
                'rows' => $this->filas->sortBy(['mes', 'dispositivo_id'])->load('dispositivo')
                    ->map(fn ($f) => [$f->mes, $f->dispositivo?->ubicacion, $f->cuenta?->id_cuenta, $f->cuenta?->nombre_cuenta, $f->impresiones, $f->copias, $f->escaneos])->values()->all()],
        ];
    }
}
