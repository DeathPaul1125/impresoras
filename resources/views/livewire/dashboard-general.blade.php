@php
    $q = fn ($v) => 'Q'.number_format((float) $v, 2);
    $n = fn ($v) => number_format((int) $v);
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard General</h1>
        <p class="text-sm text-gray-500">Resumen de consumo de impresión y fotocopiado</p>
    </div>

    @include('livewire.partials.filtros')

    {{-- Tarjetas KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
        @foreach ([
            ['Total Impresiones', $n($totales['impresiones']), 'fa-print', 'blue'],
            ['Total Copias', $n($totales['copias']), 'fa-copy', 'green'],
            ['Total Escaneos', $n($totales['escaneados']), 'fa-expand', 'gray'],
            ['Precio Unitario', $q($totales['precio_unitario']), 'fa-tag', 'purple'],
            ['Costo Total Aprox.', $q($totales['costo_total']), 'fa-coins', 'red'],
        ] as [$titulo, $valor, $icon, $color])
            <x-kpi :titulo="$titulo" :valor="$valor" :icon="$icon" :color="$color" />
        @endforeach
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Usuarios con Mayor Consumo</h2>
            @include('livewire.partials.grafica', ['key' => 'usuarios', 'tipo' => 'bar', 'label' => 'Costo Q', 'labels' => $graficas['usuarios']['labels'], 'datos' => $graficas['usuarios']['data'], 'firma' => $firma])
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Unidades con Mayor Consumo</h2>
            @include('livewire.partials.grafica', ['key' => 'unidades', 'tipo' => 'doughnut', 'label' => 'Costo Q', 'labels' => $graficas['unidades']['labels'], 'datos' => $graficas['unidades']['data'], 'firma' => $firma])
        </div>
    </div>

    {{-- Tabla top usuarios --}}
    <x-tabla-seccion titulo="Usuarios con Mayor Consumo">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">ID Cuenta</th>
                    <th class="px-4 py-2 text-left">Nombre Cuenta</th>
                    <th class="px-4 py-2 text-left">Unidad</th>
                    <th class="px-4 py-2 text-right">Impresiones</th>
                    <th class="px-4 py-2 text-right">Copias</th>
                    <th class="px-4 py-2 text-right">Escaneados</th>
                    <th class="px-4 py-2 text-right">Costo Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($topUsuarios as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $u->id_cuenta }}</td>
                        <td class="px-4 py-2">{{ $u->nombre_cuenta }}</td>
                        <td class="px-4 py-2">{{ $u->unidad }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->impresiones) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->copias) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->escaneados) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($u->costo_total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">Sin datos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
