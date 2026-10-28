<?php

namespace App\Support;

use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Corte;
use App\Models\Dispositivo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consumo contra el contrato del proveedor (bolsón B/N).
 * Contador del contrato = impresiones + copias (coincide con los contadores que reporta el proveedor).
 * Producido de un equipo en un período = contador al cierre − contador al corte anterior,
 * solo si la serie es la misma; si cambió, no se puede calcular y se reporta como alerta.
 */
class Contrato
{
    public array $cfg;

    /** @var Collection<int, Dispositivo> ordenadas por nivel */
    public Collection $dispositivos;

    /** @var Collection<int, Carbon> fechas de corte; la primera es el contador inicial */
    public Collection $fechas;

    /** [fecha Y-m-d H:i:s][dispositivo_id] => Corte */
    private array $cortes = [];

    /** [dispositivo_id] => ['contador', 'serie', 'fecha'] última lectura SNMP */
    public array $actual = [];

    public function __construct()
    {
        $this->cfg = Configuracion::contrato();
        $this->dispositivos = Dispositivo::orderByRaw('nivel IS NULL, nivel')->orderBy('ubicacion')->get()->keyBy('id');

        $cortes = Corte::orderBy('fecha')->get();
        foreach ($cortes as $c) {
            $this->cortes[$c->fecha->format('Y-m-d H:i:s')][$c->dispositivo_id] = $c;
        }
        $this->fechas = $cortes->pluck('fecha')->unique(fn ($f) => $f->format('Y-m-d H:i:s'))->values();

        $ultimas = ContadorEquipo::whereIn('id', ContadorEquipo::selectRaw('MAX(id)')->groupBy('dispositivo_id'))->get();
        foreach ($ultimas as $l) {
            if ($l->impresiones !== null && $l->copias !== null) {
                $this->actual[$l->dispositivo_id] = [
                    'contador' => $l->impresiones + $l->copias,
                    'serie' => $this->dispositivos[$l->dispositivo_id]->serie ?? null,
                    'fecha' => $l->leido_en,
                ];
            }
        }
    }

    public static function etiquetaMes(Carbon $f): string
    {
        return ucfirst($f->locale('es')->translatedFormat('F Y'));
    }

    private function punto(?Corte $c): ?array
    {
        return $c ? ['contador' => $c->contador, 'serie' => $c->serie] : null;
    }

    /** Producido entre dos puntos del mismo equipo; null si falta alguno o cambió la serie. */
    private function producido(?array $desde, ?array $hasta): ?int
    {
        if (! $desde || ! $hasta || ($desde['serie'] && $hasta['serie'] && $desde['serie'] !== $hasta['serie'])) {
            return null;
        }

        return max(0, $hasta['contador'] - $desde['contador']);
    }

    public function contadorEnCorte(Carbon $fecha, int $dispositivoId): ?Corte
    {
        return $this->cortes[$fecha->format('Y-m-d H:i:s')][$dispositivoId] ?? null;
    }

    /**
     * Períodos cerrados (entre cortes): [{fecha, etiqueta, producido[id], total, saldo}].
     */
    public function periodos(): Collection
    {
        $saldo = (int) $this->cfg['bolson'];
        $periodos = collect();

        for ($i = 1; $i < $this->fechas->count(); $i++) {
            $prev = $this->fechas[$i - 1];
            $fecha = $this->fechas[$i];
            $producido = [];
            foreach ($this->dispositivos as $id => $d) {
                $producido[$id] = $this->producido($this->punto($this->contadorEnCorte($prev, $id)), $this->punto($this->contadorEnCorte($fecha, $id)));
            }
            $total = array_sum(array_filter($producido));
            $saldo -= $total;
            $periodos->push((object) [
                'desde' => $prev, 'fecha' => $fecha, 'etiqueta' => self::etiquetaMes($fecha),
                'producido' => $producido, 'total' => $total, 'saldo' => $saldo,
            ]);
        }

        return $periodos;
    }

    /** Período en curso: desde el último corte hasta la última lectura SNMP. */
    public function enCurso(): object
    {
        $ultimo = $this->fechas->last();
        $producido = [];
        foreach ($this->dispositivos as $id => $d) {
            $producido[$id] = $ultimo ? $this->producido($this->punto($this->contadorEnCorte($ultimo, $id)), $this->actual[$id] ?? null) : null;
        }
        $hasta = collect($this->actual)->pluck('fecha')->max();

        return (object) ['desde' => $ultimo, 'hasta' => $hasta, 'producido' => $producido, 'total' => array_sum(array_filter($producido))];
    }

    /** Resumen completo para el dashboard, los reportes y los correos. */
    public function resumen(): array
    {
        $periodos = $this->periodos();
        $enCurso = $this->enCurso();
        $bolson = (int) $this->cfg['bolson'];

        $porEquipo = $this->dispositivos->map(function (Dispositivo $d) use ($periodos, $enCurso) {
            $acumulado = $periodos->sum(fn ($p) => $p->producido[$d->id] ?? 0) + ($enCurso->producido[$d->id] ?? 0);
            $ultimoCorte = $this->fechas->isNotEmpty() ? $this->contadorEnCorte($this->fechas->last(), $d->id) : null;

            return (object) [
                'id' => $d->id,
                'ubicacion' => $d->ubicacion ?? $d->nombre_host,
                'nivel' => $d->nivel,
                'serie' => $d->serie,
                'serie_contrato' => $ultimoCorte?->serie,
                'modelo' => $d->modelo?->nombre,
                'acumulado' => $acumulado,
                'ultimo_periodo' => $periodos->last()?->producido[$d->id] ?? null,
                'en_curso' => $enCurso->producido[$d->id] ?? null,
                'contador_actual' => $this->actual[$d->id]['contador'] ?? null,
            ];
        })->values();

        $acumulado = $porEquipo->sum('acumulado');
        $porEquipo->each(fn ($e) => $e->porcentaje = $acumulado ? round($e->acumulado / $acumulado * 100, 1) : 0);

        $cerrados = $periodos->count();
        $promedio = $cerrados ? (int) round($periodos->sum('total') / $cerrados) : 0;
        $disponible = $bolson - $acumulado;
        $inicio = $this->cfg['inicio'] ? Carbon::parse($this->cfg['inicio']) : $this->fechas->first();
        $finContrato = $this->cfg['fin'] ? Carbon::parse($this->cfg['fin']) : $inicio?->copy()->addYear()->subDay();

        // Proyección: al ritmo promedio mensual, ¿cuándo se agota el bolsón?
        $agotamiento = ($promedio > 0 && $periodos->isNotEmpty())
            ? $periodos->last()->fecha->copy()->addDays((int) round(($bolson - $periodos->sum('total')) / $promedio * 30.44))
            : null;

        return [
            'contrato' => $this->cfg['numero'],
            'bolson' => $bolson,
            'inicio' => $inicio,
            'fin' => $finContrato,
            'mensual_aprox' => (int) round($bolson / 12),
            'promedio_mensual' => $promedio,
            'acumulado' => $acumulado,
            'disponible' => $disponible,
            'porcentaje' => $bolson ? round($acumulado / $bolson * 100, 1) : 0,
            'corte_al' => $enCurso->hasta ?? $this->fechas->last(),
            'periodos' => $periodos,
            'en_curso' => $enCurso,
            'por_equipo' => $porEquipo,
            'agotamiento' => $agotamiento,
            'alertas' => $this->alertas($porEquipo),
        ];
    }

    /** @return string[] */
    private function alertas(Collection $porEquipo): array
    {
        $alertas = [];
        foreach ($porEquipo as $e) {
            // Series registradas para el equipo en los cortes + la que reporta hoy, en orden.
            $series = $this->fechas->map(fn ($f) => $this->contadorEnCorte($f, $e->id)?->serie)->push($e->serie)->filter()->unique()->values();
            if ($series->count() > 1) {
                $alertas[] = "{$e->ubicacion}: cambió la serie del equipo ({$series->implode(' → ')}). "
                    .'El consumo del período en que ocurrió el cambio no se puede calcular hasta confirmar con el proveedor el contador final del equipo anterior.';
            }
            $ultimo = $this->fechas->last();
            if ($ultimo && ! $this->contadorEnCorte($ultimo, $e->id)) {
                $alertas[] = "{$e->ubicacion}: no tiene contador en el corte del {$ultimo->format('d/m/Y')} (no respondió). Ingréselo en Cortes con el dato del proveedor.";
            }
            if ($e->contador_actual === null) {
                $alertas[] = "{$e->ubicacion}: sin lecturas SNMP; revise que la impresora esté encendida y en red.";
            }
        }

        return $alertas;
    }

    /** Hallazgos en texto para el dashboard y los correos: [titulo, texto, icono, color]. */
    public function hallazgos(array $r): array
    {
        $h = [];
        $orden = $r['por_equipo']->sortByDesc('acumulado')->values();
        if ($orden->isNotEmpty() && $r['acumulado'] > 0) {
            $top = $orden->take(3);
            $h[] = ['Mayor consumo acumulado', sprintf('%s lidera con %s páginas (%s%% del total)%s.',
                $top[0]->ubicacion, number_format($top[0]->acumulado), $top[0]->porcentaje,
                $top->count() > 2 ? sprintf(', seguido por %s (%s%%) y %s (%s%%)', $top[1]->ubicacion, $top[1]->porcentaje, $top[2]->ubicacion, $top[2]->porcentaje) : ''),
                'fa-trophy', 'blue'];
            $min = $orden->last();
            $h[] = ['Menor consumo acumulado', sprintf('%s registra el menor consumo con %s páginas (%s%% del total).', $min->ubicacion, number_format($min->acumulado), $min->porcentaje), 'fa-arrow-trend-down', 'green'];
        }

        $p = $r['periodos'];
        if ($p->count() >= 2) {
            [$ant, $ult] = [$p[$p->count() - 2], $p->last()];
            $var = $ant->total ? round(($ult->total - $ant->total) / $ant->total * 100, 1) : 0;
            $h[] = ["Producción de {$ult->etiqueta}", sprintf('Se produjeron %s páginas, %s respecto a %s (%s%%). El saldo del bolsón al corte es de %s páginas.',
                number_format($ult->total), $var >= 0 ? 'un aumento' : 'una disminución', $ant->etiqueta, ($var > 0 ? '+' : '').$var, number_format($ult->saldo)),
                'fa-calendar-days', 'orange'];
        }

        if ($r['agotamiento'] && $r['fin']) {
            $alcanza = $r['agotamiento']->gte($r['fin']);
            $h[] = ['Proyección del bolsón', $alcanza
                ? sprintf('Al ritmo promedio de %s páginas/mes, el bolsón alcanza para todo el contrato (hasta %s) y sobrarían unas %s páginas.',
                    number_format($r['promedio_mensual']), $r['fin']->format('d/m/Y'),
                    number_format(max(0, (int) round($r['bolson'] - $r['periodos']->sum('total') - $r['promedio_mensual'] * $r['periodos']->last()->fecha->diffInDays($r['fin']) / 30.44))))
                : sprintf('Al ritmo promedio de %s páginas/mes, el bolsón se agotaría alrededor del %s, antes del fin del contrato (%s).',
                    number_format($r['promedio_mensual']), $r['agotamiento']->format('d/m/Y'), $r['fin']->format('d/m/Y')),
                'fa-chart-line', $alcanza ? 'green' : 'red'];
        }

        return $h;
    }

    /**
     * Registra un corte con los contadores leídos ahora por SNMP. Devuelve [ubicación => contador|null].
     */
    public static function registrarCorte(?Carbon $fecha = null, ?array $soloIds = null, int $intentos = 3): array
    {
        $fecha = ($fecha ?? now())->copy()->startOfMinute();
        $resultado = [];

        $equipos = Dispositivo::whereNotNull('direccion_ip')->when($soloIds, fn ($q) => $q->whereIn('id', $soloIds))->orderBy('nivel')->get();
        foreach ($equipos as $d) {
            // ponytail: reintentos simples; a veces una impresora no responde al primer intento (ahorro de energía).
            $l = null;
            for ($i = 1; $i <= $intentos && ! $l; $i++) {
                $l = Lector::leer($d->direccion_ip);
                if (! $l && $i < $intentos) {
                    sleep(5);
                }
            }
            if (! $l || $l['impresiones'] === null || $l['copias'] === null) {
                $resultado[$d->ubicacion ?? $d->nombre_host] = null;

                continue;
            }

            Corte::updateOrCreate(
                ['fecha' => $fecha, 'dispositivo_id' => $d->id],
                ['serie' => $l['serie'], 'contador' => $l['impresiones'] + $l['copias'], 'origen' => 'snmp'],
            );
            // De paso queda como lectura diaria.
            ContadorEquipo::create([
                'dispositivo_id' => $d->id, 'total' => $l['total'], 'impresiones' => $l['impresiones'],
                'copias' => $l['copias'], 'escaneos' => $l['escaneos'], 'leido_en' => $fecha,
            ]);
            if (! $d->serie && $l['serie']) {
                $d->update(['serie' => $l['serie']]);
            }
            $resultado[$d->ubicacion ?? $d->nombre_host] = $l['impresiones'] + $l['copias'];
        }

        return $resultado;
    }
}
