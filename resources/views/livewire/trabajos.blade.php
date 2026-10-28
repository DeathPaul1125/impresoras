@php
    $ctrl = 'block w-full py-2 px-3 rounded-lg border-0 bg-papel ring-1 ring-inset ring-linea text-sm text-tinta focus:ring-2 focus:ring-marino-600';
    $icono = ['impresion' => 'fa-print', 'copia' => 'fa-copy', 'escaneo' => 'fa-file-import', 'envio' => 'fa-paper-plane', 'otro' => 'fa-file'];
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-tinta">Registro de trabajos</h1>
        <p class="text-sm text-tinta-3">Cada impresión, copia y escaneo leído de las impresoras cada minuto: quién, dónde, cuántas páginas y qué documento. Solo visible para administradores.</p>
    </div>

    <div class="bg-white rounded-xl shadow-hoja p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-tinta-2 mb-1">Desde</label>
            <input type="date" wire:model.live="desde" class="{{ $ctrl }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-tinta-2 mb-1">Hasta</label>
            <input type="date" wire:model.live="hasta" class="{{ $ctrl }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-tinta-2 mb-1">Impresora</label>
            <select wire:model.live="dispositivo" class="{{ $ctrl }}">
                <option value="">Todas</option>
                @foreach ($dispositivos as $d)
                    <option value="{{ $d->id }}">{{ $d->ubicacion ?? $d->nombre_host }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-tinta-2 mb-1">Tipo</label>
            <select wire:model.live="tipo" class="{{ $ctrl }}">
                <option value="">Todos</option>
                @foreach (\App\Models\Trabajo::TIPOS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-tinta-2 mb-1">Buscar</label>
            <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Usuario, cuenta o documento" class="{{ $ctrl }}">
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach (['impresion', 'copia', 'escaneo', 'envio'] as $t)
            <div class="bg-white rounded-xl shadow-hoja px-4 py-3">
                <div class="text-xs text-tinta-3"><i class="fas {{ $icono[$t] }} me-1"></i>{{ \App\Models\Trabajo::TIPOS[$t] }}</div>
                <div class="mt-1 text-xl font-semibold text-tinta cifra">{{ number_format($resumen[$t]->paginas ?? 0) }} <span class="text-xs font-normal text-tinta-3">páginas</span></div>
                <div class="text-xs text-tinta-3 cifra">{{ number_format($resumen[$t]->trabajos ?? 0) }} trabajos</div>
            </div>
        @endforeach
    </div>

    <x-tabla-seccion titulo="Trabajos" :descripcion="number_format($trabajos->total()).' trabajos en el filtro'" :export="route('export.excel', ['tipo' => 'trabajos'] + $this->filtros())">
        <table class="min-w-full divide-y divide-linea text-sm">
            <thead class="text-xs">
                <tr>
                    <th class="px-5 py-2.5 text-left">Fecha y hora</th>
                    <th class="px-3 py-2.5 text-left">Impresora</th>
                    <th class="px-3 py-2.5 text-left">Tipo</th>
                    <th class="px-3 py-2.5 text-left">Usuario / cuenta</th>
                    <th class="px-3 py-2.5 text-left">Documento</th>
                    <th class="px-3 py-2.5 text-right">Páginas</th>
                    <th class="px-5 py-2.5 text-right">Juegos</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($trabajos as $t)
                    <tr wire:key="t-{{ $t->id }}" class="hover:bg-papel/70 {{ $t->estado === 'cancelado' ? 'opacity-60' : '' }}">
                        <td class="px-5 py-2 whitespace-nowrap cifra">{{ $t->inicio?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap"><x-nivel :nivel="$t->dispositivo?->nivel" class="me-1" />{{ $t->dispositivo?->ubicacion }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <i class="fas {{ $icono[$t->tipo] ?? 'fa-file' }} text-tinta-3 me-1"></i>{{ \App\Models\Trabajo::TIPOS[$t->tipo] ?? $t->tipo }}
                            @if ($t->estado === 'cancelado') <span class="ms-1 text-[11px] text-red-700">cancelado</span> @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="text-tinta">{{ $t->cuenta ?? '—' }}</div>
                            @if ($t->usuario) <div class="text-[11px] text-tinta-3">{{ $t->usuario }}</div> @endif
                        </td>
                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $t->documento }}">{{ $t->documento ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-tinta cifra">{{ number_format($t->paginas) }}</td>
                        <td class="px-5 py-2 text-right cifra">{{ $t->juegos }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-tinta-3">No hay trabajos con estos filtros. Se guardan desde que se activó la lectura (cada minuto).</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($trabajos->hasPages())
            <div class="px-5 py-3 border-t border-linea">{{ $trabajos->links() }}</div>
        @endif
    </x-tabla-seccion>
</div>
