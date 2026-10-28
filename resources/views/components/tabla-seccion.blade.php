@props(['titulo', 'export' => null, 'pdf' => null, 'descripcion' => null])

<section {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-hoja overflow-hidden mb-6']) }}>
    <header class="flex flex-wrap items-center justify-between gap-2 px-5 py-3.5 border-b border-linea">
        <div>
            <h2 class="text-sm font-semibold text-tinta">{{ $titulo }}</h2>
            @if ($descripcion) <p class="text-xs text-tinta-3">{{ $descripcion }}</p> @endif
        </div>
        <div class="flex gap-1.5">
            @if ($export)
                <a href="{{ $export }}" class="h-8 px-2.5 inline-flex items-center gap-1.5 text-xs font-medium rounded-md text-saldo hover:bg-saldo-50 ring-1 ring-inset ring-saldo/25">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
            @endif
            @if ($pdf)
                <a href="{{ $pdf }}" class="h-8 px-2.5 inline-flex items-center gap-1.5 text-xs font-medium rounded-md text-red-700 hover:bg-red-50 ring-1 ring-inset ring-red-700/20">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            @endif
        </div>
    </header>
    <div class="overflow-x-auto [&_thead]:bg-papel [&_thead]:text-tinta-3 [&_th]:font-medium [&_th]:text-[11px] [&_th]:tracking-wide [&_tbody]:divide-linea [&_td]:text-tinta-2">
        {{ $slot }}
    </div>
</section>
