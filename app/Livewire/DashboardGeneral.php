<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConFiltros;
use App\Support\Consumo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard General')]
class DashboardGeneral extends Component
{
    use ConFiltros;

    public function mount(): void
    {
        $this->inicializarFiltros();
    }

    public function filtrar(): void
    {
        // wire:model deferido; el submit fuerza el re-render.
    }

    public function render()
    {
        $consumo = new Consumo($this->filtros());

        $topUsuarios = $consumo->porUsuario('costo_total', 'DESC', 10);
        $unidades = $consumo->porUnidad();

        return view('livewire.dashboard-general', [
            'totales' => $consumo->totales(),
            'topUsuarios' => $topUsuarios,
            'unidades' => $unidades,
            'opciones' => $this->opcionesFiltros(),
            'firma' => md5(json_encode($this->filtros())),
            'graficas' => [
                'usuarios' => [
                    'labels' => $topUsuarios->pluck('nombre_cuenta'),
                    'data' => $topUsuarios->pluck('costo_total')->map(fn ($v) => round($v, 2)),
                ],
                'unidades' => [
                    'labels' => $unidades->pluck('unidad')->map(fn ($v) => $v ?: 'S/U'),
                    'data' => $unidades->pluck('costo_total')->map(fn ($v) => round($v, 2)),
                ],
            ],
        ]);
    }
}
