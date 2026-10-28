<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConFiltros;
use App\Support\Consumo;
use App\Support\ConsumoCuentas;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tablero de Totales')]
class Tablero extends Component
{
    use ConFiltros;

    #[Url]
    public string $sort_field = 'costo_total';

    #[Url]
    public string $sort_order = 'DESC';

    public function mount(): void
    {
        $this->inicializarFiltros();
    }

    public function ordenar(string $campo): void
    {
        if (! in_array($campo, Consumo::ORDENABLES, true)) {
            return;
        }
        if ($this->sort_field === $campo) {
            $this->sort_order = $this->sort_order === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->sort_field = $campo;
            $this->sort_order = 'DESC';
        }
    }

    /** Nivel de uso relativo al equipo de mayor consumo B/N: [etiqueta, clases]. */
    public static function nivel(int $valor, int $max): array
    {
        $pct = $max ? $valor / $max * 100 : 0;

        return match (true) {
            $pct >= 90 => ['CRÍTICA', 'text-red-400'],
            $pct >= 75 => ['ALTA', 'text-orange-400'],
            $pct >= 50 => ['MEDIA', 'text-amber-300'],
            $pct >= 30 => ['MODERADA', 'text-yellow-300'],
            $pct >= 10 => ['BAJA', 'text-green-400'],
            default => ['MÍNIMA', 'text-emerald-300'],
        };
    }

    /** Hallazgos del período: [título, texto]. */
    private function hallazgos($equipos): array
    {
        $con = $equipos->filter(fn ($e) => $e->bn + $e->escaneos > 0)->values();
        if ($con->isEmpty()) {
            return [];
        }
        $intenso = $con->filter(fn ($e) => $e->prom_usuario)->sortByDesc('prom_usuario')->first();
        $k = fn ($v) => $v >= 1000 ? round($v / 1000).'k' : number_format($v);
        $top = $con->first();
        $escaneo = $con->sortByDesc(fn ($e) => $e->escaneos / max(1, $e->bn))->first();
        $bajo = $con->sortBy('bn')->first();
        $copias = $con->sortByDesc(fn ($e) => $e->copias / max(1, $e->bn))->first();

        return [
            ['Punto de flujo crítico', "{$top->ubicacion} lidera con {$k($top->bn)} páginas B/N ({$k($top->impresiones)} impresiones, {$k($top->copias)} copias) y {$k($top->escaneos)} escaneos."],
            ['Foco en escaneo', "{$escaneo->ubicacion} escanea ".round($escaneo->escaneos / max(1, $escaneo->bn), 1).' páginas por cada página impresa o copiada.'],
            ['Oportunidad de balance', "{$bajo->ubicacion} es el equipo menos usado en papel ({$k($bajo->bn)}); puede absorber carga de {$top->ubicacion}."],
            $intenso
                ? ['Intensidad de usuario', "{$intenso->ubicacion} tiene el mayor promedio: ".number_format($intenso->prom_usuario).' páginas B/N por usuario.']
                : ['Mayor uso como copiadora', "En {$copias->ubicacion} el ".round($copias->copias / max(1, $copias->bn) * 100).'% del papel son copias.'],
        ];
    }

    public function filtrar(): void
    {
        // wire:model deferido; el submit sólo fuerza el re-render.
    }

    public function render()
    {
        $consumo = new Consumo($this->filtros());

        $porDia = $consumo->porDia();
        $matriz = $consumo->porDiaDispositivo();
        $tendencias = $consumo->tendenciaMensual();
        $columnas = $consumo->dispositivos();   // orden fijo por ubicación para la tabla día × impresora

        $impresoras = $consumo->porDispositivo($this->sort_field, $this->sort_order);
        $cuentas = new ConsumoCuentas($this->filtros());
        $promedios = $cuentas->promedioPorUsuario();
        $equipos = $impresoras->each(function ($r) use ($promedios) {
            $r->bn = $r->impresiones + $r->copias;
            $r->prom_usuario = $promedios[$r->id] ?? null;
        })->sortByDesc('bn')->values();

        return view('livewire.tablero', [
            'totales' => $consumo->totales(),
            'impresoras' => $impresoras,
            'equipos' => $equipos,
            'hallazgos' => $this->hallazgos($equipos),
            'usuarios' => $cuentas->porUsuario(),
            'unidadesConsumo' => $cuentas->porUnidad(),
            'hayCuentas' => $cuentas->hayDatos(),
            'porDia' => $porDia,
            'matriz' => $matriz,
            'columnas' => $columnas,
            'tendencias' => $tendencias,
            'opciones' => $this->opcionesFiltros(),
            'firma' => md5(json_encode($this->filtros()).$this->sort_field.$this->sort_order),
            'graficas' => [
                'dias' => [
                    'labels' => $porDia->map(fn ($d) => Carbon::parse($d->fecha)->format('d/m')),
                    'series' => $columnas->map(fn ($d) => [
                        'label' => $d->ubicacion ?? $d->nombre_host,
                        'data' => $porDia->map(fn ($dia) => $matriz[$dia->fecha][$d->id]->total ?? 0),
                    ])->values(),
                ],
                'tendencias' => [
                    'labels' => $tendencias->pluck('periodo'),
                    'data' => $tendencias->pluck('costo_total')->map(fn ($v) => round($v, 2)),
                ],
            ],
        ]);
    }
}
