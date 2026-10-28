{{-- Campo de formulario: etiqueta, control (slot), ayuda y error de validación. --}}
@props(['label', 'nombre' => null, 'ayuda' => null])

<div {{ $attributes }}>
    <label class="block text-xs font-medium text-tinta-2 mb-1.5">{{ $label }}</label>
    {{ $slot }}
    @if ($ayuda) <p class="text-[11px] text-tinta-3 mt-1">{{ $ayuda }}</p> @endif
    @if ($nombre) @error($nombre) <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
</div>
