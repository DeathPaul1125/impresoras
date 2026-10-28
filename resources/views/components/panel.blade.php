{{-- Hoja/sección: título, descripción opcional y acciones a la derecha. --}}
@props(['titulo' => null, 'descripcion' => null, 'acciones' => null, 'padding' => true])

<section {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-hoja']) }}>
    @if ($titulo || $acciones)
        <header class="flex flex-wrap items-start justify-between gap-3 px-5 pt-4 {{ $padding ? '' : 'pb-4 border-b border-linea' }}">
            <div class="min-w-0">
                @if ($titulo) <h2 class="text-sm font-semibold text-tinta">{{ $titulo }}</h2> @endif
                @if ($descripcion) <p class="text-xs text-tinta-3 mt-0.5">{{ $descripcion }}</p> @endif
            </div>
            @if ($acciones) <div class="flex flex-wrap items-center gap-2">{{ $acciones }}</div> @endif
        </header>
    @endif
    <div class="{{ $padding ? 'p-5' : '' }}">{{ $slot }}</div>
</section>
