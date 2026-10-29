@php
    $n = fn ($v) => number_format((int) $v);
    $var = fn ($a, $b) => $a > 0 ? round(($b - $a) / $a * 100, 1) : null;
    // Más consumo = ámbar (vigilar); menos = azul de acción. El verde queda reservado al saldo.
    $tono = fn ($v) => $v === null ? 'text-tinta-4' : ($v > 0 ? 'text-amber-700' : 'text-marino-600');
    $signo = fn ($v) => $v === null ? '—' : ($v > 0 ? '+' : ($v < 0 ? '−' : '')).number_format(abs($v), 1).'%';

    [$aAnt, $aAct] = $anios;
    $mesCorto = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $mesLargo = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $nComp = $comp['meses'];
    $tramo = $nComp === 1 ? $mesLargo[1] : 'enero a '.$mesLargo[$nComp];
    $ca = $comp['anterior'];
    $cb = $comp['actual'];
    $vBn = $var($ca->bn, $cb->bn);

    // Meses que no se leen solos: lectura tardía (dos meses juntos) y el mes en curso.
    $tardio = [];
    foreach ($anomalias['tardias'] as [$m1, $m2]) { $tardio[$m1] = [$m1, $m2]; $tardio[$m2] = [$m1, $m2]; }
    $parcial = $anomalias['parcial'];

    $techo = max(1, collect($serie)->flatMap(fn ($s) => [$s['anterior'], $s['actual']])->max());
    $crece = $impresoras->filter(fn ($i) => $i->anterior > 0)->sortByDesc(fn ($i) => $var($i->anterior, $i->actual));
    [$usrTotal, $usrTop, $usrPct] = $concentracion;
    $maxCelda = max(1, $impresoras->flatMap(fn ($i) => $i->meses->values())->max());
    $ctrl = 'block w-full h-9 py-0 px-3 rounded-lg border-0 bg-white shadow-hoja text-sm text-tinta focus:ring-2 focus:ring-marino-600';
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-tinta">Histórico</h1>
            <p class="text-sm text-tinta-3">Consumo mes a mes desde enero de {{ $aAnt }}, por impresora, unidad y usuario. B/N = impresiones + copias.</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="dispositivo" class="{{ $ctrl }} w-56 pe-8" aria-label="Filtrar por impresora">
                <option value="">Las 7 impresoras</option>
                @foreach ($dispositivos as $d) <option value="{{ $d->id }}">{{ $d->ubicacion }} · N{{ $d->nivel }}</option> @endforeach
            </select>
            <a href="{{ route('export.excel', ['tipo' => 'historia', 'dispositivo' => $dispositivo ?: null]) }}"
               class="h-9 px-3 inline-flex items-center gap-1.5 text-sm font-medium rounded-lg text-saldo bg-white hover:bg-saldo-50 ring-1 ring-inset ring-saldo/25 whitespace-nowrap">
                <i class="fas fa-file-excel"></i> Excel completo
            </a>
        </div>
    </div>

    @if (! $h->hayDatos())
        <x-panel>
            <p class="text-sm text-tinta-2">Aún no se ha importado la historia. En el servidor ejecute
                <code class="cifra bg-papel px-1.5 py-0.5 rounded">php artisan impresoras:importar-historia --desde=2025-01</code>
                (lee del sistema anterior; tarda menos de un minuto).</p>
        </x-panel>
    @else
        <div wire:loading.class="opacity-60" class="transition-opacity duration-150">

        {{-- Asiento del ejercicio: la conclusión escrita y el comparativo en forma de libro --}}
        <section class="bg-white rounded-xl shadow-hoja grid grid-cols-1 lg:grid-cols-[1.15fr_1fr]">
            <div class="min-w-0 p-5 sm:p-6 lg:p-7 border-b lg:border-b-0 lg:border-e border-linea">
                <h2 class="text-sm font-semibold text-tinta">Asiento {{ $aAct }}</h2>
                <p class="mt-3 text-base sm:text-lg leading-relaxed text-tinta-2 max-w-[58ch]">
                    De {{ $tramo }} se registraron
                    <b class="cifra font-semibold text-tinta">{{ $n($cb->bn) }}</b> páginas B/N,
                    @if ($vBn === null)
                        sin datos de {{ $aAnt }} para comparar.
                    @else
                        <b class="font-semibold {{ $tono($vBn) }}">{{ $vBn == 0 ? 'lo mismo' : number_format(abs($vBn), 1).'% '.($vBn > 0 ? 'más' : 'menos') }}</b>
                        que en el mismo tramo de {{ $aAnt }} (<span class="cifra">{{ $n($ca->bn) }}</span>).
                    @endif
                    @if (! $dispositivo && $crece->count() > 1)
                        @php $sube = $crece->first(); $baja = $crece->last(); @endphp
                        <b class="font-semibold text-tinta">{{ $sube->ubicacion }}</b> es la que más cambia hacia arriba
                        (<span class="cifra {{ $tono($var($sube->anterior, $sube->actual)) }}">{{ $signo($var($sube->anterior, $sube->actual)) }}</span>)
                        y <b class="font-semibold text-tinta">{{ $baja->ubicacion }}</b> la que más baja
                        (<span class="cifra {{ $tono($var($baja->anterior, $baja->actual)) }}">{{ $signo($var($baja->anterior, $baja->actual)) }}</span>).
                    @endif
                    @if ($usrTotal >= 10)
                        Los <span class="cifra">{{ $usrTop }}</span> usuarios con mayor consumo —el 10% de {{ $usrTotal }}— hacen el
                        <b class="cifra font-semibold text-tinta">{{ $usrPct }}%</b> de las páginas.
                    @endif
                </p>
                @if ($tardio || $parcial)
                    <p class="mt-4 text-xs text-tinta-3 max-w-[70ch]">
                        @foreach ($anomalias['tardias'] as [$m1, $m2])
                            {{ ucfirst($mesLargo[$m1]) }} casi no tiene registro y {{ $mesLargo[$m2] }} sale doble: el sistema anterior no leyó en {{ $mesLargo[$m1] }} y sumó ese consumo en {{ $mesLargo[$m2] }}; se leen juntos.
                        @endforeach
                        @if ($parcial) {{ ucfirst($mesLargo[$parcial]) }} {{ $aAct }} está incompleto y no entra en la comparación. @endif
                    </p>
                @endif
            </div>

            <div class="min-w-0 p-5 sm:p-6 lg:p-7">
                <table class="w-full text-sm">
                    <caption class="sr-only">Comparativo {{ $tramo }}, {{ $aAnt }} contra {{ $aAct }}</caption>
                    <thead>
                        <tr class="text-[11px] tracking-wide text-tinta-3 border-b-[3px] border-double border-tinta/25">
                            <th class="pb-2 text-left font-medium">{{ ucfirst($tramo) }}</th>
                            <th class="pb-2 text-right font-medium">{{ $aAnt }}</th>
                            <th class="pb-2 text-right font-medium">{{ $aAct }}</th>
                            <th class="pb-2 text-right font-medium w-20">Var.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-linea">
                        @foreach ([
                            ['Páginas B/N', $ca->bn, $cb->bn, true],
                            ['Impresiones', $ca->impresiones, $cb->impresiones, false],
                            ['Copias', $ca->copias, $cb->copias, false],
                            ['Promedio mensual B/N', $ca->bn / $nComp, $cb->bn / $nComp, false],
                            ['Escaneos (sin costo)', $ca->escaneos, $cb->escaneos, false],
                            ['Usuarios con consumo', $ca->usuarios, $cb->usuarios, false],
                        ] as [$k, $x, $y, $fuerte])
                            @php $v = $var($x, $y); @endphp
                            <tr class="{{ $fuerte ? 'font-semibold text-tinta' : 'text-tinta-2' }}">
                                <td class="py-2">{{ $k }}</td>
                                <td class="py-2 text-right cifra text-tinta-3 {{ $fuerte ? 'font-medium' : '' }}">{{ $n($x) }}</td>
                                <td class="py-2 text-right cifra">{{ $n($y) }}</td>
                                <td class="py-2 text-right cifra {{ $tono($v) }}">{{ $signo($v) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Mes a mes: barra del año actual sobre la pauta del año anterior --}}
        <section class="mt-6 bg-white rounded-xl shadow-hoja p-5 lg:p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                <h2 class="text-sm font-semibold text-tinta">Mes a mes</h2>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-tinta-3">
                    <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-marino"></span>{{ $aAct }}</span>
                    <span class="inline-flex items-center gap-2"><span class="w-4 h-0.5 bg-tinta-3"></span>{{ $aAnt }}, la pauta</span>
                    <span class="inline-flex items-center gap-2"><span class="w-3 h-3 rounded-sm border border-marino/50 bg-[repeating-linear-gradient(135deg,rgba(13,33,73,.35)_0_2px,transparent_2px_5px)]"></span>incompleto</span>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-12 gap-1 sm:gap-2" role="list" aria-label="Páginas B/N por mes">
                @foreach ($serie as $m => $s)
                    @php
                        $act = $s['actual']; $ant = $s['anterior'];
                        $hAct = $act ? max(1.5, $act / $techo * 100) : 0;
                        $hAnt = $ant ? $ant / $techo * 100 : null;
                        $esParcial = $m === $parcial;
                        $esTardio = isset($tardio[$m]);
                        $vm = ($esParcial || $esTardio || ! $act) ? null : $var($ant, $act);
                        $etiqueta = $mesLargo[$m].': '.($act ? $n($act).' páginas en '.$aAct : 'sin datos de '.$aAct).($ant ? ', '.$n($ant).' en '.$aAnt : '');
                    @endphp
                    <div role="listitem" title="{{ ucfirst($etiqueta) }}" aria-label="{{ ucfirst($etiqueta) }}" class="group">
                        <div class="relative h-44 sm:h-52 rounded-md group-hover:bg-papel/70 transition-colors duration-150">
                            @if ($act)
                                <div class="absolute inset-x-[18%] bottom-0 rounded-t-[3px] {{ $esParcial ? 'border border-b-0 border-marino/50 bg-[repeating-linear-gradient(135deg,rgba(13,33,73,.35)_0_2px,transparent_2px_5px)]' : ($esTardio ? 'bg-marino/55' : 'bg-marino') }}"
                                     style="height: {{ $hAct }}%"></div>
                                <div class="absolute inset-x-0 text-center text-[10px] sm:text-[11px] font-semibold cifra text-tinta hidden sm:block"
                                     style="bottom: calc({{ max($hAct, $hAnt ?? 0) }}% + 5px)">{{ number_format($act / 1000, 1) }}k</div>
                            @endif
                            @if ($hAnt !== null)
                                <div class="absolute inset-x-[6%] h-0.5 bg-tinta-3 rounded-full" style="bottom: calc({{ $hAnt }}% - 1px)"></div>
                            @endif
                        </div>
                        <div class="mt-2 text-center border-t border-tinta/20 pt-1.5">
                            <div class="text-[11px] font-medium text-tinta-2">{{ $mesCorto[$m] }}</div>
                            <div class="text-[10px] cifra {{ $tono($vm) }} hidden sm:block">{{ $vm === null ? ($ant && ! $act ? '—' : '') : $signo($vm) }}&nbsp;</div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Notas al margen, alineadas bajo sus meses --}}
            @if ($tardio || $parcial)
                <div class="mt-1 hidden sm:grid grid-cols-12 gap-2 text-[11px] text-tinta-3">
                    @foreach ($anomalias['tardias'] as [$m1, $m2])
                        @php
                            $junto = $serie[$m1]['actual'] + $serie[$m2]['actual'];
                            $juntoAnt = $serie[$m1]['anterior'] + $serie[$m2]['anterior'];
                        @endphp
                        <div style="grid-column: {{ $m1 }} / span 2" class="pt-2 border-t border-tinta-4 relative before:absolute before:left-0 before:top-[-5px] before:h-[5px] before:w-px before:bg-tinta-4 after:absolute after:right-0 after:top-[-5px] after:h-[5px] after:w-px after:bg-tinta-4">
                            <span class="font-medium text-tinta-2">Lectura tardía.</span>
                            Juntos: <span class="cifra">{{ $n($junto) }}</span> · {{ $aAnt }}: <span class="cifra">{{ $n($juntoAnt) }}</span>
                            <span class="cifra {{ $tono($var($juntoAnt, $junto)) }}">({{ $signo($var($juntoAnt, $junto)) }})</span>
                        </div>
                    @endforeach
                    @if ($parcial && ! isset($tardio[$parcial]))
                        <div style="grid-column: {{ $parcial }} / span {{ $parcial > 10 ? 13 - $parcial : 3 }}" class="pt-2 border-t border-tinta-4 relative before:absolute before:left-0 before:top-[-5px] before:h-[5px] before:w-px before:bg-tinta-4">
                            <span class="font-medium text-tinta-2">Mes en curso.</span> Lleva <span class="cifra">{{ $n($serie[$parcial]['actual']) }}</span>; no se compara.
                        </div>
                    @endif
                </div>
            @endif
        </section>

        {{-- Libro por impresora: cada celda lleva el año actual y, debajo, el anterior --}}
        <section class="mt-6 bg-white rounded-xl shadow-hoja overflow-hidden">
            <header class="px-5 pt-4 pb-3 border-b border-linea flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold text-tinta">Por impresora</h2>
                <p class="text-xs text-tinta-3">Cada celda: <span class="text-tinta font-medium">{{ $aAct }}</span> arriba, <span class="text-tinta-4">{{ $aAnt }}</span> abajo. Variación sobre {{ $tramo }}.</p>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-papel text-tinta-3 text-[11px] tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium sticky left-0 bg-papel">Impresora</th>
                            @foreach (range(1, 12) as $m) <th class="px-1.5 py-2 text-right font-medium">{{ $mesCorto[$m] }}</th> @endforeach
                            <th class="px-3 py-2 text-right font-medium border-s border-linea whitespace-nowrap">Ene–{{ $mesCorto[$nComp] }} {{ $aAct }}</th>
                            <th class="px-3 py-2 text-right font-medium">Var.</th>
                            <th class="px-4 py-2 text-right font-medium">Copias</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-linea">
                        @foreach ($impresoras as $i)
                            @php $vi = $var($i->anterior, $i->actual); @endphp
                            <tr class="hover:bg-papel/50">
                                <td class="px-4 py-2 whitespace-nowrap sticky left-0 bg-white"><x-nivel :nivel="$i->nivel" class="me-1.5" /><span class="font-semibold text-tinta">{{ $i->ubicacion }}</span></td>
                                @foreach (range(1, 12) as $m)
                                    @php
                                        $x = (int) ($i->meses[sprintf('%d-%02d', $aAct, $m)] ?? 0);
                                        $y = (int) ($i->meses[sprintf('%d-%02d', $aAnt, $m)] ?? 0);
                                        $raro = $m === $parcial || isset($tardio[$m]);
                                    @endphp
                                    <td class="px-1.5 py-1.5 text-right cifra leading-tight" style="background: rgba(13, 33, 73, {{ $raro ? 0 : round($x / $maxCelda * 0.16, 3) }})">
                                        <div class="{{ $raro ? 'text-tinta-3 italic' : 'text-tinta' }}">{{ $x ? $n($x) : '' }}</div>
                                        <div class="text-[10px] text-tinta-4">{{ $y ? $n($y) : '' }}</div>
                                    </td>
                                @endforeach
                                <td class="px-3 py-1.5 text-right cifra font-semibold text-tinta border-s border-linea">{{ $n($i->actual) }}</td>
                                <td class="px-3 py-1.5 text-right cifra {{ $tono($vi) }}">{{ $signo($vi) }}</td>
                                <td class="px-4 py-1.5 text-right">
                                    @php $pc = $i->actual ? round($i->copias / $i->actual * 100) : 0; @endphp
                                    <div class="inline-flex items-center gap-2">
                                        <span class="w-12 h-1.5 rounded-full bg-papel overflow-hidden"><span class="block h-full bg-tinta-3" style="width: {{ $pc }}%"></span></span>
                                        <span class="cifra text-tinta-2 w-8">{{ $pc }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
            {{-- Unidades: la variación crece desde el centro --}}
            <section class="bg-white rounded-xl shadow-hoja overflow-hidden">
                <header class="px-5 pt-4 pb-3 border-b border-linea">
                    <h2 class="text-sm font-semibold text-tinta">Por unidad</h2>
                    <p class="text-xs text-tinta-3">Páginas B/N de {{ $tramo }}. La barra crece a la derecha si la unidad imprime más que en {{ $aAnt }}.</p>
                </header>
                <table class="min-w-full text-sm">
                    <thead class="bg-papel text-tinta-3 text-[11px] tracking-wide">
                        <tr>
                            <th class="px-5 py-2 text-left font-medium">Unidad</th>
                            <th class="px-2 py-2 text-right font-medium">{{ $aAnt }}</th>
                            <th class="px-2 py-2 text-right font-medium">{{ $aAct }}</th>
                            <th class="px-4 py-2 font-medium w-[38%]"><span class="sr-only">Variación</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-linea">
                        @foreach ($unidades as $u)
                            @php $vu = $var($u->anterior, $u->actual); $ancho = $vu === null ? 0 : min(50, abs($vu) / 30 * 50); @endphp
                            <tr>
                                <td class="px-5 py-2">
                                    <div class="font-semibold text-tinta">{{ $u->unidad }}</div>
                                    <div class="text-[11px] text-tinta-3 leading-tight">{{ $u->nombre }} · {{ $u->usuarios }} {{ $u->usuarios === 1 ? 'usuario' : 'usuarios' }}</div>
                                </td>
                                <td class="px-2 py-2 text-right cifra text-tinta-3">{{ $n($u->anterior) }}</td>
                                <td class="px-2 py-2 text-right cifra font-semibold text-tinta">{{ $n($u->actual) }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-1 h-2" aria-hidden="true">
                                            <div class="absolute left-1/2 -top-1 -bottom-1 w-px bg-tinta/25"></div>
                                            @if ($vu !== null)
                                                <div class="absolute top-0 h-2 {{ $vu > 0 ? 'bg-amber-500 rounded-r-sm' : 'bg-marino-600 rounded-l-sm' }}"
                                                     style="{{ $vu > 0 ? 'left: 50%' : 'right: 50%' }}; width: {{ $ancho }}%"></div>
                                            @endif
                                        </div>
                                        <span class="w-14 text-right text-xs cifra font-medium {{ $tono($vu) }}">{{ $signo($vu) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            {{-- Usuarios: el peso de cada uno en el total del año --}}
            <section class="bg-white rounded-xl shadow-hoja overflow-hidden">
                <header class="px-5 pt-4 pb-3 border-b border-linea flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-tinta">Usuarios con mayor consumo</h2>
                        <p class="text-xs text-tinta-3">Todo {{ $anio ?? $aAct }}. La barra es su parte del total de páginas B/N del año.</p>
                    </div>
                    <div class="inline-flex rounded-lg bg-papel p-0.5" role="group" aria-label="Año">
                        @foreach ([$aAct, $aAnt] as $y)
                            <button type="button" wire:click="$set('anio', {{ $y }})" aria-pressed="{{ ($anio ?? $aAct) === $y ? 'true' : 'false' }}"
                                    class="h-7 px-3 rounded-md text-xs font-medium cifra transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-marino-600 {{ ($anio ?? $aAct) === $y ? 'bg-white text-tinta shadow-hoja' : 'text-tinta-3 hover:text-tinta' }}">{{ $y }}</button>
                        @endforeach
                    </div>
                </header>
                <ol class="divide-y divide-linea">
                    @foreach ($usuarios as $u)
                        @php $peso = $totalAnio ? $u->bn / $totalAnio * 100 : 0; @endphp
                        <li class="px-5 py-2 grid grid-cols-[1.5rem_1fr_auto] items-center gap-x-3">
                            <span class="text-xs cifra text-tinta-4">{{ $loop->iteration }}</span>
                            <div class="min-w-0">
                                <div class="flex items-baseline gap-2 min-w-0">
                                    <span class="truncate text-sm text-tinta">{{ $u->nombre }}</span>
                                    <span class="text-[11px] cifra text-tinta-4 shrink-0">clave {{ $u->clave }}</span>
                                </div>
                                <div class="mt-1 h-1 rounded-full bg-papel overflow-hidden"><div class="h-full bg-marino" style="width: {{ $totalAnio ? min(100, $u->bn / max(1, $usuarios->first()->bn) * 100) : 0 }}%"></div></div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm cifra font-semibold text-tinta">{{ $n($u->bn) }}</div>
                                <div class="text-[11px] cifra text-tinta-3">{{ number_format($peso, 1) }}% · {{ $n($u->escaneos) }} esc.</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
        </div>
    @endif
</div>
