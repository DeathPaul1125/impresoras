@php
    $q = fn ($v) => 'Q'.number_format((float) $v, 2);
    $n = fn ($v) => number_format((int) $v);
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Dashboard General</h1>
        <p class="text-sm text-gray-500">Consumo diario por impresora (lectura de las 6 pm − lectura del día anterior)</p>
    </div>

    @include('livewire.partials.filtros')

    {{-- Tarjetas KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
        @foreach ([
            ['Total Impresiones', $n($totales['impresiones']), 'fa-print', 'blue'],
            ['Total Copias', $n($totales['copias']), 'fa-copy', 'green'],
            ['Escaneos · sin costo', $n($totales['escaneos']), 'fa-expand', 'gray'],
            ['Costo Total Aprox.', $q($totales['costo_total']), 'fa-coins', 'red'],
            ['Promedio Diario', $q($totales['promedio_diario']), 'fa-calendar-day', 'orange'],
        ] as [$titulo, $valor, $icon, $color])
            <x-kpi :titulo="$titulo" :valor="$valor" :icon="$icon" :color="$color" />
        @endforeach
    </div>

    @if ($totales['dias'] === 0)
        <div class="mb-8 rounded-lg bg-amber-50 border border-amber-200 p-3 text-amber-800 text-sm">
            Aún no hay consumo en este rango. El consumo de un día se calcula a partir de la segunda lectura diaria (6 pm) de cada impresora.
        </div>
    @endif

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Costo por Día</h2>
            @include('livewire.partials.grafica', ['key' => 'dias', 'tipo' => 'line', 'label' => 'Costo Q', 'labels' => $graficas['dias']['labels'], 'datos' => $graficas['dias']['data'], 'firma' => $firma])
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Costo por Impresora</h2>
            @include('livewire.partials.grafica', ['key' => 'impresoras', 'tipo' => 'doughnut', 'label' => 'Costo Q', 'labels' => $graficas['impresoras']['labels'], 'datos' => $graficas['impresoras']['data'], 'firma' => $firma])
        </div>
    </div>

    <x-tabla-seccion titulo="Consumo por Impresora">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Ubicación</th>
                    <th class="px-4 py-2 text-left">Host</th>
                    <th class="px-4 py-2 text-right">Impresiones</th>
                    <th class="px-4 py-2 text-right">Copias</th>
                    <th class="px-4 py-2 text-right">Escaneos</th>
                    <th class="px-4 py-2 text-right">Costo Total</th>
                    <th class="px-4 py-2 text-right">Porcentaje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($impresoras as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $d->ubicacion ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $d->nombre_host }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($d->impresiones) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($d->copias) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($d->escaneos) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($d->costo_total) }}</td>
                        <td class="px-4 py-2 text-right">{{ $d->porcentaje }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">No hay impresoras registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
