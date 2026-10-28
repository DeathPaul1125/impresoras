@php
    $n = fn ($v) => $v === null ? '—' : number_format((int) $v);
    $input = 'block w-full py-1.5 px-2 rounded-lg border-0 bg-papel ring-1 ring-inset ring-linea text-sm text-right text-tinta cifra focus:ring-2 focus:ring-marino-600';
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-tinta">Cortes del contrato</h1>
            <p class="text-sm text-tinta-3">Contador B/N (impresiones + copias) de cada equipo en cada corte. Se registra solo el día {{ $c->cfg['dia_corte'] }} a las 00:00; aquí se corrige con el dato del proveedor.</p>
        </div>
        <div class="flex gap-2">
            <x-secondary-button wire:click="nuevo" type="button"><i class="fas fa-plus me-2"></i> Corte manual</x-secondary-button>
            <x-primary-button wire:click="registrarAhora" wire:loading.attr="disabled" wire:target="registrarAhora" wire:confirm="¿Registrar un corte ahora con los contadores actuales de las impresoras?" type="button">
                <i class="fas fa-satellite-dish me-2" wire:loading.class="fa-beat" wire:target="registrarAhora"></i> Registrar corte ahora
            </x-primary-button>
        </div>
    </div>

    @if (session('ok'))
        <div class="mb-4 rounded-lg bg-saldo-50 ring-1 ring-inset ring-saldo/20 px-4 py-3 text-saldo text-sm">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    @if ($editando)
        <form wire:submit="guardar" class="bg-white rounded-xl shadow-hoja ring-2 ring-marino-600 p-6 mb-8">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $editando === 'nuevo' ? 'Nuevo corte' : 'Editar corte' }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Fecha y hora del corte</label>
                    <input type="datetime-local" wire:model="fecha" class="{{ $input }} text-left">
                    @error('fecha') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @foreach ($c->dispositivos as $d)
                    <div>
                        <label class="block text-xs text-tinta-2 mb-1 truncate" title="{{ $d->serie }}"><x-nivel :nivel="$d->nivel" class="me-1" />{{ $d->ubicacion }}</label>
                        <input type="number" min="0" wire:model="contadores.{{ $d->id }}" placeholder="{{ $c->actual[$d->id]['contador'] ?? '' }}" class="{{ $input }}">
                        @error('contadores.'.$d->id) <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-3">Vacío = sin dato para ese equipo. En gris se sugiere el contador de la última lectura.</p>
            <div class="mt-4 flex gap-2">
                <x-primary-button>Guardar corte</x-primary-button>
                <x-secondary-button wire:click="cancelar" type="button">Cancelar</x-secondary-button>
            </div>
        </form>
    @endif

    <x-tabla-seccion titulo="Contadores por corte" :export="route('export.excel', ['tipo' => 'control'])" :pdf="route('export.pdf', ['tipo' => 'control'])">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Corte</th>
                    @foreach ($c->dispositivos as $d)
                        <th class="px-3 py-2 text-right whitespace-nowrap"><x-nivel :nivel="$d->nivel" class="me-1" />{{ $d->ubicacion }}</th>
                    @endforeach
                    <th class="px-3 py-2 text-right">Producido</th>
                    <th class="px-3 py-2 text-right">Saldo bolsón</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($c->fechas->reverse() as $f)
                    @php $k = $f->format('Y-m-d H:i:s'); $p = $periodos[$k] ?? null; @endphp
                    <tr wire:key="corte-{{ $k }}" class="odd:bg-gray-50/40 hover:bg-blue-50/60 {{ $editando === $k ? 'bg-blue-50' : '' }}">
                        <td class="px-4 py-2 whitespace-nowrap">
                            <div class="font-medium text-gray-800">{{ $p?->etiqueta ?? 'Contador inicial' }}</div>
                            <div class="text-xs text-gray-500">{{ $f->format('d/m/Y H:i') }}</div>
                        </td>
                        @foreach ($c->dispositivos as $d)
                            @php $corte = $c->contadorEnCorte($f, $d->id); $prod = $p?->producido[$d->id] ?? null; @endphp
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                @if ($corte)
                                    <div title="Serie {{ $corte->serie }} · {{ $corte->origen }}">{{ $n($corte->contador) }}
                                        @if ($corte->origen === 'manual') <i class="fas fa-pen text-[10px] text-amber-500"></i> @endif
                                    </div>
                                    @if ($p)
                                        <div class="text-xs {{ $prod === null ? 'text-red-500' : 'text-gray-500' }}">{{ $prod === null ? 'sin cálculo' : '+'.$n($prod) }}</div>
                                    @endif
                                @else
                                    <span class="text-red-500 text-xs">falta</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-2 text-right font-semibold">{{ $p ? $n($p->total) : '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $p ? $n($p->saldo) : $n($c->cfg['bolson']) }}</td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <button type="button" wire:click="editar('{{ $k }}')" title="Editar" class="p-1.5 text-gray-500 hover:text-blue-600"><i class="fas fa-pen"></i></button>
                            <button type="button" wire:click="eliminar('{{ $k }}')" wire:confirm="¿Eliminar el corte del {{ $f->format('d/m/Y') }}?" title="Eliminar" class="p-1.5 text-gray-500 hover:text-red-600"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $c->dispositivos->count() + 4 }}" class="px-3 py-6 text-center text-gray-400">No hay cortes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
