<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConFiltros;
use App\Support\Consumo;
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

        $usuarios = $consumo->porUsuario($this->sort_field, $this->sort_order);
        $unidades = $consumo->porUnidad();
        $dispositivos = $consumo->porDispositivo();
        $tendencias = $consumo->tendenciaMensual();

        return view('livewire.tablero', [
            'totales' => $consumo->totales(),
            'usuarios' => $usuarios,
            'unidades' => $unidades,
            'dispositivos' => $dispositivos,
            'tendencias' => $tendencias,
            'opciones' => $this->opcionesFiltros(),
            'firma' => md5(json_encode($this->filtros()).$this->sort_field.$this->sort_order),
            'graficas' => [
                'usuarios' => [
                    'labels' => $usuarios->take(10)->pluck('nombre_cuenta'),
                    'data' => $usuarios->take(10)->pluck('costo_total')->map(fn ($v) => round($v, 2)),
                ],
                'unidades' => [
                    'labels' => $unidades->pluck('unidad')->map(fn ($v) => $v ?: 'S/U'),
                    'data' => $unidades->pluck('costo_total')->map(fn ($v) => round($v, 2)),
                ],
                'tendencias' => [
                    'labels' => $tendencias->pluck('periodo'),
                    'data' => $tendencias->pluck('costo_total')->map(fn ($v) => round($v, 2)),
                ],
            ],
        ]);
    }
}
