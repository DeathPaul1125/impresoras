<?php

namespace App\Livewire;

use App\Models\Dispositivo;
use App\Support\Historia;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Cómo ha ido el consumo mes a mes desde 2025: por impresora, unidad y usuario (datos del sistema anterior). */
#[Layout('layouts.app')]
#[Title('Histórico')]
class Historico extends Component
{
    #[Url]
    public string $dispositivo = '';

    #[Url]
    public ?int $anio = null;

    public function render()
    {
        $h = new Historia($this->dispositivo !== '' ? (int) $this->dispositivo : null);
        [, $actual] = $h->anios();

        return view('livewire.historico', [
            'h' => $h,
            'anios' => $h->anios(),
            'meses' => $h->meses(),
            'comp' => $h->comparativo(),
            'impresoras' => $h->porImpresora(),
            'unidades' => $h->porUnidad(),
            'usuarios' => $h->usuarios($this->anio ?? $actual, 15),
            'dispositivos' => Dispositivo::orderBy('nivel')->get(),
        ]);
    }
}
