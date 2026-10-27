@props(['titulo', 'valor', 'icon' => 'fa-chart-simple', 'color' => 'blue'])

@php
    $map = [
        'blue' => 'bg-blue-50 text-blue-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'gray' => 'bg-slate-100 text-slate-500',
        'purple' => 'bg-violet-50 text-violet-600',
        'red' => 'bg-rose-50 text-rose-600',
        'orange' => 'bg-amber-50 text-amber-600',
    ];
    $chip = $map[$color] ?? $map['blue'];
@endphp

<div class="relative bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow p-4">
    <span class="absolute top-3 end-3 inline-flex items-center justify-center size-8 rounded-lg {{ $chip }}">
        <i class="fas {{ $icon }} text-sm"></i>
    </span>
    <p class="text-[11px] text-gray-500 uppercase tracking-wide pe-9 leading-tight">{{ $titulo }}</p>
    <p class="mt-2 text-lg xl:text-xl font-bold text-gray-800 whitespace-nowrap">{{ $valor }}</p>
</div>
