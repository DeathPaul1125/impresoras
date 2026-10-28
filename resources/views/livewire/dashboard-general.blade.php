@php
    $n = fn ($v) => $v === null ? '—' : number_format((int) $v);
    $f = fn ($d) => $d?->format('d/m/Y');
    $mes = fn ($d) => $d ? $d->locale('es')->translatedFormat('F') : '';
    $periodos = $r['periodos'];
    $ultimo = $periodos->last();
    $equipos = $r['por_equipo']->sortByDesc('acumulado')->values();
    $max = max(1, (int) $equipos->max('acumulado'));
    $primerMes = $periodos->first()?->fecha->locale('es')->translatedFormat('M');
    $ultimoMes = $ultimo?->fecha->locale('es')->translatedFormat('M Y');
    $atenuar = fn ($id) => $equipo && $equipo !== $id;
    $selNombre = $ficha['e']->ubicacion ?? null;
    $iconos = [
        'blue' => 'bg-blue-600', 'green' => 'bg-green-700', 'navy' => 'bg-marino', 'purple' => 'bg-purple-700',
        'orange' => 'bg-orange-500', 'teal' => 'bg-cyan-700', 'amber' => 'bg-amber-500', 'red' => 'bg-red-600',
    ];
    $titulos = ['blue' => 'text-blue-800', 'green' => 'text-green-700', 'navy' => 'text-marino', 'purple' => 'text-purple-800',
        'orange' => 'text-orange-600', 'teal' => 'text-cyan-800', 'amber' => 'text-amber-600', 'red' => 'text-red-600'];
    $kpis = [
        ['Impresión mensual aprox.', '(12 meses)', $n($r['mensual_aprox']), 'Promedio mensual del bolsón', 'fa-chart-column', 'blue'],
        ['Consumo promedio', $primerMes ? mb_strtoupper("$primerMes – $ultimoMes") : '', $n($r['promedio_mensual']), 'Páginas promedio / mes', 'fa-chart-line', 'green'],
        ['Consumo acumulado', '('.$f($r['inicio']).' – '.$f($r['corte_al']).')', $n($r['acumulado']), 'Páginas acumuladas', 'fa-file-lines', 'navy'],
        ['Bolsón B/N total', 'Contrato '.$r['contrato'], $n($r['bolson']), 'Páginas contratadas', 'fa-layer-group', 'purple'],
        ['Bolsón disponible', 'al '.$f($r['corte_al']), $n($r['disponible']), 'Páginas disponibles', 'fa-box-open', $r['porcentaje'] >= 90 ? 'red' : 'orange'],
        ['% consumo del bolsón', '', $r['porcentaje'].'%', 'Del total contratado', 'fa-chart-pie', 'teal'],
        ['Producido '.mb_strtolower($mes($sel?->fecha)), $sel ? 'corte '.$f($sel->fecha) : '', $n($sel?->total), $esUltimo ? 'Páginas del último corte' : 'Corte seleccionado', 'fa-print', 'amber'],
    ];
    $seccion = 'bg-white rounded-xl shadow-hoja overflow-hidden';
    $cabecera = 'bg-marino text-white text-center px-4 py-2.5';
    $pista = 'text-[11px] text-blue-200/80 font-normal normal-case';
@endphp

<div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-5">
    {{-- Indicador de carga discreto --}}
    <div wire:loading.delay class="fixed top-16 end-6 z-40 rounded-full bg-marino text-white text-xs px-3 py-1.5 shadow-lg">
        <i class="fas fa-spinner fa-spin me-1"></i> Actualizando…
    </div>

    {{-- Encabezado --}}
    <div class="rounded-xl bg-marino text-white px-6 py-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <i class="fas fa-print text-4xl opacity-90"></i>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight">CONSUMO DE COPIAS E IMPRESIONES</h1>
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

    {{-- Selección activa (solo cuando hay algo seleccionado) --}}
    @if ($equipo || $periodo)
    <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="text-tinta-3">Viendo:</span>
            @if ($selNombre)
                <button type="button" wire:click="seleccionarEquipo" class="inline-flex items-center gap-2 h-7 ps-2 pe-2.5 rounded-full bg-white shadow-hoja hover:bg-papel">
                    <span class="size-2.5 rounded-full" style="background: {{ $colorDe[$equipo] }}"></span>
                    <b class="text-tinta">{{ $selNombre }}</b> <i class="fas fa-xmark text-tinta-3"></i>
                </button>
            @endif
            @if ($periodo && $sel)
                <button type="button" wire:click="seleccionarPeriodo" class="inline-flex items-center gap-2 h-7 px-2.5 rounded-full bg-white shadow-hoja hover:bg-papel">
                    <i class="fas fa-calendar-check text-amber-500"></i> <b class="text-tinta">{{ $sel->etiqueta }}</b> <i class="fas fa-xmark text-tinta-3"></i>
                </button>
            @endif
            <button type="button" wire:click="limpiar" class="text-xs font-medium text-marino-600 hover:underline">Quitar selección</button>
    </div>
    @endif

    @if ($r['alertas'])
        <details class="group rounded-lg bg-amber-50 ring-1 ring-inset ring-amber-300/60 px-4 py-2 text-amber-900">
            <summary class="flex items-center gap-2 text-sm cursor-pointer list-none">
                <i class="fas fa-triangle-exclamation"></i>
                <b>{{ count($r['alertas']) }} pendiente{{ count($r['alertas']) > 1 ? 's' : '' }} de revisar</b>
                <span class="truncate text-amber-800">· {{ \Illuminate\Support\Str::limit($r['alertas'][0], 110) }}</span>
                <span class="ms-auto shrink-0 text-xs font-semibold text-amber-800">Ver detalle <i class="fas fa-chevron-down transition-transform group-open:rotate-180"></i></span>
            </summary>
            <ul class="mt-2 space-y-1 text-[13px] list-disc ps-6 marker:text-amber-500">
                @foreach ($r['alertas'] as $a) <li>{{ $a }}</li> @endforeach
            </ul>
            @can('admin') <a href="{{ route('cortes') }}" wire:navigate class="mt-1.5 mb-1 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800 hover:underline">Ir a Cortes del contrato <i class="fas fa-arrow-right"></i></a> @endcan
        </details>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 2xl:grid-cols-7 gap-3">
        @foreach ($kpis as [$titulo, $sub, $valor, $pie, $icono, $color])
            <div class="bg-white rounded-xl shadow-hoja px-3 py-3 flex flex-col">
                <div class="text-center leading-tight">
                    <div class="text-xs font-bold uppercase {{ $titulos[$color] }}">{{ $titulo }}</div>
                    <div class="text-[11px] font-semibold {{ $titulos[$color] }} opacity-80 truncate">{{ $sub ?: ' ' }}</div>
                </div>
                <div class="flex items-center gap-3 mt-2 mx-auto">
                    <span class="shrink-0 inline-flex items-center justify-center size-11 rounded-full text-white {{ $iconos[$color] }}"><i class="fas {{ $icono }}"></i></span>
                    <div class="min-w-0">
                        <div class="cifra text-[26px] font-bold tracking-tight leading-none {{ $titulos[$color] }}">{{ $valor }}</div>
                        <div class="text-[11px] text-tinta-3 mt-1">{{ $pie }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Fila 2: por impresora · distribución · resumen --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-12 gap-5">
        <div class="2xl:col-span-5 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Consumo acumulado por impresora / ubicación</div>
                <div class="text-xs text-blue-100">({{ $f($r['inicio']) }} – {{ $f($r['corte_al']) }}) <span class="{{ $pista }}">· clic para ver detalle</span></div>
            </div>
            <table class="w-full text-sm">
                <thead class="text-xs text-tinta-3">
                    <tr><th class="px-4 py-2 text-left">Ubicación</th><th class="px-2 py-2 text-left">Serie</th><th class="px-2 py-2 text-left">Páginas acumuladas</th><th class="px-4 py-2 text-right">% del total</th></tr>
                </thead>
                <tbody>
                    @foreach ($equipos as $e)
                        <tr wire:key="acum-{{ $e->id }}" wire:click="seleccionarEquipo({{ $e->id }})"
                            class="cursor-pointer transition-[opacity,background-color] duration-150 hover:bg-papel {{ $equipo === $e->id ? 'bg-marino-50' : '' }} {{ $atenuar($e->id) ? 'opacity-40' : '' }}">
                            <td class="px-4 py-1.5 font-semibold text-tinta whitespace-nowrap"><x-nivel :nivel="$e->nivel" class="me-1.5" />{{ $e->ubicacion }}</td>
                            <td class="px-2 py-1.5 text-tinta-3 text-xs">{{ $e->serie_contrato ?? $e->serie }}</td>
                            <td class="px-2 py-1.5 w-full">
                                <div class="flex items-center gap-2">
                                    <div class="h-4 rounded-sm" style="width: {{ max(1, round($e->acumulado / $max * 75)) }}%; background: {{ $equipo === $e->id ? $colorDe[$e->id] : '#1d4ed8' }}"></div>
                                    <span class="text-xs font-semibold text-tinta-2">{{ $n($e->acumulado) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-1.5 text-right text-tinta-2">{{ $e->porcentaje }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-marino text-white font-bold">
                        <td class="px-4 py-2" colspan="2">TOTAL GENERAL</td>
                        <td class="px-2 py-2 text-right text-lg">{{ $n($r['acumulado']) }}</td>
                        <td class="px-4 py-2 text-right text-lg">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="2xl:col-span-4 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Distribución del consumo por ubicación</div>
                <div class="text-xs text-blue-100">({{ $f($r['inicio']) }} – {{ $f($r['corte_al']) }}) <span class="{{ $pista }}">· clic en una porción</span></div>
            </div>
            <div class="p-4 grid grid-cols-5 gap-2 items-center">
                <div class="col-span-3">
                    @php
                        $selE = $equipo ? $equipos->firstWhere('id', $equipo) : null;
                        $centro = $selE ? ['valor' => $selE->porcentaje.'%', 'texto' => mb_strtoupper($selE->ubicacion)."\n".$n($selE->acumulado).' PÁG.'] : ['valor' => $n($r['acumulado']), 'texto' => "PÁGINAS\nACUMULADAS"];
                    @endphp
                    @include('livewire.partials.chart', ['alto' => 270,
                        'click' => ['accion' => 'seleccionarEquipo', 'valores' => $equipos->pluck('id')],
                        'config' => [
                            'type' => 'doughnut',
                            'data' => ['labels' => $equipos->pluck('ubicacion'), 'datasets' => [[
                                'data' => $equipos->pluck('acumulado'),
                                'backgroundColor' => $equipos->map(fn ($e) => $colorDe[$e->id].($atenuar($e->id) ? '33' : '')),
                                'offset' => $equipos->map(fn ($e) => $equipo === $e->id ? 14 : 0),
                                'borderColor' => '#fff', 'borderWidth' => 2,
                            ]]],
                            'options' => ['cutout' => '55%', 'layout' => ['padding' => 8], 'plugins' => ['legend' => ['display' => false], 'centro' => $centro, 'etiquetas' => ! $equipo]],
                        ]])
                </div>
                <ul class="col-span-2 space-y-1 text-xs">
                    @foreach ($equipos as $e)
                        <li wire:key="ley-{{ $e->id }}">
                            <button type="button" wire:click="seleccionarEquipo({{ $e->id }})"
                                    class="w-full flex items-center gap-2 rounded-md px-1.5 py-1 hover:bg-papel transition-opacity {{ $equipo === $e->id ? 'bg-marino-50' : '' }} {{ $atenuar($e->id) ? 'opacity-40' : '' }}">
                                <span class="size-3 rounded-full shrink-0" style="background: {{ $colorDe[$e->id] }}"></span>
                                <span class="font-semibold text-tinta whitespace-nowrap">{{ $e->ubicacion }}</span>
                                <span class="ms-auto text-tinta-3 whitespace-nowrap cifra">{{ $n($e->acumulado) }} ({{ $e->porcentaje }}%)</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="xl:col-span-2 2xl:col-span-3 {{ $seccion }}">
            <div class="{{ $cabecera }} font-bold uppercase text-sm">Resumen del contrato</div>
            <ul class="grid sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-1 gap-x-8 px-5 [&>li]:border-b [&>li]:border-linea">
                @foreach ([
                    ['fa-file-contract', 'text-blue-700', '# de contrato', $r['contrato']],
                    ['fa-calendar', 'text-tinta-3', 'Vigencia', $r['inicio']?->format('d/m/y').' – '.$r['fin']?->format('d/m/y')],
                    ['fa-layer-group', 'text-purple-700', 'Bolsón B/N total', $n($r['bolson'])],
                    ['fa-circle-check', 'text-green-700', 'Consumo acumulado', $n($r['acumulado'])],
                    ['fa-box-open', 'text-orange-500', 'Bolsón disponible', $n($r['disponible'])],
                    ['fa-hourglass-end', $r['agotamiento'] && $r['fin'] && $r['agotamiento']->lt($r['fin']) ? 'text-red-600' : 'text-green-700', 'Se agota (proyección)', $r['agotamiento'] ? $f($r['agotamiento']) : '—'],
                ] as [$icono, $color, $label, $valor])
                    <li class="flex items-center gap-3 py-2.5">
                        <i class="fas {{ $icono }} {{ $color }} text-xl w-7 text-center"></i>
                        <span class="text-sm font-semibold text-tinta-2">{{ $label }}</span>
                        <span class="ms-auto text-lg font-bold text-marino whitespace-nowrap cifra">{{ $valor }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="bg-marino-50 px-5 py-3 flex items-center gap-3">
                <i class="fas fa-chart-pie text-3xl text-cyan-700"></i>
                <span class="text-sm font-semibold text-tinta-2">% consumo del bolsón</span>
                <span class="ms-auto cifra text-3xl font-bold tracking-tight text-marino-600">{{ $r['porcentaje'] }}%</span>
            </div>
            <x-medidor-bolson :r="$r" class="px-5 py-4" />
        </div>
    </div>

    {{-- Ficha de la impresora seleccionada --}}
    @if ($ficha)
        @php $fe = $ficha['e']; $d = $ficha['dispositivo']; $color = $colorDe[$fe->id]; @endphp
        <section wire:key="ficha-{{ $fe->id }}" class="bg-white rounded-xl shadow-hoja overflow-hidden ring-2" style="--tw-ring-color: {{ $color }}">
            <header class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-linea">
                <span class="size-3 rounded-full" style="background: {{ $color }}"></span>
                <x-nivel :nivel="$fe->nivel" />
                <h2 class="text-lg font-semibold text-tinta">{{ $fe->ubicacion }}</h2>
                <span class="text-sm text-tinta-3 cifra">{{ $fe->modelo }} · serie {{ $fe->serie }}
                    @if ($d?->direccion_ip) · <a href="http://{{ $d->direccion_ip }}" target="_blank" rel="noopener" class="text-marino-600 hover:underline">{{ $d->direccion_ip }}</a> @endif
                </span>
                <button type="button" wire:click="seleccionarEquipo" class="ms-auto size-8 inline-flex items-center justify-center rounded-md text-tinta-3 hover:bg-papel" title="Cerrar"><i class="fas fa-xmark"></i></button>
            </header>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 p-5">
                <dl class="lg:col-span-4 grid grid-cols-2 gap-x-4 gap-y-3 content-start">
                    @foreach ([
                        ['Acumulado', $n($fe->acumulado), $fe->porcentaje.'% del total'],
                        ['Promedio mensual', $n($ficha['promedio']), 'por corte'],
                        ['Mejor mes', $n($ficha['mejor']['valor'] ?? null), $ficha['mejor']['etiqueta'] ?? ''],
                        ['Mes más bajo', $n($ficha['menor']['valor'] ?? null), $ficha['menor']['etiqueta'] ?? ''],
                        ['En curso', $n($fe->en_curso), 'desde el último corte'],
                        ['Contador actual', $n($fe->contador_actual), 'impresiones + copias'],
                    ] as [$k, $v, $nota])
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-tinta-3">{{ $k }}</dt>
                            <dd class="cifra text-xl font-bold text-tinta leading-tight">{{ $v }}</dd>
                            <dd class="text-[11px] text-tinta-3">{{ $nota }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="lg:col-span-8">
                    <div class="text-xs font-semibold text-tinta-2 mb-2">Producido en cada corte <span class="font-normal text-tinta-3">· clic en un mes para seleccionarlo</span></div>
                    <div class="flex items-end gap-1.5 h-40">
                        @foreach ($ficha['serie'] as $p)
                            @php $esSel = $sel && $p['fecha']->equalTo($sel->fecha); @endphp
                            <button type="button" wire:click="seleccionarPeriodo('{{ $p['fecha']->toDateString() }}')" title="{{ $p['etiqueta'] }}: {{ $n($p['valor']) }}"
                                    class="group flex-1 h-full flex flex-col justify-end items-center gap-1 min-w-0">
                                <span class="cifra text-[10px] font-semibold {{ $esSel ? 'text-tinta' : 'text-tinta-3' }}">{{ $p['valor'] === null ? 's/d' : number_format($p['valor'] / 1000, 1).'k' }}</span>
                                <span class="w-full rounded-t transition-opacity group-hover:opacity-100 {{ $esSel ? '' : 'opacity-60' }} {{ $p['valor'] === null ? 'bg-red-200' : '' }}"
                                      style="height: {{ $p['valor'] === null ? 6 : max(4, round($p['valor'] / $ficha['max'] * 100)) }}%; {{ $p['valor'] !== null ? 'background: '.$color : '' }}"></span>
                                <span class="text-[10px] {{ $esSel ? 'font-bold text-tinta' : 'text-tinta-3' }}">{{ mb_substr(ucfirst($mes($p['fecha'])), 0, 3) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Fila 3: tendencia · producido del corte seleccionado · hallazgos --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-12 gap-5">
        <div class="xl:col-span-2 2xl:col-span-6 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Tendencia mensual de producción (páginas){{ $selNombre ? ' · '.$selNombre : '' }}</div>
                <div class="{{ $pista }}">Clic en un punto para ver ese corte</div>
            </div>
            <div class="p-4">
                @php
                    $etiquetasMes = $periodos->map(fn ($p) => [mb_strtoupper($mes($p->fecha)), $p->fecha->format('Y')]);
                    $radios = $periodos->map(fn ($p) => $sel && $p->fecha->equalTo($sel->fecha) ? 8 : 4);
                    $datasets = $ficha
                        ? [
                            ['label' => $selNombre, 'data' => $periodos->map(fn ($p) => $p->producido[$equipo] ?? null), 'borderColor' => $colorDe[$equipo], 'backgroundColor' => $colorDe[$equipo],
                                'borderWidth' => 2.5, 'pointRadius' => $radios, 'tension' => 0.25, 'spanGaps' => true],
                            ['label' => 'Total', 'data' => $periodos->pluck('total'), 'borderColor' => '#cbd5e1', 'backgroundColor' => '#cbd5e1', 'borderDash' => [5, 4],
                                'borderWidth' => 1.5, 'pointRadius' => 2, 'tension' => 0.25, 'yAxisID' => 'y2', 'sinEtiquetas' => true],
                        ]
                        : [['label' => 'Total', 'data' => $periodos->pluck('total'), 'borderColor' => '#1d4ed8', 'backgroundColor' => '#1d4ed8', 'borderWidth' => 2.5, 'pointRadius' => $radios, 'tension' => 0.25]];
                    $escalas = ['y' => ['beginAtZero' => true, 'grid' => ['color' => '#f1f5f9']], 'x' => ['grid' => ['display' => false], 'ticks' => ['font' => ['size' => 10]]]];
                    if ($ficha) {
                        $escalas['y2'] = ['position' => 'right', 'beginAtZero' => true, 'display' => false];   // el total punteado va en su propia escala; su valor se ve en el tooltip
                    }
                @endphp
                @include('livewire.partials.chart', ['alto' => 240,
                    'click' => ['accion' => 'seleccionarPeriodo', 'valores' => $periodos->map(fn ($p) => $p->fecha->toDateString())],
                    'config' => [
                        'type' => 'line',
                        'data' => ['labels' => $etiquetasMes, 'datasets' => $datasets],
                        'options' => ['layout' => ['padding' => ['top' => 20]], 'interaction' => ['mode' => 'index', 'intersect' => false],
                            'plugins' => ['legend' => ['display' => (bool) $ficha, 'position' => 'bottom', 'labels' => ['boxWidth' => 12]], 'etiquetas' => true],
                            'scales' => $escalas],
                    ]])
            </div>
            <div class="overflow-x-auto border-t border-linea">
                <table class="w-full text-xs text-center">
                    <tr>
                        <th rowspan="2" class="sticky start-0 z-10 bg-saldo-50 text-saldo font-bold px-3 py-2 text-left whitespace-nowrap shadow-[1px_0_0_rgba(15,23,42,.08)]">SALDO DEL<br>BOLSÓN</th>
                        @foreach ($periodos as $p)
                            @php $esSel = $sel && $p->fecha->equalTo($sel->fecha); @endphp
                            <td wire:click="seleccionarPeriodo('{{ $p->fecha->toDateString() }}')" class="cursor-pointer px-2 pt-2 whitespace-nowrap hover:bg-papel {{ $esSel ? 'bg-saldo-50 text-saldo font-semibold' : 'text-tinta-3' }}">Saldo al<br>{{ $f($p->fecha) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($periodos as $p)
                            @php $esSel = $sel && $p->fecha->equalTo($sel->fecha); @endphp
                            <td wire:click="seleccionarPeriodo('{{ $p->fecha->toDateString() }}')" class="cursor-pointer cifra px-2 pb-2 font-bold text-sm hover:bg-papel {{ $esSel ? 'bg-saldo-50 text-saldo' : 'text-tinta' }}">{{ $n($p->saldo) }}</td>
                        @endforeach
                    </tr>
                </table>
            </div>
        </div>

        <div class="2xl:col-span-3 {{ $seccion }}">
            <div class="{{ $cabecera }}">
                <div class="font-bold uppercase text-sm">Producido por impresora</div>
                <div class="text-xs text-blue-100">({{ $sel?->etiqueta }}{{ $esUltimo ? ' · en curso desde '.$f($r['en_curso']->desde) : ' · corte del '.$f($sel?->fecha) }})</div>
            </div>
            <table class="w-full text-sm">
                <thead class="text-xs text-tinta-3 border-b border-linea">
                    <tr>
                        <th class="px-4 py-2 text-left">Ubicación</th><th class="px-2 py-2 text-left">Serie</th>
                        <th class="px-2 py-2 text-right">{{ ucfirst($mes($sel?->fecha)) }}</th>
                        @if ($esUltimo) <th class="px-4 py-2 text-right">En curso</th> @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach ($r['por_equipo']->sortByDesc(fn ($e) => $sel?->producido[$e->id] ?? -1)->values() as $e)
                        @php $v = $sel?->producido[$e->id] ?? null; @endphp
                        <tr wire:key="prod-{{ $e->id }}" wire:click="seleccionarEquipo({{ $e->id }})"
                            class="cursor-pointer hover:bg-papel transition-opacity {{ $equipo === $e->id ? 'bg-marino-50' : '' }} {{ $atenuar($e->id) ? 'opacity-40' : '' }}">
                            <td class="px-4 py-1.5 font-medium whitespace-nowrap"><x-nivel :nivel="$e->nivel" class="me-1.5" />{{ $e->ubicacion }}</td>
                            <td class="px-2 py-1.5 text-xs text-tinta-3">{{ $e->serie }}</td>
                            <td class="px-2 py-1.5 text-right cifra {{ $v === null ? 'text-red-500' : '' }}">{{ $v === null ? 'pendiente' : $n($v) }}</td>
                            @if ($esUltimo) <td class="px-4 py-1.5 text-right text-tinta-3 cifra">{{ $n($e->en_curso) }}</td> @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-marino text-white font-bold">
                        <td class="px-4 py-2" colspan="2">TOTAL GENERAL</td>
                        <td class="px-2 py-2 text-right cifra">{{ $n($sel?->total) }}</td>
                        @if ($esUltimo) <td class="px-4 py-2 text-right cifra">{{ $n($r['en_curso']->total) }}</td> @endif
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="2xl:col-span-3 {{ $seccion }}">
            <div class="{{ $cabecera }} font-bold uppercase text-sm">Hallazgos principales</div>
            <ul class="p-4 space-y-4">
                @foreach ($hallazgos as [$titulo, $texto, $icono, $color])
                    <li class="flex gap-3">
                        <span class="shrink-0 inline-flex items-center justify-center size-11 rounded-full text-white {{ $iconos[$color] ?? 'bg-blue-600' }}"><i class="fas {{ $icono }}"></i></span>
                        <div>
                            <div class="font-bold text-sm {{ $titulos[$color] ?? 'text-blue-800' }}">{{ $titulo }}</div>
                            <p class="text-xs text-tinta-2 leading-snug">{{ $texto }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
