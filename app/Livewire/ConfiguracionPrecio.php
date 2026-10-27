<?php

namespace App\Livewire;

use App\Models\Configuracion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Configuración')]
class ConfiguracionPrecio extends Component
{
    public float $precio_unitario = 0.17;

    public function mount(): void
    {
        $this->precio_unitario = Configuracion::precioUnitario();
    }

    public function guardar(): void
    {
        $this->validate([
            'precio_unitario' => 'required|numeric|min:0',
        ]);

        Configuracion::setPrecioUnitario($this->precio_unitario);

        session()->flash('ok', 'Precio unitario actualizado a Q'.number_format($this->precio_unitario, 2).'.');
    }

    public function render()
    {
        return view('livewire.configuracion-precio');
    }
}
