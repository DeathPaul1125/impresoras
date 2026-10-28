@php
    $n = fn ($v) => $v === null ? '—' : number_format((int) $v);
    $ctrl = 'block w-full py-2 px-3 rounded-lg border-0 bg-papel ring-1 ring-inset ring-linea text-sm text-tinta placeholder:text-tinta-4 focus:ring-2 focus:ring-marino-600';
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-tinta">Impresoras</h1>
            <p class="text-sm text-tinta-3">Los contadores se guardan solos cada día a las 6 pm. Aquí puedes leerlos en vivo (esa lectura no se guarda).</p>
        </div>
        <x-secondary-button wire:click="leerTodas" wire:loading.attr="disabled" wire:target="leerTodas">
            <i class="fas fa-rotate" wire:loading.class="fa-spin" wire:target="leerTodas"></i> Leer todas en vivo
        </x-secondary-button>
    </div>

    @if (session('ok'))
        <div class="rounded-lg bg-saldo-50 ring-1 ring-inset ring-saldo/20 px-4 py-3 text-sm text-saldo flex gap-2"><i class="fas fa-circle-check mt-0.5"></i>{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg bg-red-50 ring-1 ring-inset ring-red-200 px-4 py-3 text-sm text-red-700 flex gap-2"><i class="fas fa-circle-exclamation mt-0.5"></i>{{ session('error') }}</div>
    @endif

    {{-- Alta / edición --}}
    <x-panel :titulo="$editando ? 'Editar impresora' : 'Agregar impresora'" descripcion="Con la IP, el botón de búsqueda detecta modelo y serie por SNMP." class="{{ $editando ? 'ring-2 ring-marino-600' : '' }}">
        <form wire:submit="guardar">
            <div class="grid grid-cols-2 md:grid-cols-12 gap-4">
                <x-campo label="Ubicación *" nombre="ubicacion" class="col-span-2 md:col-span-2">
                    <input type="text" wire:model="ubicacion" placeholder="DAB 7o." class="{{ $ctrl }}">
                </x-campo>
                <x-campo label="Nivel" nombre="nivel" class="md:col-span-1">
                    <input type="text" wire:model="nivel" placeholder="7.1" class="{{ $ctrl }}">
                </x-campo>
                <x-campo label="Nombre host *" nombre="nombre_host" class="md:col-span-2">
                    <input type="text" wire:model="nombre_host" placeholder="KMC58AC2" class="{{ $ctrl }}">
                </x-campo>
                <x-campo label="Dirección IP *" nombre="direccion_ip" class="col-span-2 md:col-span-3">
                    <div class="flex gap-1.5">
                        <input type="text" wire:model="direccion_ip" placeholder="192.100.10.110" class="{{ $ctrl }} cifra">
                        <button type="button" wire:click="detectar" wire:loading.attr="disabled" wire:target="detectar" title="Detectar modelo y serie por SNMP"
                                class="shrink-0 size-9 inline-flex items-center justify-center rounded-lg bg-white shadow-hoja text-tinta-2 hover:bg-papel active:scale-[0.97]">
                            <i class="fas fa-magnifying-glass" wire:loading.remove wire:target="detectar"></i>
                            <i class="fas fa-spinner fa-spin" wire:loading wire:target="detectar"></i>
                        </button>
                    </div>
                </x-campo>
                <x-campo label="Modelo" nombre="modelo" class="md:col-span-2">
                    <input type="text" wire:model="modelo" list="modelos" class="{{ $ctrl }}">
                    <datalist id="modelos">@foreach ($modelos as $m) <option value="{{ $m }}"> @endforeach</datalist>
                </x-campo>
                <x-campo label="Serie" nombre="serie" class="md:col-span-2">
                    <input type="text" wire:model="serie" class="{{ $ctrl }} cifra">
                </x-campo>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                @if ($editando)
                    <x-secondary-button wire:click="cancelar">Cancelar</x-secondary-button>
                @endif
                <x-primary-button>{{ $editando ? 'Guardar cambios' : 'Agregar impresora' }}</x-primary-button>
            </div>
        </form>
    </x-panel>

    {{-- Listado --}}
    <x-tabla-seccion titulo="Impresoras registradas ({{ $impresoras->count() }})" descripcion="Contadores en vivo: pulsa ↻ en una fila o «Leer todas».">
        <table class="min-w-full divide-y divide-linea text-sm">
            <thead>
                <tr>
                    <th class="px-5 py-2.5 text-left">Equipo</th>
                    <th class="px-3 py-2.5 text-left">Red</th>
                    <th class="px-3 py-2.5 text-left">Modelo / serie</th>
                    <th class="px-3 py-2.5 text-right">Lecturas</th>
                    <th class="px-3 py-2.5 text-right">Impresiones</th>
                    <th class="px-3 py-2.5 text-right">Copias</th>
                    <th class="px-3 py-2.5 text-right">Escaneos</th>
                    <th class="px-3 py-2.5 text-right">Total</th>
                    <th class="px-5 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($impresoras as $d)
                    @php $c = $contadores[$d->id] ?? null; @endphp
                    <tr wire:key="imp-{{ $d->id }}" class="hover:bg-papel/70 {{ $editando === $d->id ? 'bg-marino-50' : '' }}">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <x-nivel :nivel="$d->nivel" />
                                <span class="font-semibold text-tinta">{{ $d->ubicacion ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="text-tinta">{{ $d->nombre_host }}</div>
                            @if ($d->direccion_ip)
                                <a href="http://{{ $d->direccion_ip }}" target="_blank" rel="noopener" class="text-xs text-marino-600 hover:underline cifra">{{ $d->direccion_ip }}</a>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            <div class="text-tinta-2">{{ $d->modelo?->nombre ?? '—' }}</div>
                            <div class="text-xs text-tinta-3 cifra">{{ $d->serie ?? 'sin serie' }}</div>
                        </td>
                        <td class="px-3 py-3 text-right" title="Última: {{ $d->contadores_max_leido_en ?? '—' }}">
                            <div class="text-tinta">{{ number_format($d->contadores_count) }}</div>
                            @if ($d->contadores_max_leido_en)
                                <div class="text-[11px] text-tinta-3">{{ \Illuminate\Support\Carbon::parse($d->contadores_max_leido_en)->diffForHumans() }}</div>
                            @endif
                        </td>
                        @if ($c === false)
                            <td colspan="4" class="px-3 py-3 text-right"><span class="inline-flex items-center gap-1.5 text-xs text-red-700"><i class="fas fa-plug-circle-xmark"></i> Sin respuesta (¿apagada?)</span></td>
                        @else
                            <td class="px-3 py-3 text-right">{{ $n($c['impresiones'] ?? null) }}</td>
                            <td class="px-3 py-3 text-right">{{ $n($c['copias'] ?? null) }}</td>
                            <td class="px-3 py-3 text-right">{{ $n($c['escaneos'] ?? null) }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-tinta">{{ $n($c['total'] ?? null) }}</td>
                        @endif
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="leer({{ $d->id }})" title="Leer en vivo" class="size-8 inline-flex items-center justify-center rounded-md text-tinta-3 hover:bg-papel hover:text-marino-600">
                                <i class="fas fa-rotate" wire:loading.class="fa-spin" wire:target="leer({{ $d->id }})"></i>
                            </button>
                            <button type="button" wire:click="editar({{ $d->id }})" title="Editar" class="size-8 inline-flex items-center justify-center rounded-md text-tinta-3 hover:bg-papel hover:text-marino-600">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" wire:click="eliminar({{ $d->id }})" wire:confirm="¿Eliminar la impresora {{ $d->nombre_host }}?" title="Eliminar"
                                    class="size-8 inline-flex items-center justify-center rounded-md text-tinta-3 hover:bg-red-50 hover:text-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-10 text-center text-tinta-3">Todavía no hay impresoras. Agrega la primera con el formulario de arriba.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
