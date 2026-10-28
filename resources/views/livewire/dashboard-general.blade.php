@php
    $n = fn ($v) => $v === null ? '—' : number_format((int) $v);
    $f = fn ($d) => $d?->format('d/m/Y');
    $periodos = $r['periodos'];
    $ultimo = $periodos->last();
    $equipos = $r['por_equipo']->sortByDesc('acumulado')->values();
    $max = max(1, (int) $equipos->max('acumulado'));
    $primerMes = $periodos->first()?->fecha->locale('es')->translatedFormat('M');
    $ultimoMes = $ultimo?->fecha->locale('es')->translatedFormat('M Y');
    $iconos = [
        'blue' => 'bg-blue-600', 'green' => 'bg-green-700', 'navy' => 'bg-[#0d2149]', 'purple' => 'bg-purple-700',
        'orange' => 'bg-orange-500', 'teal' => 'bg-cyan-700', 'amber' => 'bg-amber-500', 'red' => 'bg-red-600',
    ];
    $titulos = ['blue' => 'text-blue-800', 'green' => 'text-green-700', 'navy' => 'text-[#0d2149]', 'purple' => 'text-purple-800',
        'orange' => 'text-orange-600', 'teal' => 'text-cyan-800', 'amber' => 'text-amber-600', 'red' => 'text-red-600'];
    $kpis = [
        ['Impresión mensual aprox.', '(12 meses)', $n($r['mensual_aprox']), 'Promedio mensual del bolsón', 'fa-chart-column', 'blue'],
        ['Consumo promedio', $primerMes ? mb_strtoupper("$primerMes – $ultimoMes") : '', $n($r['promedio_mensual']), 'Páginas promedio / mes', 'fa-chart-line', 'green'],
        ['Consumo acumulado', '('.$f($r['inicio']).' – '.$f($r['corte_al']).')', $n($r['acumulado']), 'Páginas acumuladas', 'fa-file-lines', 'navy'],
        ['Bolsón B/N total', 'Contrato '.$r['contrato'], $n($r['bolson']), 'Páginas contratadas', 'fa-layer-group', 'purple'],
        ['Bolsón disponible', 'al '.$f($r['corte_al']), $n($r['disponible']), 'Páginas disponibles', 'fa-box-open', $r['porcentaje'] >= 90 ? 'red' : 'orange'],
        ['% consumo del bolsón', '', $r['porcentaje'].'%', 'Del total contratado', 'fa-chart-pie', 'teal'],
        ['Producido '.($ultimo ? mb_strtolower($ultimo->fecha->locale('es')->translatedFormat('F')) : ''), $ultimo ? 'corte '.$f($ultimo->fecha) : '', $n($ultimo?->total), 'Páginas del último corte', 'fa-print', 'amber'],
        ['En curso', $r['en_curso']->desde ? 'desde '.$f($r['en_curso']->desde) : '', $n($r['en_curso']->total), 'Desde el último corte', 'fa-hourglass-half', 'blue'],
    ];
    $seccion = 'bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden';
    $cabecera = 'bg-[#0d2149] text-white text-center px-4 py-2.5';
@endphp

<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
    {{-- Encabezado --}}
    <div class="rounded-2xl bg-gradient-to-r from-[#0d2149] via-[#123a7a] to-[#1d4ed8] text-white px-6 py-5 flex flex-wrap items-center justify-between gap-4 shadow">
        <div class="flex items-center gap-4">
            <i class="fas fa-print text-4xl opacity-90"></i>
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">CONSUMO DE COPIAS E IMPRESIONES</h1>
                <p class="text-blue-100">Dashboard de consumo acumulado al {{ $r['corte_al']?->format('d/m/Y H:i') }} Hrs.</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-calendar-days text-3xl opacity-90"></i>
                <div class="text-sm leading-tight">
                    <div class="font-semibold uppercase tracking-wide">Corte de información</div>
                    <div>{{ $r['corte_al']?->format('d/m/Y H:i') }} Hrs.</div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('export.excel', ['tipo' => 'control']) }}" class="py-2 px-3 inline-flex items-center gap-2 text-xs font-semibold rounded-lg bg-white/15 hover:bg-white/25">
                    <i class="fas fa-file-excel"></i> Control de contadores
                </a>
                <a href="{{ route('export.pdf', ['tipo' => 'resumen']) }}" class="py-2 px-3 inline-flex items-center gap-2 text-xs font-semibold rounded-lg bg-white/15 hover:bg-white/25">
                    <i class="fas fa-file-pdf"></i> Resumen PDF
                </a>
            </div>
        </div>
    </div>

    @foreach ($r['alertas'] as $a)
        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-2.5 text-amber-900 text-sm flex gap-2">
            <i class="fas fa-triangle-exclamation mt-0.5"></i><span>{{ $a }}</span>
        </div>
    @endforeach

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 2xl:grid-cols-8 gap-3">
        @foreach ($kpis as [$titulo, $sub, $valor, $pie, $icono, $color])
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-3 flex flex-col">
                <div class="text-center leading-tight min-h-[2.5rem]">
                    <div class="text-xs font-bold uppercase {{ $titulos[$color] }}">{{ $titulo }}</div>
                    <div class="text-[11px] font-semibold {{ $titulos[$color] }} opacity-80">{{ $sub }}</div>
                </div>
                <div class="flex items-center gap-3 mt-2">
                    <span class="shrink-0 inline-flex items-center justify-center size-11 rounded-full text-white {{ $iconos[$color] }}"><i class="fas {{ $icono }}"></i></span>
                    <div class="min-w-0">
                        <div class="text-2xl font-extrabold {{ $titulos[$color] }} leading-none">{{ $valor }}</div>
                        <div class="text-[11px] text-gray-500 mt-1">{{ $pie }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Fila 2: por impresora · distribución · resumen --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <div class="xl:col-span-5 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Consumo acumulado por impresora / ubicación</div>
                <div class="text-xs text-blue-100">({{ $f($r['inicio']) }} – {{ $f($r['corte_al']) }})</div>
            </div>
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-600">
                    <tr><th class="px-4 py-2 text-left">Ubicación</th><th class="px-2 py-2 text-left">Serie</th><th class="px-2 py-2 text-left">Páginas acumuladas</th><th class="px-4 py-2 text-right">% del total</th></tr>
                </thead>
                <tbody>
                    @foreach ($equipos as $i => $e)
                        <tr>
                            <td class="px-4 py-1.5 font-semibold text-gray-800 whitespace-nowrap">{{ $e->ubicacion }}</td>
                            <td class="px-2 py-1.5 text-gray-600 text-xs">{{ $e->serie_contrato ?? $e->serie }}</td>
                            <td class="px-2 py-1.5 w-full">
                                <div class="flex items-center gap-2">
                                    <div class="h-4 rounded-sm bg-blue-700" style="width: {{ max(1, round($e->acumulado / $max * 75)) }}%"></div>
                                    <span class="text-xs font-semibold text-gray-700">{{ $n($e->acumulado) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-1.5 text-right text-gray-700">{{ $e->porcentaje }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#0d2149] text-white font-bold">
                        <td class="px-4 py-2" colspan="2">TOTAL GENERAL</td>
                        <td class="px-2 py-2 text-right text-lg">{{ $n($r['acumulado']) }}</td>
                        <td class="px-4 py-2 text-right text-lg">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="xl:col-span-4 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Distribución del consumo por ubicación</div>
                <div class="text-xs text-blue-100">({{ $f($r['inicio']) }} – {{ $f($r['corte_al']) }})</div>
            </div>
            <div class="p-4 grid grid-cols-5 gap-2 items-center">
                <div class="col-span-3">
                    @include('livewire.partials.chart', ['alto' => 270, 'config' => [
                        'type' => 'doughnut',
                        'data' => ['labels' => $equipos->pluck('ubicacion'), 'datasets' => [['data' => $equipos->pluck('acumulado'), 'backgroundColor' => array_slice($colores, 0, $equipos->count()), 'borderColor' => '#fff', 'borderWidth' => 2]]],
                        'options' => ['cutout' => '55%', 'plugins' => ['legend' => ['display' => false], 'centro' => ['valor' => $n($r['acumulado']), 'texto' => "PÁGINAS\nACUMULADAS"], 'etiquetas' => true]],
                    ]])
                </div>
                <ul class="col-span-2 space-y-2 text-xs">
                    @foreach ($equipos as $i => $e)
                        <li class="flex items-center gap-2">
                            <span class="size-3 rounded-full shrink-0" style="background: {{ $colores[$i % count($colores)] }}"></span>
                            <span class="font-semibold text-gray-800">{{ $e->ubicacion }}</span>
                            <span class="ms-auto text-gray-500 whitespace-nowrap">{{ $n($e->acumulado) }} ({{ $e->porcentaje }}%)</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="xl:col-span-3 {{ $seccion }}">
            <div class="{{ $cabecera }} font-bold uppercase text-sm">Resumen del contrato</div>
            <ul class="divide-y divide-gray-100 px-5">
                @foreach ([
                    ['fa-file-contract', 'text-blue-700', '# de contrato', $r['contrato']],
                    ['fa-calendar', 'text-gray-600', 'Vigencia', $f($r['inicio']).' – '.$f($r['fin'])],
                    ['fa-layer-group', 'text-purple-700', 'Bolsón B/N total', $n($r['bolson'])],
                    ['fa-circle-check', 'text-green-700', 'Consumo acumulado', $n($r['acumulado'])],
                    ['fa-box-open', 'text-orange-500', 'Bolsón disponible', $n($r['disponible'])],
                    ['fa-hourglass-end', $r['agotamiento'] && $r['fin'] && $r['agotamiento']->lt($r['fin']) ? 'text-red-600' : 'text-green-700', 'Se agota (proyección)', $r['agotamiento'] ? $f($r['agotamiento']) : '—'],
                ] as [$icono, $color, $label, $valor])
                    <li class="flex items-center gap-3 py-2.5">
                        <i class="fas {{ $icono }} {{ $color }} text-xl w-7 text-center"></i>
                        <span class="text-sm font-semibold text-gray-700">{{ $label }}</span>
                        <span class="ms-auto text-lg font-bold text-[#0d2149] whitespace-nowrap">{{ $valor }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="bg-blue-50 px-5 py-3 flex items-center gap-3">
                <i class="fas fa-chart-pie text-3xl text-cyan-700"></i>
                <span class="text-sm font-semibold text-gray-700">% consumo del bolsón</span>
                <span class="ms-auto text-3xl font-extrabold text-blue-700">{{ $r['porcentaje'] }}%</span>
            </div>
            <div class="px-5 py-3">
                <div class="h-2.5 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full {{ $r['porcentaje'] >= 90 ? 'bg-red-600' : 'bg-blue-600' }}" style="width: {{ min(100, $r['porcentaje']) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fila 3: tendencia · producido último corte · hallazgos --}}
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
        <div class="xl:col-span-6 {{ $seccion }}">
            <div class="{{ $cabecera }} font-bold uppercase text-sm">Tendencia mensual de producción (páginas)</div>
            <div class="p-4">
                @include('livewire.partials.chart', ['alto' => 240, 'config' => [
                    'type' => 'line',
                    'data' => ['labels' => $periodos->map(fn ($p) => [mb_strtoupper($p->fecha->locale('es')->translatedFormat('F')), $p->fecha->format('Y')]), 'datasets' => [[
                        'data' => $periodos->pluck('total'), 'borderColor' => '#1d4ed8', 'backgroundColor' => '#1d4ed8',
                        'borderWidth' => 2.5, 'pointRadius' => 5, 'tension' => 0.25,
                    ]]],
                    'options' => ['layout' => ['padding' => ['top' => 20]], 'plugins' => ['legend' => ['display' => false], 'etiquetas' => true],
                        'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => '#f1f5f9']], 'x' => ['grid' => ['display' => false], 'ticks' => ['font' => ['size' => 10]]]]],
                ]])
            </div>
            <div class="overflow-x-auto border-t border-gray-100">
                <table class="w-full text-xs text-center">
                    <tr>
                        <th rowspan="2" class="bg-green-50 text-green-800 font-bold px-3 py-2 text-left whitespace-nowrap">SALDO DEL<br>BOLSÓN</th>
                        @foreach ($periodos as $p)
                            <td class="px-2 pt-2 text-gray-500 whitespace-nowrap {{ $loop->last ? 'bg-green-50 text-green-800 font-semibold' : '' }}">Saldo al<br>{{ $f($p->fecha) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($periodos as $p)
                            <td class="px-2 pb-2 font-bold text-sm {{ $loop->last ? 'bg-green-50 text-green-700' : 'text-gray-800' }}">{{ $n($p->saldo) }}</td>
                        @endforeach
                    </tr>
                </table>
            </div>
        </div>

        <div class="xl:col-span-3 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Producido por impresora</div>
                <div class="text-xs text-blue-100">({{ $ultimo?->etiqueta }} · en curso desde {{ $f($r['en_curso']->desde) }})</div>
            </div>
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-600 border-b border-gray-100">
                    <tr><th class="px-4 py-2 text-left">Ubicación</th><th class="px-2 py-2 text-left">Serie</th><th class="px-2 py-2 text-right">{{ $ultimo ? ucfirst($ultimo->fecha->locale('es')->translatedFormat('F')) : 'Último' }}</th><th class="px-4 py-2 text-right">En curso</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($r['por_equipo']->sortByDesc('ultimo_periodo')->values() as $e)
                        <tr>
                            <td class="px-4 py-1.5 font-medium">{{ $e->ubicacion }}</td>
                            <td class="px-2 py-1.5 text-xs text-gray-600">{{ $e->serie }}</td>
                            <td class="px-2 py-1.5 text-right {{ $e->ultimo_periodo === null ? 'text-red-500' : '' }}">{{ $e->ultimo_periodo === null ? 'pendiente' : $n($e->ultimo_periodo) }}</td>
                            <td class="px-4 py-1.5 text-right text-gray-600">{{ $n($e->en_curso) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-[#0d2149] text-white font-bold">
                        <td class="px-4 py-2" colspan="2">TOTAL GENERAL</td>
                        <td class="px-2 py-2 text-right">{{ $n($ultimo?->total) }}</td>
                        <td class="px-4 py-2 text-right">{{ $n($r['en_curso']->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="xl:col-span-3 {{ $seccion }}">
            <div class="{{ $cabecera }} font-bold uppercase text-sm">Hallazgos principales</div>
            <ul class="p-4 space-y-4">
                @foreach ($hallazgos as [$titulo, $texto, $icono, $color])
                    <li class="flex gap-3">
                        <span class="shrink-0 inline-flex items-center justify-center size-11 rounded-full text-white {{ $iconos[$color] ?? 'bg-blue-600' }}"><i class="fas {{ $icono }}"></i></span>
                        <div>
                            <div class="font-bold text-sm {{ $titulos[$color] ?? 'text-blue-800' }}">{{ $titulo }}</div>
                            <p class="text-xs text-gray-700 leading-snug">{{ $texto }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
