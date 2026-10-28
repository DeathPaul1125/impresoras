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

    @php
        $k = fn ($v) => $v >= 1000 ? number_format($v / 1000, $v >= 100000 ? 0 : 1).'k' : number_format($v);
        $maxBn = max(1, (int) $equipos->max('bn'));
        $topBn = $equipos->first();
        $topEsc = $equipos->sortByDesc(fn ($e) => $e->escaneos / max(1, $e->bn))->first();
    @endphp

    {{-- Infografía: Consumo de multifuncionales monocromáticas --}}
    <div class="rounded-2xl bg-gradient-to-b from-blue-50 to-sky-100 border border-blue-100 shadow-sm overflow-hidden mb-8">
        <div class="bg-[#0d2149] text-white text-center font-bold tracking-wide py-2 uppercase">Datos de consumo y operación de multifuncionales</div>
        <div class="text-center pt-5 px-4">
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#0d2149] uppercase">Consumo de multifuncionales monocromáticas</h2>
            <p class="text-gray-700 font-semibold">Análisis de copias, impresiones y escaneos · {{ \Illuminate\Support\Carbon::parse($fecha_inicio)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($fecha_fin)->format('d/m/Y') }}</p>
        </div>

        <h3 class="mt-5 text-center font-extrabold text-[#0d2149] uppercase"><i class="fas fa-arrow-right text-sky-500 me-2"></i>Totales del parque</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 px-6 py-4">
            @foreach ([
                ['Total impresiones', $k($totales['impresiones']), 'fa-print'],
                ['Total copias', $k($totales['copias']), 'fa-copy'],
                ['Total escaneos', $k($totales['escaneos']), 'fa-expand'],
                ['Costo aprox.', $q($totales['costo_total']), 'fa-coins'],
            ] as [$titulo, $valor, $icono])
                <div class="flex items-center justify-center">
                    <span class="z-10 -me-4 inline-flex items-center justify-center size-16 rounded-full bg-[#0d2149] text-sky-400 text-2xl"><i class="fas {{ $icono }}"></i></span>
                    <div class="size-32 rounded-full bg-[#0d2149] text-white flex flex-col items-center justify-center text-center">
                        <span class="text-xs">{{ $titulo }}:</span>
                        <span class="text-2xl font-extrabold">{{ $valor }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 px-6 pb-6">
            <div>
                <h3 class="font-extrabold text-[#0d2149] uppercase"><i class="fas fa-arrow-right text-sky-500 me-2"></i>Comparativa por equipo (impresiones + copias)</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($equipos as $i => $e)
                        @php [$nivel, $clase] = \App\Livewire\Tablero::nivel($e->bn, $maxBn); @endphp
                        <div class="flex items-center gap-2">
                            <span class="w-24 shrink-0 text-sm font-semibold text-gray-800 truncate">{{ $e->ubicacion }}</span>
                            <div class="flex-1 flex items-center gap-2">
                                <div class="h-7 rounded-e-lg {{ $i % 2 ? 'bg-[#0d2149]' : 'bg-sky-500' }} flex items-center justify-end pe-2 text-white text-xs font-bold"
                                     style="width: {{ max(2, round($e->bn / $maxBn * 100)) }}%">{{ $e->bn / $maxBn > 0.25 ? number_format($e->bn) : '' }}</div>
                                @if ($e->bn / $maxBn <= 0.25) <span class="text-xs font-bold text-gray-800">{{ number_format($e->bn) }}</span> @endif
                            </div>
                            <span class="w-24 shrink-0 text-center rounded-full bg-[#0d2149] py-1 text-[11px] font-extrabold {{ $clase }}">{{ $nivel }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="font-extrabold text-[#0d2149] uppercase"><i class="fas fa-arrow-right text-sky-500 me-2"></i>Datos operativos clave</h3>
                <div class="mt-3 space-y-2">
                    @foreach ($equipos->sortBy('nivel')->values() as $e)
                        @php
                            $tag = $topBn && $e->id === $topBn->id && $e->bn > 0 ? 'Mayor uso general' : ($topEsc && $e->id === $topEsc->id && $e->escaneos > 0 ? 'Foco en escaneo' : null);
                        @endphp
                        <div class="flex items-center rounded-full {{ $tag ? 'bg-sky-500 text-white' : 'bg-sky-200/70 text-gray-900' }} text-sm overflow-hidden">
                            <span class="shrink-0 bg-[#0d2149] text-white font-extrabold px-3 py-1.5 w-24 text-center truncate">{{ $e->ubicacion }}</span>
                            <span class="px-3 whitespace-nowrap">Imp. <b>{{ number_format($e->impresiones) }}</b></span>
                            <span class="px-3 border-s border-current/30 whitespace-nowrap">Cop. <b>{{ number_format($e->copias) }}</b></span>
                            <span class="px-3 border-s border-current/30 whitespace-nowrap">Esc. <b>{{ number_format($e->escaneos) }}</b></span>
                            @if ($tag)
                                <span class="ms-auto bg-[#0d2149] text-amber-300 font-bold px-3 py-1.5 whitespace-nowrap">{{ $tag }}</span>
                            @else
                                <span class="ms-auto px-3 whitespace-nowrap text-xs">{{ $q($e->costo_total) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($hallazgos)
            <div class="bg-white/60 px-6 py-5">
                <h3 class="text-center font-extrabold text-[#0d2149] uppercase mb-3">Hallazgos clave</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 xl:divide-x divide-sky-400">
                    @foreach ($hallazgos as [$titulo, $texto])
                        <div class="text-center px-3">
                            <div class="font-bold text-gray-900">{{ $titulo }}</div>
                            <p class="text-sm text-gray-700">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-center text-sm text-gray-600 pb-6">Aún no hay consumo en este rango: se necesitan al menos dos lecturas diarias (6 pm).</p>
        @endif
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
