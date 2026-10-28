<?php

namespace App\Support;

use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reporte detallado de fin de mes (mes calendario): consumo por impresora y por día, papel y dúplex,
 * tóner, usuarios y unidades, registro de trabajos completo, trabajos por hora y ficha técnica al cierre.
 * Cada hoja es ['titulo', 'headings', 'rows'] para el Excel; el PDF y el correo usan las mismas hojas.
 */
class ReporteMensual
{
    public readonly Carbon $desde;

    public readonly Carbon $hasta;

    private Collection $dispositivos;

    public function __construct(Carbon $mes)
    {
        $this->desde = $mes->copy()->startOfMonth();
        $this->hasta = $mes->copy()->endOfMonth();
        $this->dispositivos = Dispositivo::with('modelo')->orderBy('nivel')->get();
    }

    public function etiqueta(): string
    {
        return ucfirst($this->desde->translatedFormat('F Y'));
    }

    private function filtroFechas(): array
    {
        return ['fecha_inicio' => $this->desde->toDateString(), 'fecha_fin' => $this->hasta->toDateString()];
    }

    /** @return list<array{titulo:string,headings:array,rows:array}> */
    public function hojas(bool $conTrabajos = true): array
    {
        $hojas = [$this->impresoras(), $this->diario(), $this->papel(), $this->toner(), $this->usuarios(), $this->unidades(), $this->porHora()];
        if ($conTrabajos) {
            $hojas[] = self::hojaTrabajos(Trabajo::filtrar(['desde' => $this->desde->toDateString(), 'hasta' => $this->hasta->toDateString()]));
        }
        $hojas[] = $this->ficha();

        return $hojas;
    }

    /** Totales del mes para el cuerpo del correo. */
    public function totales(): array
    {
        $t = (new Consumo($this->filtroFechas()))->totales();
        $trabajos = Trabajo::filtrar(['desde' => $this->desde->toDateString(), 'hasta' => $this->hasta->toDateString()]);

        return $t + ['trabajos' => $trabajos->count(),'usuarios' => (new ConsumoCuentas($this->filtroFechas()))->porUsuario()->count()];
    }

    public function impresoras(): array
    {
        $c = new Consumo($this->filtroFechas());
        $rows = $c->porDispositivo('ubicacion', 'ASC')->map(fn ($r) => [
            $r->ubicacion, $r->nivel, $r->direccion_ip, $r->modelo, (int) $r->impresiones, (int) $r->copias, (int) $r->escaneos, (int) $r->total, round($r->costo_total, 2), $r->porcentaje.'%',
        ])->all();
        $t = $c->totales();
        $rows[] = ['TOTAL', '', '', '', (int) $t['impresiones'], (int) $t['copias'], (int) $t['escaneos'], (int) $t['total'], round($t['costo_total'], 2), '100%'];

        return ['titulo' => 'Consumo por impresora', 'headings' => ['Ubicación', 'Nivel', 'IP', 'Modelo', 'Impresiones', 'Copias', 'Escaneos', 'Total páginas', 'Costo (Q)', '% del costo'], 'rows' => $rows];
    }

    public function diario(): array
    {
        $c = new Consumo($this->filtroFechas());
        $matriz = $c->porDiaDispositivo();

        return [
            'titulo' => 'Consumo diario',
            'headings' => ['Fecha', ...$this->dispositivos->map(fn ($d) => $d->ubicacion ?? $d->nombre_host)->all(), 'Total páginas', 'Costo (Q)'],
            'rows' => $c->porDia()->map(fn ($dia) => [
                Carbon::parse($dia->fecha)->format('d/m/Y'),
                ...$this->dispositivos->map(fn ($d) => (int) ($matriz[$dia->fecha][$d->id]->total ?? 0))->all(),
                (int) $dia->total, round($dia->costo_total, 2),
            ])->all(),
        ];
    }

    /** Lecturas diarias con detalle (tóner, papel, dúplex) de una impresora: la última antes del mes como base + las del mes. */
    private function detalles(int $id): Collection
    {
        $q = ContadorEquipo::where('dispositivo_id', $id)->whereNotNull('detalle');
        $base = (clone $q)->where('leido_en', '<', $this->desde)->latest('leido_en')->first();
        $mes = (clone $q)->whereBetween('leido_en', [$this->desde, $this->hasta])->orderBy('leido_en')->get();

        return $base ? $mes->prepend($base) : $mes;
    }

    /** Páginas del mes por tamaño de papel y una cara / doble cara (diferencia entre la primera y la última lectura). */
    public function papel(): array
    {
        $datos = $this->dispositivos->map(function ($d) {
            $l = $this->detalles($d->id);
            [$a, $b] = [$l->first()?->detalle, $l->last()?->detalle];
            if ($l->count() < 2) {
                return ['d' => $d, 'papel' => [], 'simplex' => null, 'duplex' => null];
            }
            $papel = [];
            foreach ($b['papel'] ?? [] as $tam => $pag) {
                $papel[$tam] = max(0, $pag - ($a['papel'][$tam] ?? 0));
            }

            return ['d' => $d, 'papel' => $papel,
                'simplex' => isset($a['simplex'], $b['simplex']) ? max(0, $b['simplex'] - $a['simplex']) : null,
                'duplex' => isset($a['duplex'], $b['duplex']) ? max(0, $b['duplex'] - $a['duplex']) : null];
        });
        $tamanos = $datos->flatMap(fn ($x) => array_keys(array_filter($x['papel'])))->unique()->values();

        return [
            'titulo' => 'Papel y doble cara',
            'headings' => ['Impresora', ...$tamanos->map(fn ($t) => Dispositivo::nombrePapel($t))->all(), 'Una cara', 'Doble cara', '% doble cara'],
            'rows' => $datos->map(fn ($x) => [
                $x['d']->ubicacion,
                ...$tamanos->map(fn ($t) => $x['papel'] ? (int) ($x['papel'][$t] ?? 0) : 's/d')->all(),
                $x['simplex'] ?? 's/d', $x['duplex'] ?? 's/d',
                ($x['simplex'] + $x['duplex']) > 0 ? round($x['duplex'] / ($x['simplex'] + $x['duplex']) * 100, 1).'%' : 's/d',
            ])->all(),
        ];
    }

    public function toner(): array
    {
        return [
            'titulo' => 'Tóner',
            'headings' => ['Impresora', 'Cartucho', 'Al inicio del mes', 'Al cierre', 'Cambios de cartucho en el mes'],
            'rows' => $this->dispositivos->map(function ($d) {
                $niveles = $this->detalles($d->id)->map(fn ($l) => $l->detalle['toner']['pct'] ?? null)->filter(fn ($v) => $v !== null)->values();
                $cierre = $d->estado['toner']['pct'] ?? $niveles->last();
                // ponytail: un salto de 30 puntos o más entre lecturas diarias se cuenta como cartucho nuevo.
                $cambios = $niveles->sliding(2)->filter(fn ($p) => $p->last() - $p->first() >= 30)->count();

                return [$d->ubicacion, $d->estado['toner']['nombre'] ?? '—', $niveles->isEmpty() ? 's/d' : $niveles->first().'%', $cierre === null ? 's/d' : $cierre.'%', $niveles->count() > 1 ? $cambios : 's/d'];
            })->all(),
        ];
    }

    public function usuarios(): array
    {
        return [
            'titulo' => 'Consumo por usuario',
            'headings' => ['Clave', 'Usuario', 'Unidad', 'Impresiones', 'Copias', 'Escaneos', 'Costo (Q)'],
            'rows' => (new ConsumoCuentas($this->filtroFechas()))->porUsuario()->map(fn ($u) => [
                $u->id_cuenta, $u->nombre_cuenta, $u->unidad, (int) $u->impresiones, (int) $u->copias, (int) $u->escaneos, round($u->costo_total, 2),
            ])->all(),
        ];
    }

    public function unidades(): array
    {
        return [
            'titulo' => 'Consumo por unidad',
            'headings' => ['Unidad', 'Nombre', 'Usuarios', 'Impresiones', 'Copias', 'Escaneos', 'Costo (Q)', '%'],
            'rows' => (new ConsumoCuentas($this->filtroFechas()))->porUnidad()->map(fn ($u) => [
                $u->unidad, $u->unidad_nombre, $u->usuarios, (int) $u->impresiones, (int) $u->copias, (int) $u->escaneos, round($u->costo_total, 2), $u->porcentaje.'%',
            ])->all(),
        ];
    }

    /** Páginas por hora del día y tipo de trabajo (registro de trabajos). */
    public function porHora(): array
    {
        $tipos = array_keys(Trabajo::TIPOS);
        $horas = Trabajo::filtrar(['desde' => $this->desde->toDateString(), 'hasta' => $this->hasta->toDateString()])
            ->where('estado', 'completado')->get(['inicio', 'tipo', 'paginas'])
            ->groupBy(fn ($t) => (int) $t->inicio->format('G'));

        $rows = [];
        foreach ($horas->keys()->sort() as $h) {
            $porTipo = $horas[$h]->groupBy('tipo');
            $rows[] = [sprintf('%02d:00 – %02d:59', $h, $h), ...array_map(fn ($t) => (int) ($porTipo->get($t)?->sum('paginas') ?? 0), $tipos), $horas[$h]->count()];
        }

        return ['titulo' => 'Trabajos por hora', 'headings' => ['Hora', ...array_values(Trabajo::TIPOS), 'Trabajos'], 'rows' => $rows];
    }

    /** Registro de trabajos (también lo usa el Excel de la página Registro de trabajos). */
    public static function hojaTrabajos(Builder $q): array
    {
        return [
            'titulo' => 'Registro de trabajos',
            'headings' => ['Fecha', 'Hora', 'Impresora', 'Tipo', 'Cuenta', 'Usuario (Windows)', 'Documento', 'Páginas', 'Originales', 'Juegos', 'Estado'],
            'rows' => $q->with('dispositivo')->orderBy('inicio')->get()->map(fn (Trabajo $t) => [
                $t->inicio?->format('d/m/Y'), $t->inicio?->format('H:i'), $t->dispositivo?->ubicacion, Trabajo::TIPOS[$t->tipo] ?? $t->tipo,
                $t->cuenta, $t->usuario, $t->documento, $t->paginas, $t->originales, $t->juegos, $t->estado,
            ])->all(),
        ];
    }

    /** Ficha técnica y estado de cada impresora al cierre. */
    public function ficha(): array
    {
        return [
            'titulo' => 'Ficha técnica y estado',
            'headings' => ['Impresora', 'IP', 'Modelo', 'Serie', 'MAC', 'Firmware', 'Instalada', 'Estado', 'Tóner', 'Alertas activas', 'Páginas una cara (vida)', 'Páginas doble cara (vida)', 'Actualizado'],
            'rows' => $this->dispositivos->map(function ($d) {
                $e = $d->estado ?? [];

                return [
                    $d->ubicacion, $d->direccion_ip, $d->modelo?->nombre, $d->serie, $e['mac'] ?? '—', $e['firmware'] ?? '—', $e['instalada'] ?? '—',
                    $e['estado'] ?? 'sin datos', isset($e['toner']['pct']) ? $e['toner']['pct'].'%' : '—', count($e['alertas'] ?? []),
                    $e['simplex'] ?? '—', $e['duplex'] ?? '—', $d->estado_en?->format('d/m/Y H:i') ?? '—',
                ];
            })->all(),
        ];
    }
}
