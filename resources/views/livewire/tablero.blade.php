@php
    // Parámetros para las exportaciones: filtros vigentes + orden.
    $exportParams = array_merge(array_filter($this->filtros(), fn ($v) => $v !== ''), [
        'sort_field' => $sort_field,
        'sort_order' => $sort_order,
    ]);
    $q = fn ($v) => 'Q'.number_format((float) $v, 2);
    $n = fn ($v) => number_format((int) $v);
    $excel = fn ($tipo) => route('export.excel', array_merge(['tipo' => $tipo], $exportParams));
    $pdf = fn ($tipo) => route('export.pdf', array_merge(['tipo' => $tipo], $exportParams));
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Panel de Consumo</h1>
            <p class="text-sm text-gray-500">Servicios de impresión y fotocopiado · SENABED · lecturas diarias de las 6 pm</p>
        </div>
        <span class="inline-flex items-center gap-2 py-1.5 px-3 rounded-full bg-white border border-gray-200 text-xs text-gray-600 shadow-sm">
            <i class="fas fa-calendar-day text-gray-400"></i>
            {{ \Illuminate\Support\Carbon::parse($fecha_inicio)->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($fecha_fin)->format('d/m/Y') }}
        </span>
    </div>

    @include('livewire.partials.filtros')

    {{-- Tarjetas KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        @foreach ([
            ['Total Impresiones', $n($totales['impresiones']), 'fa-print', 'blue'],
            ['Total Copias', $n($totales['copias']), 'fa-copy', 'green'],
            ['Escaneos · sin costo', $n($totales['escaneos']), 'fa-expand', 'gray'],
            ['Precio Unitario', $q($totales['precio_unitario']), 'fa-tag', 'purple'],
            ['Costo Total Aprox.', $q($totales['costo_total']), 'fa-coins', 'red'],
            ['Promedio Diario', $q($totales['promedio_diario']), 'fa-calendar-day', 'orange'],
        ] as [$titulo, $valor, $icon, $color])
            <x-kpi :titulo="$titulo" :valor="$valor" :icon="$icon" :color="$color" />
        @endforeach
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Páginas por Día e Impresora</h2>
            @include('livewire.partials.grafica', ['key' => 'dias', 'tipo' => 'bar', 'label' => 'Páginas', 'labels' => $graficas['dias']['labels'], 'series' => $graficas['dias']['series'], 'moneda' => false, 'firma' => $firma])
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Tendencia Mensual (costo)</h2>
            @include('livewire.partials.grafica', ['key' => 'tendencias', 'tipo' => 'line', 'label' => 'Costo Q', 'labels' => $graficas['tendencias']['labels'], 'datos' => $graficas['tendencias']['data'], 'firma' => $firma])
        </div>
    </div>

    {{-- Consumo por impresora (ordenable) --}}
    <x-tabla-seccion titulo="Consumo por Impresora" :export="$excel('dispositivos')" :pdf="$pdf('dispositivos')">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left cursor-pointer select-none hover:text-blue-600 {{ $sort_field === 'ubicacion' ? 'text-blue-600' : '' }}" wire:click="ordenar('ubicacion')">Ubicación</th>
                    <th class="px-4 py-2 text-left">Host</th>
                    <th class="px-4 py-2 text-left">IP</th>
                    <x-sort-th campo="impresiones" :sort_field="$sort_field" :sort_order="$sort_order">Impresiones</x-sort-th>
                    <x-sort-th campo="copias" :sort_field="$sort_field" :sort_order="$sort_order">Copias</x-sort-th>
                    <x-sort-th campo="escaneos" :sort_field="$sort_field" :sort_order="$sort_order">Escaneos</x-sort-th>
                    <x-sort-th campo="total" :sort_field="$sort_field" :sort_order="$sort_order">Total Páginas</x-sort-th>
                    <x-sort-th campo="costo_total" :sort_field="$sort_field" :sort_order="$sort_order">Costo Total</x-sort-th>
                    <th class="px-4 py-2 text-right">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($impresoras as $d)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $d->ubicacion ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $d->nombre_host }}</td>
                        <td class="px-4 py-2">{{ $d->direccion_ip }}</td>
                        <td class="px-6 py-2 text-right">{{ $n($d->impresiones) }}</td>
                        <td class="px-6 py-2 text-right">{{ $n($d->copias) }}</td>
                        <td class="px-6 py-2 text-right">{{ $n($d->escaneos) }}</td>
                        <td class="px-6 py-2 text-right">{{ $n($d->total) }}</td>
                        <td class="px-6 py-2 text-right font-semibold">{{ $q($d->costo_total) }}</td>
                        <td class="px-4 py-2 text-right">{{ $d->porcentaje }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-3 py-6 text-center text-gray-400">No hay impresoras registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>

    {{-- Consumo diario: páginas por día × impresora --}}
    <x-tabla-seccion titulo="Consumo Diario (páginas)" :export="$excel('diario')" :pdf="$pdf('diario')">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Fecha</th>
                    @foreach ($columnas as $c)
                        <th class="px-4 py-2 text-right">{{ $c->ubicacion ?? $c->nombre_host }}</th>
                    @endforeach
                    <th class="px-4 py-2 text-right">Total</th>
                    <th class="px-4 py-2 text-right">Costo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($porDia->reverse() as $dia)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($dia->fecha)->format('d/m/Y') }}</td>
                        @foreach ($columnas as $c)
                            @php $celda = $matriz[$dia->fecha][$c->id] ?? null; @endphp
                            <td class="px-4 py-2 text-right {{ $celda ? '' : 'text-gray-300' }}">{{ $celda ? $n($celda->total) : '—' }}</td>
                        @endforeach
                        <td class="px-4 py-2 text-right font-semibold">{{ $n($dia->total) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($dia->costo_total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $columnas->count() + 3 }}" class="px-3 py-6 text-center text-gray-400">Aún no hay consumo en este rango (se necesitan al menos dos lecturas diarias).</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>

    {{-- Tendencias de Consumo Mensual --}}
    <x-tabla-seccion titulo="Tendencias de Consumo Mensual" :export="$excel('tendencias')" :pdf="$pdf('tendencias')">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Período</th>
                    <th class="px-4 py-2 text-right">Días</th>
                    <th class="px-4 py-2 text-right">Impresiones</th>
                    <th class="px-4 py-2 text-right">Copias</th>
                    <th class="px-4 py-2 text-right">Escaneos</th>
                    <th class="px-4 py-2 text-right">Costo Total</th>
                    <th class="px-4 py-2 text-right">Variación %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tendencias as $t)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2">{{ $t->periodo }}</td>
                        <td class="px-4 py-2 text-right">{{ $t->dias }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($t->impresiones) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($t->copias) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($t->escaneos) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($t->costo_total) }}</td>
                        <td class="px-4 py-2 text-right {{ $t->variacion === null ? 'text-gray-400' : ($t->variacion >= 0 ? 'text-red-600' : 'text-green-600') }}">
                            {{ $t->variacion === null ? '—' : ($t->variacion > 0 ? '+' : '').$t->variacion.'%' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
