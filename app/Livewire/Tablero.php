<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConFiltros;
use App\Support\Consumo;
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

        return view('livewire.tablero', [
            'totales' => $consumo->totales(),
            'impresoras' => $consumo->porDispositivo($this->sort_field, $this->sort_order),
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
