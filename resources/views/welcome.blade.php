<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <header class="flex items-center gap-4 mb-10">
            <div class="h-14 w-14 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xl font-bold">SB</div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestor de Contadores de Impresión y Fotocopiado</h1>
                <p class="text-gray-500 text-sm">SENABED — Dirección de Informática y Estadística</p>
            </div>
            <div class="ml-auto">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Entrar al sistema</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Iniciar sesión</a>
                @endauth
            </div>
        </header>

        @auth
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $items = [
                        ['Dashboard General', 'Estadísticas generales de consumo.', route('dashboard')],
                        ['Tablero de Totales', 'Informes de totales y resúmenes por período.', route('tablero')],
                        ['Verificación de Carga', 'Revisa el estado de las importaciones.', route('verificacion')],
                    ];
                    if (auth()->user()->isAdmin()) {
                        $items[] = ['Carga de Contadores', 'Sube archivos CSV de contadores.', route('carga')];
                        $items[] = ['Configuración', 'Ajusta el precio unitario.', route('configuracion')];
                    }
                @endphp
                @foreach ($items as [$titulo, $desc, $url])
                    <a href="{{ $url }}" class="block bg-white border border-gray-200 rounded-xl shadow-2xs p-5 hover:shadow-md hover:border-blue-300 transition">
                        <h3 class="font-semibold text-gray-800">{{ $titulo }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $desc }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-600">
                Inicia sesión para acceder al panel de consumo de impresión y fotocopiado.
            </div>
        @endauth

        <footer class="mt-12 text-center text-xs text-gray-400">
            Dirección de Informática y Estadística — SENABED — {{ date('Y') }}
        </footer>
    </div>
</body>
</html>
