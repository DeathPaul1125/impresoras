@php
    // Parámetros para las exportaciones: filtros vigentes + orden.
    $exportParams = array_merge(array_filter($this->filtros(), fn ($v) => $v !== ''), [
        'sort_field' => $sort_field,
        'sort_order' => $sort_order,
    ]);
    $q = fn ($v) => 'Q'.number_format((float) $v, 2);
    $n = fn ($v) => number_format((int) $v);
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Panel de Consumo</h1>
            <p class="text-sm text-gray-500">Servicios de impresión y fotocopiado · SENABED</p>
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
            ['Escaneos · sin costo', $n($totales['escaneados']), 'fa-expand', 'gray'],
            ['Precio Unitario', $q($totales['precio_unitario']), 'fa-tag', 'purple'],
            ['Costo Total Aprox.', $q($totales['costo_total']), 'fa-coins', 'red'],
            ['Prom. por Usuario', $n($totales['promedio_usuario']), 'fa-user', 'orange'],
        ] as [$titulo, $valor, $icon, $color])
            <x-kpi :titulo="$titulo" :valor="$valor" :icon="$icon" :color="$color" />
        @endforeach
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Top 10 Usuarios (costo)</h2>
            @include('livewire.partials.grafica', ['key' => 'usuarios', 'tipo' => 'bar', 'label' => 'Costo Q', 'labels' => $graficas['usuarios']['labels'], 'datos' => $graficas['usuarios']['data'], 'firma' => $firma])
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Unidades (costo)</h2>
            @include('livewire.partials.grafica', ['key' => 'unidades', 'tipo' => 'doughnut', 'label' => 'Costo Q', 'labels' => $graficas['unidades']['labels'], 'datos' => $graficas['unidades']['data'], 'firma' => $firma])
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">Tendencia Mensual (costo)</h2>
            @include('livewire.partials.grafica', ['key' => 'tendencias', 'tipo' => 'line', 'label' => 'Costo Q', 'labels' => $graficas['tendencias']['labels'], 'datos' => $graficas['tendencias']['data'], 'firma' => $firma])
        </div>
    </div>

    {{-- Usuarios por Consumo (ordenable) --}}
    <x-tabla-seccion titulo="Usuarios por Consumo" :export="route('export.excel', array_merge(['tipo' => 'usuarios'], $exportParams))"
                     :pdf="route('export.pdf', array_merge(['tipo' => 'usuarios'], $exportParams))">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">ID Cuenta</th>
                    <th class="px-4 py-2 text-left">Nombre Cuenta</th>
                    <th class="px-4 py-2 text-left">Unidad</th>
                    <x-sort-th campo="impresiones" :sort_field="$sort_field" :sort_order="$sort_order">Impresiones</x-sort-th>
                    <x-sort-th campo="copias" :sort_field="$sort_field" :sort_order="$sort_order">Copias</x-sort-th>
                    <x-sort-th campo="escaneados" :sort_field="$sort_field" :sort_order="$sort_order">Escaneados</x-sort-th>
                    <x-sort-th campo="costo_total" :sort_field="$sort_field" :sort_order="$sort_order">Costo Total</x-sort-th>
                    <th class="px-4 py-2 text-left">Último Corte</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($usuarios as $u)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2">{{ $u->id_cuenta }}</td>
                        <td class="px-4 py-2">{{ $u->nombre_cuenta }}</td>
                        <td class="px-4 py-2">{{ $u->unidad }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->impresiones) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->copias) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->escaneados) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($u->costo_total) }}</td>
                        <td class="px-4 py-2">{{ $u->ultimo_corte ? \Illuminate\Support\Carbon::parse($u->ultimo_corte)->format('d/m/Y H:i') : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-6 text-center text-gray-400">Sin datos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-tabla-seccion>

    {{-- Unidades por Consumo --}}
    <x-tabla-seccion titulo="Unidades por Consumo" :export="route('export.excel', array_merge(['tipo' => 'unidades'], $exportParams))"
                     :pdf="route('export.pdf', array_merge(['tipo' => 'unidades'], $exportParams))">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Nombre Unidad</th>
                    <th class="px-4 py-2 text-right">Impresiones</th>
                    <th class="px-4 py-2 text-right">Copias</th>
                    <th class="px-4 py-2 text-right">Escaneados</th>
                    <th class="px-4 py-2 text-right">Costo Total</th>
                    <th class="px-4 py-2 text-right">Porcentaje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($unidades as $u)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2">{{ $u->unidad }} — {{ $u->unidad_nombre }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->impresiones) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->copias) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($u->escaneados) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($u->costo_total) }}</td>
                        <td class="px-4 py-2 text-right">{{ $u->porcentaje }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-tabla-seccion>

    {{-- Dispositivos por Consumo --}}
    <x-tabla-seccion titulo="Dispositivos por Consumo" :export="route('export.excel', array_merge(['tipo' => 'dispositivos'], $exportParams))"
                     :pdf="route('export.pdf', array_merge(['tipo' => 'dispositivos'], $exportParams))">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Nombre Host</th>
                    <th class="px-4 py-2 text-left">Dirección IP</th>
                    <th class="px-4 py-2 text-left">Modelo</th>
                    <th class="px-4 py-2 text-right">Impresiones</th>
                    <th class="px-4 py-2 text-right">Copias</th>
                    <th class="px-4 py-2 text-right">Escaneados</th>
                    <th class="px-4 py-2 text-right">Costo Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($dispositivos as $d)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2">{{ $d->nombre_host }}</td>
                        <td class="px-4 py-2">{{ $d->direccion_ip }}</td>
                        <td class="px-4 py-2">{{ $d->modelo }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($d->impresiones) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($d->copias) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($d->escaneados) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($d->costo_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-tabla-seccion>

    {{-- Tendencias de Consumo Mensual --}}
    <x-tabla-seccion titulo="Tendencias de Consumo Mensual" :export="route('export.excel', array_merge(['tipo' => 'tendencias'], $exportParams))"
                     :pdf="route('export.pdf', array_merge(['tipo' => 'tendencias'], $exportParams))">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Período</th>
                    <th class="px-4 py-2 text-right">Usuarios Activos</th>
                    <th class="px-4 py-2 text-right">Promedio por Usuario</th>
                    <th class="px-4 py-2 text-right">Costo Total</th>
                    <th class="px-4 py-2 text-right">Variación %</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($tendencias as $t)
                    <tr class="odd:bg-gray-50/40 hover:bg-blue-50/60 transition-colors">
                        <td class="px-4 py-2">{{ $t->periodo }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($t->usuarios) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($t->promedio_usuario) }}</td>
                        <td class="px-4 py-2 text-right font-semibold">{{ $q($t->costo_total) }}</td>
                        <td class="px-4 py-2 text-right {{ $t->variacion === null ? 'text-gray-400' : ($t->variacion >= 0 ? 'text-red-600' : 'text-green-600') }}">
                            {{ $t->variacion === null ? '—' : ($t->variacion > 0 ? '+' : '').$t->variacion.'%' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-tabla-seccion>
</div>
