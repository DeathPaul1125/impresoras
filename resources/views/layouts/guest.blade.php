<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Iniciar sesión · {{ config('app.name', 'Contadores SENABED') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased h-full bg-papel text-tinta">
        <div class="min-h-full grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            {{-- Panel institucional --}}
            <aside class="hidden lg:flex flex-col justify-between bg-marino text-white px-12 py-10">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center size-10 rounded-lg bg-white/10 ring-1 ring-white/15"><i class="fas fa-print"></i></span>
                    <div class="leading-tight">
                        <div class="font-semibold tracking-tight">Contadores</div>
                        <div class="text-xs text-slate-400">SENABED · Multifuncionales</div>
                    </div>
                </div>

                <div class="max-w-md">
                    <h1 class="text-3xl font-bold tracking-tight leading-tight">Consumo de copias e impresiones</h1>
                    <p class="mt-3 text-slate-300">Control del contrato de impresión: lecturas automáticas de cada multifuncional, cortes mensuales y reportes por correo.</p>

                    <ul class="mt-8 space-y-4 text-sm">
                        @foreach ([
                            ['fa-satellite-dish', 'Lecturas diarias', 'Los contadores de las 7 impresoras se leen solos cada día a las 6 pm.'],
                            ['fa-layer-group', 'Bolsón del contrato', 'Consumo acumulado, saldo disponible y proyección de agotamiento.'],
                            ['fa-envelope-open-text', 'Reportes automáticos', 'Diario, semanal y de corte, con Excel y PDF.'],
                        ] as [$icono, $titulo, $texto])
                            <li class="flex gap-3">
                                <span class="shrink-0 inline-flex items-center justify-center size-9 rounded-lg bg-white/10 text-sky-300"><i class="fas {{ $icono }}"></i></span>
                                <div>
                                    <div class="font-semibold">{{ $titulo }}</div>
                                    <div class="text-slate-400">{{ $texto }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="text-xs text-slate-500">Dirección de Informática y Estadística · {{ date('Y') }}</div>
            </aside>

            {{-- Formulario --}}
            <main class="flex flex-col justify-center px-6 py-12 sm:px-12">
                <div class="w-full max-w-sm mx-auto">
                    <div class="lg:hidden flex items-center gap-3 mb-10">
                        <span class="inline-flex items-center justify-center size-10 rounded-lg bg-marino text-white"><i class="fas fa-print"></i></span>
                        <div class="leading-tight">
                            <div class="font-semibold">Contadores</div>
                            <div class="text-xs text-tinta-3">SENABED · Multifuncionales</div>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
