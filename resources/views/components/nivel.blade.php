{{-- Chip de piso/nivel del equipo (1, 7.1, 9.2...): identifica cada impresora en todo el sistema. --}}
@props(['nivel' => null])

@if ($nivel !== null && $nivel !== '')
    <span {{ $attributes->merge(['class' => 'cifra inline-flex items-center justify-center min-w-[2.25rem] h-5 px-1.5 rounded bg-marino text-[10px] font-semibold text-white tracking-wide']) }}
          title="Nivel {{ $nivel }}">N{{ $nivel }}</span>
@endif
