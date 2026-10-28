<?php

namespace App\Support;

use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Models\Trabajo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reporte de un período (día, semana o mes): resumen con comparación, consumo por impresora y por día,
 * papel y dúplex, tóner, usuarios y unidades, trabajos por hora, registro de trabajos y estado de los equipos.
 * Cada hoja es ['titulo', 'headings', 'rows']: el Excel lleva todas, el PDF todas menos el registro de trabajos,
 * y el cuerpo del correo usa correo(). Lo usan los reportes diario, semanal y detallado de fin de mes.
 */
class ReportePeriodo
{
    public readonly Carbon $desde;

    public readonly Carbon $hasta;

    private Collection $dispositivos;

    public function __construct(Carbon $desde, Carbon $hasta, public readonly string $tipo = 'periodo')
    {
        $this->desde = $desde->copy()->startOfDay();
        $this->hasta = $hasta->copy()->endOfDay();
        $this->dispositivos = Dispositivo::with('modelo')->orderBy('nivel')->get();
    }

    public static function dia(Carbon $dia): self
    {
        return new self($dia, $dia, 'diario');
    }

    /** Lunes a viernes de la semana del día dado (hasta ese día). */
    public static function semana(Carbon $hasta): self
    {
        return new self($hasta->copy()->startOfWeek(), $hasta, 'semanal');
    }

    public static function mes(Carbon $mes): self
    {
        return new self($mes->copy()->startOfMonth(), $mes->copy()->endOfMonth(), 'detallado');
    }

    public function etiqueta(): string
    {
        return match ($this->tipo) {
            'diario' => ucfirst($this->desde->translatedFormat('l d/m/Y')),
            'detallado' => ucfirst($this->desde->translatedFormat('F Y')),
            default => $this->desde->format('d/m/Y').' – '.$this->hasta->format('d/m/Y'),
        };
    }

    public function titulo(): string
    {
        return match ($this->tipo) {
            'diario' => 'Reporte diario · '.$this->desde->format('d/m/Y'),
            'semanal' => 'Reporte semanal (lunes a viernes) · '.$this->etiqueta(),
            'detallado' => 'Reporte detallado de '.$this->etiqueta(),
            default => 'Reporte · '.$this->etiqueta(),
        };
    }

    /** Período anterior equivalente: día hábil anterior, semana anterior o mes anterior. */
    public function anterior(): self
    {
        return match ($this->tipo) {
            'diario' => self::dia($this->desde->copy()->subWeekday()),
            'detallado' => self::mes($this->desde->copy()->subMonthNoOverflow()),
            default => new self($this->desde->copy()->subWeek(), $this->hasta->copy()->subWeek(), $this->tipo),
        };
    }

    private function trabajos(): Builder
    {
        return Trabajo::filtrar(['desde' => $this->desde->toDateString(), 'hasta' => $this->hasta->toDateString()]);
    }

    /** Datos del cuerpo del correo: indicadores con comparación, tablas resumidas y estado de los equipos. */
    public function correo(): array
    {
        $t = $this->totales();
        $a = $this->anterior()->totales();
        $var = fn ($k) => $a[$k] > 0 ? round(($t[$k] - $a[$k]) / $a[$k] * 100, 1) : null;
        $c = new Consumo($this->filtroFechas());
        $cuentas = new ConsumoCuentas($this->filtroFechas());
        $r = (new Contrato)->resumen();
        $hora = collect($this->porHora()['rows'])->sortByDesc(fn ($f) => array_sum(array_slice($f, 1, -1)))->first();

        return [
            'totales' => $t,
            'anterior' => ['etiqueta' => $this->anterior()->etiqueta(), 'total' => $a['total'], 'costo_total' => $a['costo_total']],
            'variacion' => ['total' => $var('total'), 'costo_total' => $var('costo_total')],
            'impresoras' => $c->porDispositivo('total', 'DESC'),
            'dias' => $this->desde->isSameDay($this->hasta) ? collect() : $c->porDia(),
            'usuarios' => $cuentas->porUsuario()->take(10),
            'unidades' => $cuentas->porUnidad(),
            'hora_pico' => $hora[0] ?? null,
            'bolson' => $r['bolson'] ? ['contrato' => $r['contrato'], 'acumulado' => $r['acumulado'], 'bolson' => $r['bolson'],
                'porcentaje' => $r['porcentaje'], 'disponible' => $r['disponible'], 'agotamiento' => $r['agotamiento']] : null,
            'toner' => $this->dispositivos->mapWithKeys(fn ($d) => [$d->id => $d->estado['toner']['pct'] ?? null]),
            'atencion' => self::atencion($this->dispositivos),
        ];
    }

    /**
     * Lo que hay que atender hoy en los equipos: sin respuesta, tóner bajo y alertas activas.
     *
     * @return list<array{impresora:string,texto:string,accion:?string,grave:bool}>
     */
    public static function atencion(?Collection $dispositivos = null): array
    {
        $lista = [];
        foreach ($dispositivos ?? Dispositivo::orderBy('nivel')->get() as $d) {
            $e = $d->estado ?? [];
            $t = $e['toner']['pct'] ?? null;
            if (($e['estado'] ?? null) === 'sin_respuesta') {
                $lista[] = ['impresora' => $d->ubicacion, 'texto' => 'No responde en la red', 'accion' => 'Revisar que esté encendida y conectada.', 'grave' => true];
            }
            if ($t !== null && $t < 25) {
                $lista[] = ['impresora' => $d->ubicacion, 'texto' => "Tóner al {$t}%", 'grave' => $t < 10,
                    'accion' => $t < 10 ? 'Cambiar el cartucho o solicitarlo al proveedor de inmediato.' : 'Solicitar un cartucho de reemplazo al proveedor.'];
            }
            foreach (AlertasImpresora::activas($e['alertas'] ?? []) as $a) {
                if (in_array($a['codigo'], [1101, 1104], true) && $t !== null) {
                    continue;   // el tóner ya se informa con su porcentaje
                }
                $lista[] = ['impresora' => $d->ubicacion, 'texto' => AlertasImpresora::texto($a), 'accion' => $a['accion'] ?? null, 'grave' => ($a['nivel'] ?? '') === 'critica'];
            }
        }

        return $lista;
    }

    public function hojaAtencion(): array
    {
        return [
            'titulo' => 'Atención de equipos',
            'headings' => ['Impresora', 'Qué pasa', 'Qué hacer', 'Prioridad'],
            'rows' => array_map(fn ($x) => [$x['impresora'], $x['texto'], $x['accion'] ?? '—', $x['grave'] ? 'Alta' : 'Normal'], self::atencion($this->dispositivos))
                ?: [['Todos los equipos', 'Sin novedades', '—', '—']],
        ];
    }

    /** Hoja de resumen: indicadores del período frente al anterior. */
    public function resumen(): array
    {
        $t = $this->totales();
        $ant = $this->anterior();
        $a = $ant->totales();
        $fila = fn ($k, $nombre, $q = false) => [$nombre, $q ? round($t[$k], 2) : (int) $t[$k], $q ? round($a[$k], 2) : (int) $a[$k],
            $a[$k] > 0 ? round(($t[$k] - $a[$k]) / $a[$k] * 100, 1).'%' : '—'];

        return [
            'titulo' => 'Resumen',
            'headings' => ['Indicador', $this->etiqueta(), 'Anterior: '.$ant->etiqueta(), 'Variación'],
            'rows' => [
                $fila('total', 'Páginas (impresiones + copias + fax)'),
                $fila('impresiones', 'Impresiones'),
                $fila('copias', 'Copias'),
                $fila('escaneos', 'Escaneos (sin costo)'),
                $fila('costo_total', 'Costo (Q)', true),
                $fila('trabajos', 'Trabajos registrados'),
                $fila('usuarios', 'Usuarios con consumo'),
            ],
        ];
    }

    private function filtroFechas(): array
    {
        return ['fecha_inicio' => $this->desde->toDateString(), 'fecha_fin' => $this->hasta->toDateString()];
    }

    /** @return list<array{titulo:string,headings:array,rows:array}> */
    public function hojas(bool $conTrabajos = true): array
    {
        $hojas = [$this->resumen(), $this->hojaAtencion(), $this->impresoras(), $this->diario(), $this->papel(), $this->toner(), $this->usuarios(), $this->unidades(), $this->porHora()];
        if ($conTrabajos) {
            $hojas[] = self::hojaTrabajos($this->trabajos());
        }
        $hojas[] = $this->ficha();

        return $hojas;
    }

    /** Totales del período. */
    public function totales(): array
    {
        return (new Consumo($this->filtroFechas()))->totales()
            + ['trabajos' => $this->trabajos()->count(), 'usuarios' => (new ConsumoCuentas($this->filtroFechas()))->porUsuario()->count()];
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
        $horas = $this->trabajos()
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
            'titulo' => 'Ficha técnica',
            'headings' => ['Impresora', 'IP', 'Modelo', 'Serie', 'MAC', 'Firmware', 'Instalada', 'Estado', 'Tóner', 'Alertas activas', 'Páginas una cara (vida)', 'Páginas doble cara (vida)', 'Actualizado'],
            'rows' => $this->dispositivos->map(function ($d) {
                $e = $d->estado ?? [];

                return [
                    $d->ubicacion, $d->direccion_ip, $d->modelo?->nombre, $d->serie, $e['mac'] ?? '—', $e['firmware'] ?? '—', $e['instalada'] ?? '—',
                    ['disponible' => 'Disponible', 'imprimiendo' => 'Imprimiendo', 'calentando' => 'Calentando', 'sin_respuesta' => 'Sin respuesta'][$e['estado'] ?? ''] ?? 'Sin datos', isset($e['toner']['pct']) ? $e['toner']['pct'].'%' : '—',
                    collect(AlertasImpresora::activas($e['alertas'] ?? []))->map(fn ($a) => AlertasImpresora::texto($a))->implode('; ') ?: 'Ninguna',
                    $e['simplex'] ?? '—', $e['duplex'] ?? '—', $d->estado_en?->format('d/m/Y H:i') ?? '—',
                ];
            })->all(),
        ];
    }
}
