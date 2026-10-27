{{-- Filtros compartidos por el Dashboard General y el Tablero. Requiere $opciones. --}}
@php
    $ctrl = 'py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<form wire:submit="filtrar" class="bg-white border border-gray-200 rounded-xl shadow-2xs p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
            <input type="date" wire:model="fecha_inicio" class="{{ $ctrl }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
            <input type="date" wire:model="fecha_fin" class="{{ $ctrl }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad Organizativa</label>
            <select wire:model="unidad" class="{{ $ctrl }}">
                <option value="">Todas</option>
                @foreach ($opciones['unidades'] as $u)
                    <option value="{{ $u->codigo }}">{{ $u->codigo }} - {{ $u->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dispositivo</label>
            <select wire:model="dispositivo" class="{{ $ctrl }}">
                <option value="">Todos</option>
                @foreach ($opciones['dispositivos'] as $d)
                    <option value="{{ $d->id }}">{{ $d->nombre_host }} ({{ $d->direccion_ip }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
            <select wire:model="modelo" class="{{ $ctrl }}">
                <option value="">Todos</option>
                @foreach ($opciones['modelos'] as $m)
                    <option value="{{ $m->id }}">{{ $m->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Cuenta</label>
            <input type="text" wire:model="nombre_cuenta" placeholder="Buscar..." class="{{ $ctrl }}">
        </div>
    </div>
    <div class="mt-4 flex gap-2">
        <x-primary-button type="submit">
            <i class="fas fa-filter"></i> Filtrar
        </x-primary-button>
        <button type="button" wire:click="limpiarFiltros"
                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none">
            Limpiar
        </button>
    </div>
</form>
