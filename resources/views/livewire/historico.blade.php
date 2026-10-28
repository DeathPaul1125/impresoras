@php
    $n = fn ($v) => number_format((int) $v);
    $var = fn ($a, $b) => $a > 0 ? round(($b - $a) / $a * 100, 1) : null;
    $chip = function ($v) {
        if ($v === null) return '<span class="text-tinta-4">—</span>';
        $c = $v > 0 ? 'text-amber-700' : 'text-saldo';
        return '<span class="cifra font-semibold whitespace-nowrap '.$c.'">'.($v > 0 ? '▲ +' : ($v < 0 ? '▼ ' : '')).$v.'%</span>';
    };
    [$aAnt, $aAct] = $anios;
    $nombresMes = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $hasta = $nombresMes[$comp['meses'] - 1];
    $ctrl = 'block w-full py-2 px-3 rounded-lg border-0 bg-papel ring-1 ring-inset ring-linea text-sm text-tinta focus:ring-2 focus:ring-marino-600';
    $serie = fn ($anio) => collect(range(1, 12))->map(fn ($m) => (int) ($meses[sprintf('%d-%02d', $anio, $m)]->bn ?? 0) ?: null)->all();
    $pico = $meses->filter(fn ($m) => str_starts_with($m->periodo, (string) $aAct))->sortByDesc('bn')->first();
    $todos = $meses->keys();
    $maxCelda = max(1, $impresoras->flatMap(fn ($i) => $i->meses->values())->max());
@endphp

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-tinta">Histórico</h1>
            <p class="text-sm text-tinta-3">Consumo mes a mes desde {{ $nombresMes[0] }} {{ $aAnt }}, por impresora, unidad y usuario (contabilidad de trabajos del sistema anterior). B/N = impresiones + copias.</p>
        </div>
        <div class="flex items-end gap-2">
            <select wire:model.live="dispositivo" class="{{ $ctrl }} w-52" aria-label="Impresora">
                <option value="">Todas las impresoras</option>
                @foreach ($dispositivos as $d) <option value="{{ $d->id }}">{{ $d->ubicacion }}</option> @endforeach
            </select>
            <a href="{{ route('export.excel', ['tipo' => 'historia', 'dispositivo' => $dispositivo ?: null]) }}" class="h-9 px-3 inline-flex items-center gap-1.5 text-sm font-medium rounded-lg text-saldo bg-white hover:bg-saldo-50 ring-1 ring-inset ring-saldo/25 whitespace-nowrap">
                <i class="fas fa-file-excel"></i> Excel completo
            </a>
        </div>
    </div>

    @if (! $h->hayDatos())
        <x-panel>
            <p class="text-sm text-tinta-2">Aún no se ha importado la historia. En el servidor ejecute <code class="cifra bg-papel px-1.5 py-0.5 rounded">php artisan impresoras:importar-historia --desde=2025-01</code> (lee del sistema anterior; tarda menos de un minuto).</p>
        </x-panel>
    @else
        {{-- Indicadores: mismo tramo de meses en ambos años --}}
        @php $ca = $comp['anterior']; $cb = $comp['actual']; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            @foreach ([
                ['B/N '.$aAct.' (ene–'.mb_strtolower($hasta).')', $n($cb->bn), $chip($var($ca->bn, $cb->bn)).' vs. '.$n($ca->bn).' en '.$aAnt],
                ['Promedio mensual '.$aAct, $n($cb->bn / $comp['meses']), $n($ca->bn / $comp['meses']).' en '.$aAnt],
                ['Escaneos '.$aAct, $n($cb->escaneos), $chip($var($ca->escaneos, $cb->escaneos)).' vs. '.$aAnt],
                ['Usuarios con consumo', $n($cb->usuarios), $n($ca->usuarios).' en '.$aAnt.($pico ? ' · mes pico: '.$nombresMes[(int) substr($pico->periodo, 5) - 1] : '')],
            ] as [$k, $v, $sub])
                <div class="bg-white rounded-xl shadow-hoja px-4 py-3">
                    <div class="text-xs text-tinta-3">{{ $k }}</div>
                    <div class="mt-1 text-2xl font-semibold text-tinta cifra">{{ $v }}</div>
                    <div class="text-xs text-tinta-3 mt-0.5">{!! $sub !!}</div>
                </div>
            @endforeach
        </div>

        {{-- Mes a mes, año contra año --}}
        <section class="bg-white rounded-xl shadow-hoja p-5 mb-6">
            <h2 class="text-sm font-semibold text-tinta">Páginas B/N por mes · {{ $aAnt }} vs. {{ $aAct }}</h2>
            <p class="text-xs text-tinta-3 mb-3">Marzo {{ $aAct }} casi no tiene registro y abril sale doble: el sistema anterior no leyó en marzo y sumó ese consumo en la lectura de abril. Octubre {{ $aAct }} está incompleto (el sistema anterior dejó de leer a inicios de mes).</p>
            @include('livewire.partials.chart', ['alto' => 280, 'config' => [
                'type' => 'bar',
                'data' => ['labels' => $nombresMes, 'datasets' => [
                    ['label' => (string) $aAnt, 'data' => $serie($aAnt), 'backgroundColor' => '#94a3b8', 'borderRadius' => 3],
                    ['label' => (string) $aAct, 'data' => $serie($aAct), 'backgroundColor' => '#0d2149', 'borderRadius' => 3],
                ]],
                'options' => ['interaction' => ['mode' => 'index', 'intersect' => false],
                    'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]],
                    'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => '#eef1f6']], 'x' => ['grid' => ['display' => false]]]],
            ]])
        </section>

        {{-- Impresora × mes --}}
        <x-tabla-seccion titulo="Impresoras mes a mes" :descripcion="'Páginas B/N. Variación = ene–'.mb_strtolower($hasta).' '.$aAct.' contra los mismos meses de '.$aAnt.'.'">
            <table class="min-w-full text-xs">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left sticky left-0 bg-papel">Impresora</th>
                        @foreach ($todos as $p)
                            <th class="px-2 py-2 text-right whitespace-nowrap {{ str_ends_with($p, '-01') ? 'border-l border-linea' : '' }}">{{ $nombresMes[(int) substr($p, 5) - 1] }}<div class="text-[10px] text-tinta-4">{{ substr($p, 0, 4) }}</div></th>
                        @endforeach
                        <th class="px-3 py-2 text-right border-l border-linea">Var.</th>
                        <th class="px-3 py-2 text-right">% copias</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($impresoras as $i)
                        <tr>
                            <td class="px-4 py-1.5 font-semibold text-tinta whitespace-nowrap sticky left-0 bg-white"><x-nivel :nivel="$i->nivel" class="me-1" />{{ $i->ubicacion }}</td>
                            @foreach ($todos as $p)
                                @php $v = (int) ($i->meses[$p] ?? 0); @endphp
                                <td class="px-2 py-1.5 text-right cifra {{ str_ends_with($p, '-01') ? 'border-l border-linea' : '' }}" style="background: rgba(13, 33, 73, {{ round($v / $maxCelda * 0.35, 3) }})">{{ $v ? $n($v) : '' }}</td>
                            @endforeach
                            <td class="px-3 py-1.5 text-right border-l border-linea">{!! $chip($var($i->anterior, $i->actual)) !!}</td>
                            <td class="px-3 py-1.5 text-right cifra">{{ $i->actual ? round($i->copias / $i->actual * 100) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-tabla-seccion>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Unidades --}}
            <x-tabla-seccion titulo="Unidades" :descripcion="'Páginas B/N ene–'.mb_strtolower($hasta).', '.$aAnt.' contra '.$aAct.'.'" class="!mb-0">
                <table class="min-w-full text-sm">
                    <thead class="text-xs">
                        <tr><th class="px-4 py-2 text-left">Unidad</th><th class="px-3 py-2 text-right">{{ $aAnt }}</th><th class="px-3 py-2 text-right">{{ $aAct }}</th><th class="px-3 py-2 text-right">Var.</th><th class="px-4 py-2 text-right">Usuarios</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($unidades as $u)
                            <tr>
                                <td class="px-4 py-1.5"><span class="font-semibold text-tinta">{{ $u->unidad }}</span> <span class="text-xs text-tinta-3">{{ $u->nombre }}</span></td>
                                <td class="px-3 py-1.5 text-right cifra">{{ $n($u->anterior) }}</td>
                                <td class="px-3 py-1.5 text-right cifra font-semibold text-tinta">{{ $n($u->actual) }}</td>
                                <td class="px-3 py-1.5 text-right">{!! $chip($var($u->anterior, $u->actual)) !!}</td>
                                <td class="px-4 py-1.5 text-right cifra">{{ $u->usuarios }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tabla-seccion>

            {{-- Usuarios --}}
            <x-tabla-seccion titulo="Usuarios con mayor consumo" :descripcion="'Páginas B/N de todo '.($anio ?? $aAct).'.'" class="!mb-0">
                <div class="px-5 pt-3 flex gap-1.5">
                    @foreach ([$aAct, $aAnt] as $y)
                        <button type="button" wire:click="$set('anio', {{ $y }})" class="h-7 px-2.5 rounded-md text-xs font-medium {{ ($anio ?? $aAct) === $y ? 'bg-marino text-white' : 'bg-papel text-tinta-2 hover:bg-marino-50' }}">{{ $y }}</button>
                    @endforeach
                </div>
                <table class="min-w-full text-sm">
                    <thead class="text-xs">
                        <tr><th class="px-4 py-2 text-left">#</th><th class="px-3 py-2 text-left">Usuario</th><th class="px-3 py-2 text-right">B/N</th><th class="px-3 py-2 text-right">Escaneos</th><th class="px-4 py-2 text-right">Meses</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($usuarios as $u)
                            <tr>
                                <td class="px-4 py-1.5 text-tinta-4 cifra">{{ $loop->iteration }}</td>
                                <td class="px-3 py-1.5 text-tinta">{{ $u->nombre }} <span class="text-xs text-tinta-4 cifra">{{ $u->clave }}</span></td>
                                <td class="px-3 py-1.5 text-right cifra font-semibold text-tinta">{{ $n($u->bn) }}</td>
                                <td class="px-3 py-1.5 text-right cifra">{{ $n($u->escaneos) }}</td>
                                <td class="px-4 py-1.5 text-right cifra">{{ $u->meses }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tabla-seccion>
        </div>
    @endif
</div>
