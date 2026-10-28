<?php

namespace App\Livewire;

use App\Models\Corte;
use App\Models\Dispositivo;
use App\Support\Contrato;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cortes del contrato')]
class Cortes extends Component
{
    /** Corte en edición (Y-m-d H:i:s) y sus contadores [dispositivo_id => contador]. */
    public ?string $editando = null;

    public string $fecha = '';

    public array $contadores = [];

    public function registrarAhora(): void
    {
        $r = Contrato::registrarCorte(intentos: 1);
        $fallas = array_keys(array_filter($r, fn ($v) => $v === null));
        session()->flash($fallas ? 'error' : 'ok', 'Corte registrado.'.($fallas ? ' Sin respuesta de: '.implode(', ', $fallas).'; ingrese sus contadores a mano.' : ''));
    }

    public function nuevo(): void
    {
        $this->editando = 'nuevo';
        $this->fecha = today()->format('Y-m-d\TH:i');
        $this->contadores = [];
        $this->resetErrorBag();
    }

    public function editar(string $fecha): void
    {
        $this->editando = $fecha;
        $this->fecha = Carbon::parse($fecha)->format('Y-m-d\TH:i');
        $this->contadores = Corte::where('fecha', $fecha)->pluck('contador', 'dispositivo_id')->map(fn ($v) => (string) $v)->all();
        $this->resetErrorBag();
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'fecha', 'contadores');
    }

    public function guardar(): void
    {
        $this->validate([
            'fecha' => 'required|date',
            'contadores.*' => 'nullable|integer|min:0',
        ], [], ['contadores.*' => 'contador']);

        $fecha = Carbon::parse($this->fecha)->startOfMinute();
        if ($this->editando !== 'nuevo') {
            // Si cambió la fecha, se mueve el corte completo.
            Corte::where('fecha', $this->editando)->update(['fecha' => $fecha]);
        }

        foreach (Dispositivo::all() as $d) {
            $valor = $this->contadores[$d->id] ?? '';
            $corte = Corte::where('fecha', $fecha)->where('dispositivo_id', $d->id)->first();
            if ($valor === '' || $valor === null) {
                $corte?->delete();

                continue;
            }
            if (! $corte || $corte->contador !== (int) $valor) {
                Corte::updateOrCreate(
                    ['fecha' => $fecha, 'dispositivo_id' => $d->id],
                    ['contador' => (int) $valor, 'serie' => $corte?->serie ?? $d->serie, 'origen' => 'manual'],
                );
            }
        }

        session()->flash('ok', 'Corte del '.$fecha->format('d/m/Y H:i').' guardado.');
        $this->cancelar();
    }

    public function eliminar(string $fecha): void
    {
        Corte::where('fecha', $fecha)->delete();
        session()->flash('ok', 'Corte del '.Carbon::parse($fecha)->format('d/m/Y').' eliminado.');
    }

    public function render()
    {
        $contrato = new Contrato;

        return view('livewire.cortes', [
            'c' => $contrato,
            'periodos' => $contrato->periodos()->keyBy(fn ($p) => $p->fecha->format('Y-m-d H:i:s')),
        ]);
    }
}
