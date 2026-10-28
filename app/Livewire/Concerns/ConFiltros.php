<?php

namespace App\Livewire\Concerns;

use App\Models\Dispositivo;
use App\Models\Modelo;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * Filtros compartidos por el Dashboard General y el Tablero de Totales.
 * Se sincronizan con la URL (#[Url]) para que las exportaciones puedan
 * reutilizar el mismo query string.
 */
trait ConFiltros
{
    #[Url]
    public string $fecha_inicio = '';

    #[Url]
    public string $fecha_fin = '';

    #[Url]
    public string $dispositivo = '';

    #[Url]
    public string $modelo = '';

    public function inicializarFiltros(): void
    {
        if ($this->fecha_inicio === '') {
            $this->fecha_inicio = Carbon::today()->subDays(30)->toDateString();
        }
        if ($this->fecha_fin === '') {
            $this->fecha_fin = Carbon::today()->toDateString();
        }
    }

    /** Arreglo de filtros que consume App\Support\Consumo. */
    public function filtros(): array
    {
        return [
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'dispositivo' => $this->dispositivo,
            'modelo' => $this->modelo,
        ];
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['dispositivo', 'modelo']);
        $this->fecha_inicio = Carbon::today()->subDays(30)->toDateString();
        $this->fecha_fin = Carbon::today()->toDateString();
    }

    /** Datos para poblar los <select> de los filtros. */
    public function opcionesFiltros(): array
    {
        return [
            'dispositivos' => Dispositivo::orderBy('ubicacion')->get(['id', 'ubicacion', 'nombre_host', 'direccion_ip']),
            'modelos' => Modelo::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
