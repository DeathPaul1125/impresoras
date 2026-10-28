<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Acceso · Contadores SENABED</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    {{-- La mesa: marino institucional con la pauta tenue de un cuaderno de registro. --}}
    <body class="font-sans antialiased min-h-full bg-marino text-tinta
                 bg-[linear-gradient(rgba(255,255,255,.045)_1px,transparent_1px)] bg-[size:100%_30px]">
        <main class="min-h-screen flex flex-col items-center justify-center px-4 py-10 sm:py-16">
            {{-- El oficio --}}
            <article x-data="{ sacudir: false }" x-on:oficio-error.window="sacudir = false; $nextTick(() => sacudir = true)"
                     :class="sacudir && 'sacudir'"
                     class="relative w-full max-w-[640px] bg-bond rounded-[3px] px-6 py-8 sm:px-14 sm:py-12
                            shadow-[0_1px_0_rgba(255,255,255,.6)_inset,0_24px_60px_-12px_rgba(0,0,0,.55),0_8px_18px_-8px_rgba(0,0,0,.35)]">
                {{-- Membrete --}}
                <header>
                    <img src="{{ asset('img/logo-senabed-die.png') }}" alt="Secretaría Nacional de Administración de Bienes en Extinción de Dominio · Dirección de Informática y Estadística"
                         class="w-full max-w-[400px] h-auto select-none" draggable="false">
                    <div class="mt-4 h-[3px] bg-cian"></div>
                    <div class="mt-3 flex flex-wrap justify-between gap-x-6 gap-y-1 font-oficio font-semibold text-[14px] text-tinta-2 cifra">
                        <span>Ref.: DIE · Sistema de Contadores</span>
                        <span>Guatemala, {{ now()->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</span>
                    </div>
                </header>

                <div class="mt-10">
                    {{ $slot }}
                </div>
            </article>

            <p class="mt-6 text-xs text-[#a8b8da] text-center">
                Uso exclusivo de la red interna de SENABED · Dirección de Informática y Estadística
            </p>
        </main>
    </body>
</html>
