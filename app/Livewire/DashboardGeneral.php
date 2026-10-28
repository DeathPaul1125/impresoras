<?php

namespace App\Livewire;

use App\Support\Contrato;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Dashboard del contrato: consumo acumulado contra el bolsón B/N, por corte y por impresora. */
#[Layout('layouts.app')]
#[Title('Dashboard')]
class DashboardGeneral extends Component
{
    public const COLORES = ['#1d4ed8', '#f97316', '#15803d', '#7e22ce', '#f59e0b', '#0891b2', '#1e3a8a', '#db2777', '#65a30d'];

    public function render()
    {
        $contrato = new Contrato;
        $r = $contrato->resumen();

        return view('livewire.dashboard-general', [
            'r' => $r,
            'hallazgos' => $contrato->hallazgos($r),
            'colores' => self::COLORES,
        ]);
    }
}
