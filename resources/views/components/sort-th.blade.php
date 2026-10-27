@props(['campo', 'sort_field', 'sort_order'])

@php
    $activo = $sort_field === $campo;
    $flecha = ! $activo ? '↕' : ($sort_order === 'ASC' ? '↑' : '↓');
@endphp

<th scope="col"
    class="px-6 py-3 text-end text-xs font-medium uppercase cursor-pointer select-none hover:text-blue-600 {{ $activo ? 'text-blue-600' : 'text-gray-500' }}"
    wire:click="ordenar('{{ $campo }}')">
    {{ $slot }} <span class="text-xs">{{ $flecha }}</span>
</th>
