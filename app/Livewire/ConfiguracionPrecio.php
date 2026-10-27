<?php

namespace App\Livewire;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Configuración')]
class ConfiguracionPrecio extends Component
{
    public float $precio_unitario = 0.17;

    /** Separados por coma. */
    public string $correos_reportes = '';

    public function mount(): void
    {
        $this->precio_unitario = Configuracion::precioUnitario();
        $this->correos_reportes = implode(', ', Configuracion::correosReportes());
    }

    public function guardar(): void
    {
        $this->validate([
            'precio_unitario' => 'required|numeric|min:0',
        ]);

        Configuracion::setPrecioUnitario($this->precio_unitario);

        session()->flash('ok', 'Precio unitario actualizado a Q'.number_format($this->precio_unitario, 2).'.');
    }

    public function guardarCorreos(): void
    {
        $this->validate(['correos_reportes' => 'nullable|string|max:255']);

        $correos = array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $this->correos_reportes))));
        foreach ($correos as $c) {
            if (Validator::make(['c' => $c], ['c' => 'email'])->fails()) {
                $this->addError('correos_reportes', "Correo inválido: {$c}");

                return;
            }
        }

        Configuracion::setCorreosReportes($correos);
        $this->correos_reportes = implode(', ', $correos);

        session()->flash('ok', $correos ? 'Los reportes se enviarán a: '.$this->correos_reportes.'.' : 'Reportes desactivados (sin destinatarios).');
    }

    public function render()
    {
        return view('livewire.configuracion-precio');
    }
}
