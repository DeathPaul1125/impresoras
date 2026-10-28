@php
    $n = fn ($v) => $v === null ? '—' : number_format((int) $v);
    $input = 'py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Impresoras</h1>
            <p class="text-sm text-gray-500">Equipos registrados. Los contadores se guardan solos cada día a las 6 pm; aquí puedes leerlos en vivo (no se guardan).</p>
        </div>
        <x-secondary-button wire:click="leerTodas" wire:loading.attr="disabled" wire:target="leerTodas" type="button">
            <i class="fas fa-rotate me-2" wire:loading.class="fa-spin" wire:target="leerTodas"></i> Leer todas
        </x-secondary-button>
    </div>

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-teal-50 border border-teal-200 p-3 text-teal-800 text-sm">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Formulario alta / edición --}}
    <form wire:submit="guardar" class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 mb-8">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $editando ? 'Editar impresora' : 'Nueva impresora' }}</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación *</label>
                <input type="text" wire:model="ubicacion" placeholder="DAB 7o." class="{{ $input }}">
                @error('ubicacion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre host *</label>
                <input type="text" wire:model="nombre_host" placeholder="KMC58AC2" class="{{ $input }}">
                @error('nombre_host') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección IP *</label>
                <div class="flex gap-2">
                    <input type="text" wire:model="direccion_ip" placeholder="192.100.10.110" class="{{ $input }}">
                    <button type="button" wire:click="detectar" wire:loading.attr="disabled" wire:target="detectar"
                            title="Detectar modelo y serie por SNMP"
                            class="shrink-0 px-3 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                        <i class="fas fa-magnifying-glass" wire:loading.remove wire:target="detectar"></i>
                        <i class="fas fa-spinner fa-spin" wire:loading wire:target="detectar"></i>
                    </button>
                </div>
                @error('direccion_ip') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                <input type="text" wire:model="modelo" list="modelos" class="{{ $input }}">
                <datalist id="modelos">
                    @foreach ($modelos as $m) <option value="{{ $m }}"> @endforeach
                </datalist>
                @error('modelo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serie</label>
                <input type="text" wire:model="serie" class="{{ $input }}">
                @error('serie') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-5 flex gap-2">
            <x-primary-button>{{ $editando ? 'Guardar cambios' : 'Agregar impresora' }}</x-primary-button>
            @if ($editando)
                <x-secondary-button wire:click="cancelar" type="button">Cancelar</x-secondary-button>
            @endif
        </div>
    </form>

    {{-- Listado --}}
    <x-tabla-seccion titulo="Impresoras registradas ({{ $impresoras->count() }})">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Ubicación</th>
                    <th class="px-4 py-2 text-left">Host</th>
                    <th class="px-4 py-2 text-left">IP</th>
                    <th class="px-4 py-2 text-left">Modelo</th>
                    <th class="px-4 py-2 text-left">Serie</th>
                    <th class="px-4 py-2 text-right">Lecturas guardadas</th>
                    <th class="px-4 py-2 text-right">Total</th>
                    <th class="px-4 py-2 text-right">Impresiones</th>
                    <th class="px-4 py-2 text-right">Copias</th>
                    <th class="px-4 py-2 text-right">Escaneos</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($impresoras as $d)
                    @php $c = $contadores[$d->id] ?? null; @endphp
                    <tr wire:key="imp-{{ $d->id }}" class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors {{ $editando === $d->id ? 'bg-blue-50' : '' }}">
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $d->ubicacion ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $d->nombre_host }}</td>
                        <td class="px-4 py-2">
                            @if ($d->direccion_ip)
                                <a href="http://{{ $d->direccion_ip }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ $d->direccion_ip }}</a>
                            @else — @endif
                        </td>
                        <td class="px-4 py-2">{{ $d->modelo?->nombre ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $d->serie ?? '—' }}</td>
                        <td class="px-4 py-2 text-right" title="Última: {{ $d->contadores_max_leido_en ?? '—' }}">{{ number_format($d->contadores_count) }}</td>
                        @if ($c === false)
                            <td colspan="4" class="px-4 py-2 text-right text-red-600 text-xs">Sin respuesta SNMP</td>
                        @else
                            <td class="px-4 py-2 text-right font-semibold">{{ $n($c['total'] ?? null) }}</td>
                            <td class="px-4 py-2 text-right">{{ $n($c['impresiones'] ?? null) }}</td>
                            <td class="px-4 py-2 text-right">{{ $n($c['copias'] ?? null) }}</td>
                            <td class="px-4 py-2 text-right">{{ $n($c['escaneos'] ?? null) }}</td>
                        @endif
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button type="button" wire:click="leer({{ $d->id }})" title="Leer contadores" class="p-1.5 text-gray-500 hover:text-blue-600">
                                <i class="fas fa-rotate" wire:loading.class="fa-spin" wire:target="leer({{ $d->id }})"></i>
                            </button>
                            <button type="button" wire:click="editar({{ $d->id }})" title="Editar" class="p-1.5 text-gray-500 hover:text-blue-600">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" wire:click="eliminar({{ $d->id }})" wire:confirm="¿Eliminar la impresora {{ $d->nombre_host }}?"
                                    title="Eliminar" class="p-1.5 text-gray-500 hover:text-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="px-4 py-6 text-center text-gray-500">No hay impresoras registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
