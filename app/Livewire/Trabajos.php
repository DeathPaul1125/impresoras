<?php

namespace App\Livewire;

use App\Models\Dispositivo;
use App\Models\Trabajo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Registro de trabajos leído de las impresoras (solo administradores: incluye nombres de documentos). */
#[Layout('layouts.app')]
#[Title('Registro de trabajos')]
class Trabajos extends Component
{
    use WithPagination;

    #[Url]
    public string $desde = '';

    #[Url]
    public string $hasta = '';

    #[Url]
    public string $dispositivo = '';

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $buscar = '';

    public function mount(): void
    {
        $this->desde = $this->desde ?: today()->subDays(6)->toDateString();
        $this->hasta = $this->hasta ?: today()->toDateString();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function filtros(): array
    {
        return $this->only(['desde', 'hasta', 'dispositivo', 'tipo', 'buscar']);
    }

    public function render()
    {
        $base = Trabajo::filtrar($this->filtros());

        return view('livewire.trabajos', [
            'trabajos' => (clone $base)->with('dispositivo')->latest('inicio')->paginate(50),
            'resumen' => (clone $base)->selectRaw('tipo, count(*) trabajos, sum(paginas) paginas')->groupBy('tipo')->get()->keyBy('tipo'),
            'dispositivos' => Dispositivo::orderBy('nivel')->get(),
        ]);
    }
}
