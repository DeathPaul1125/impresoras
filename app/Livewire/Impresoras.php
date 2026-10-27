<?php

namespace App\Livewire;

use App\Models\Dispositivo;
use App\Models\Modelo;
use App\Support\Snmp;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Impresoras')]
class Impresoras extends Component
{
    /** id en edición; null = alta nueva. */
    public ?int $editando = null;

    public string $ubicacion = '';

    public string $nombre_host = '';

    public string $direccion_ip = '';

    public string $modelo = '';

    public string $serie = '';

    /** Contadores leídos en vivo por SNMP: id => array|false (false = no respondió). No se guardan. */
    public array $contadores = [];

    public function editar(int $id): void
    {
        $d = Dispositivo::with('modelo')->findOrFail($id);
        $this->editando = $d->id;
        $this->ubicacion = (string) $d->ubicacion;
        $this->nombre_host = $d->nombre_host;
        $this->direccion_ip = (string) $d->direccion_ip;
        $this->modelo = (string) $d->modelo?->nombre;
        $this->serie = (string) $d->serie;
        $this->resetErrorBag();
    }

    public function cancelar(): void
    {
        $this->reset('editando', 'ubicacion', 'nombre_host', 'direccion_ip', 'modelo', 'serie');
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'ubicacion' => 'required|string|max:255',
            'nombre_host' => ['required', 'string', 'max:255', Rule::unique('dispositivos')->ignore($this->editando)],
            'direccion_ip' => ['required', 'ip', Rule::unique('dispositivos')->ignore($this->editando)],
            'modelo' => 'nullable|string|max:255',
            'serie' => 'nullable|string|max:30',
        ]);

        $datos['modelo_id'] = trim($datos['modelo']) !== '' ? Modelo::firstOrCreate(['nombre' => trim($datos['modelo'])])->id : null;
        $datos['serie'] = $datos['serie'] ?: null;
        unset($datos['modelo']);

        $this->editando ? Dispositivo::findOrFail($this->editando)->update($datos) : Dispositivo::create($datos);

        session()->flash('ok', 'Impresora '.($this->editando ? 'actualizada' : 'creada').': '.$datos['ubicacion'].'.');
        $this->cancelar();
    }

    /** Lee modelo y serie de la IP del formulario. */
    public function detectar(): void
    {
        $this->validateOnly('direccion_ip', ['direccion_ip' => 'required|ip']);

        $d = Snmp::leer($this->direccion_ip);
        if (! $d) {
            $this->addError('direccion_ip', 'La impresora no respondió por SNMP.');

            return;
        }
        $this->modelo = (string) $d['modelo'];
        $this->serie = (string) $d['serie'];
    }

    public function leer(int $id): void
    {
        $d = Dispositivo::findOrFail($id);
        $lectura = $d->direccion_ip ? Snmp::leer($d->direccion_ip) : null;
        $this->contadores[$id] = $lectura ?: false;

        // De paso completa serie/modelo si faltaban.
        if ($lectura && (! $d->serie || ! $d->modelo_id)) {
            $d->serie ??= $lectura['serie'];
            $d->modelo_id ??= $lectura['modelo'] ? Modelo::firstOrCreate(['nombre' => $lectura['modelo']])->id : null;
            $d->save();
        }
    }

    public function leerTodas(): void
    {
        foreach (Dispositivo::pluck('id') as $id) {
            $this->leer($id);
        }
    }

    public function eliminar(int $id): void
    {
        $d = Dispositivo::withCount('lecturas')->findOrFail($id);
        if ($d->lecturas_count > 0) {
            session()->flash('error', "No se puede eliminar {$d->nombre_host}: tiene {$d->lecturas_count} lecturas registradas.");

            return;
        }
        $d->delete();
        unset($this->contadores[$id]);
        session()->flash('ok', "Impresora {$d->nombre_host} eliminada.");
    }

    public function render()
    {
        return view('livewire.impresoras', [
            'impresoras' => Dispositivo::with('modelo')->withCount('lecturas')->orderBy('ubicacion')->get(),
            'modelos' => Modelo::orderBy('nombre')->pluck('nombre'),
        ]);
    }
}
