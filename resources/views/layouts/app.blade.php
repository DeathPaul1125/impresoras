<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script>
            window.senabedChartPlugins = [{
                id: 'centro',
                afterDraw(chart, args, o) {
                    if (!o || !o.valor) return;
                    const { ctx, chartArea: a } = chart, x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
                    ctx.save(); ctx.textAlign = 'center'; ctx.fillStyle = '#0f172a';
                    ctx.font = '700 22px Figtree, sans-serif'; ctx.fillText(o.valor, x, y + 2);
                    ctx.font = '600 10px Figtree, sans-serif'; ctx.fillStyle = '#475569';
                    (o.texto || '').split('\n').forEach((t, i) => ctx.fillText(t, x, y + 18 + i * 12));
                    ctx.restore();
                },
            }, {
                id: 'etiquetas',
                afterDatasetsDraw(chart, args, o) {
                    if (!o) return;
                    const { ctx } = chart;
                    ctx.save(); ctx.font = '700 11px Figtree, sans-serif'; ctx.textAlign = 'center';
                    chart.data.datasets.forEach((ds, i) => !ds.sinEtiquetas && chart.isDatasetVisible(i) && chart.getDatasetMeta(i).data.forEach((el, j) => {
                        const v = ds.data[j];
                        if (v === null || v === undefined) return;
                        if (chart.config.type === 'doughnut') {
                            const total = ds.data.reduce((s, n) => s + (n || 0), 0), pct = total ? v / total * 100 : 0;
                            if (pct < 3) return;
                            const p = el.tooltipPosition(); ctx.fillStyle = '#fff'; ctx.fillText(pct.toFixed(1) + '%', p.x, p.y + 4);
                        } else {
                            ctx.fillStyle = ds.borderColor || '#1e3a8a'; ctx.fillText(Number(v).toLocaleString('es-GT'), el.x, el.y - 10);
                        }
                    }));
                    ctx.restore();
                },
            }];
        </script>
    </head>
    <body class="font-sans antialiased h-full bg-papel text-tinta">
        @php
            $nav = [
                ['route' => 'dashboard', 'label' => 'Dashboard del contrato', 'icon' => 'fa-chart-line', 'admin' => false],
                ['route' => 'tablero', 'label' => 'Análisis de consumo', 'icon' => 'fa-table-cells', 'admin' => false],
                ['route' => 'historico', 'label' => 'Histórico', 'icon' => 'fa-clock-rotate-left', 'admin' => false],
                ['route' => 'impresoras', 'label' => 'Impresoras', 'icon' => 'fa-print', 'admin' => true],
                ['route' => 'trabajos', 'label' => 'Registro de trabajos', 'icon' => 'fa-list-check', 'admin' => true],
                ['route' => 'cortes', 'label' => 'Cortes del contrato', 'icon' => 'fa-scissors', 'admin' => true],
                ['route' => 'configuracion', 'label' => 'Configuración', 'icon' => 'fa-gear', 'admin' => true],
            ];
            $seccion = collect($nav)->firstWhere('route', request()->route()?->getName())['label'] ?? config('app.name');
        @endphp

        <div x-data="{ sidebar: false, userMenu: false }" class="min-h-full">
            {{-- ===== Sidebar ===== --}}
            <aside class="fixed inset-y-0 start-0 z-50 w-64 text-slate-300 bg-marino transform transition-transform duration-200 ease-out lg:translate-x-0"
                   :class="sidebar ? 'translate-x-0' : '-translate-x-full'">
                <div class="flex flex-col h-full">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 px-6 h-16 border-b border-white/10">
                        <span class="inline-flex items-center justify-center size-9 rounded-lg bg-white/10 ring-1 ring-white/15 text-white"><i class="fas fa-print"></i></span>
                        <div class="leading-tight">
                            <span class="block text-sm font-semibold text-white tracking-tight">Contadores</span>
                            <span class="block text-[11px] text-slate-400">SENABED · Multifuncionales</span>
                        </div>
                    </a>

                    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                        <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Menú</p>
                        @foreach ($nav as $item)
                            @if (! $item['admin'] || auth()->user()->isAdmin())
                                @php $activo = request()->routeIs($item['route']); @endphp
                                <a href="{{ route($item['route']) }}" wire:navigate @click="sidebar = false"
                                   @if ($activo) aria-current="page" @endif
                                   class="group relative flex items-center gap-x-3 py-2.5 px-3 rounded-lg text-sm font-medium transition-colors
                                          {{ $activo
                                              ? 'bg-white/10 text-white before:absolute before:start-0 before:inset-y-2 before:w-1 before:rounded-full before:bg-sky-400'
                                              : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <i class="fas {{ $item['icon'] }} w-4 text-center {{ $activo ? 'text-sky-300' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </nav>

                    <div class="p-4 border-t border-white/10 text-[11px] text-slate-500">
                        Informática y Estadística · {{ date('Y') }}
                    </div>
                </div>
            </aside>

            {{-- Fondo oscuro en móvil --}}
            <div x-show="sidebar" x-transition.opacity @click="sidebar = false"
                 class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" style="display:none"></div>

            {{-- ===== Topbar ===== --}}
            <header class="sticky top-0 inset-x-0 z-30 flex items-center gap-3 bg-white/90 backdrop-blur border-b border-linea px-4 sm:px-6 h-14 lg:ps-[17.5rem]">
                <button type="button" @click="sidebar = true"
                        class="lg:hidden inline-flex items-center justify-center size-9 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100"
                        aria-label="Menú">
                    <i class="fas fa-bars"></i>
                </button>

                <h1 class="text-sm font-semibold text-tinta">{{ $seccion }}</h1>
                @php $contrato = \App\Models\Configuracion::contrato(); @endphp
                @if ($contrato['numero'])
                    <span class="hidden md:inline-flex items-center gap-1.5 py-0.5 px-2 rounded-md bg-marino-50 text-[11px] font-medium text-marino">
                        <i class="fas fa-file-contract"></i> Contrato {{ $contrato['numero'] }} · bolsón {{ number_format($contrato['bolson']) }}
                    </span>
                @endif

                {{-- Menú de usuario --}}
                <div class="ms-auto relative" @click.outside="userMenu = false">
                    <button type="button" @click="userMenu = !userMenu"
                            class="inline-flex items-center gap-x-2 py-1.5 px-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-100">
                        <span class="inline-flex items-center justify-center size-8 rounded-full bg-marino text-white text-xs font-semibold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                    </button>

                    <div x-show="userMenu" x-transition style="display:none"
                         class="absolute end-0 mt-2 min-w-56 bg-white border border-gray-200 rounded-xl shadow-lg p-1 z-50">
                        <div class="px-3 py-2 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                            <span class="mt-1 inline-flex items-center py-0.5 px-1.5 rounded-full text-[10px] font-medium {{ auth()->user()->isAdmin() ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        </div>
                        <a href="{{ route('profile') }}" wire:navigate
                           class="flex items-center gap-x-2 py-2 px-3 rounded-lg text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user text-gray-400"></i> Perfil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-x-2 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-right-from-bracket text-red-400"></i> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- ===== Contenido ===== --}}
            <div class="lg:ps-64">
                @isset($header)
                    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">{{ $header }}</div>
                @endisset
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
