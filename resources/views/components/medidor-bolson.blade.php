{{-- Medidor del bolsón: consumido vs disponible, con la marca de dónde debería ir según el tiempo transcurrido del contrato. --}}
@props(['r'])

@php
    $pct = min(100, (float) $r['porcentaje']);
    $tiempo = ($r['inicio'] && $r['fin'] && $r['corte_al'])
        ? min(100, max(0, round($r['inicio']->diffInDays($r['corte_al']) / max(1, $r['inicio']->diffInDays($r['fin'])) * 100, 1)))
        : null;
    $adelantado = $tiempo !== null && $pct > $tiempo;
@endphp

<div {{ $attributes }}>
    <div class="relative h-3 rounded-full bg-saldo-50 ring-1 ring-inset ring-saldo/20">
        <div class="absolute inset-y-0 start-0 rounded-full {{ $pct >= 90 ? 'bg-red-600' : 'bg-marino-600' }}" style="width: {{ $pct }}%"></div>
        @if ($tiempo !== null)
            <div class="absolute -top-1 -bottom-1 w-0.5 bg-tinta" style="left: {{ $tiempo }}%" title="Tiempo transcurrido del contrato: {{ $tiempo }}%"></div>
        @endif
    </div>
    <div class="mt-1.5 flex justify-between text-xs text-tinta-3 cifra">
        <span><b class="text-tinta">{{ $r['porcentaje'] }}%</b> consumido</span>
        <span><b class="text-saldo">{{ number_format($r['disponible']) }}</b> disponibles</span>
    </div>
    @if ($tiempo !== null)
        <p class="mt-1 text-xs {{ $adelantado ? 'text-amber-700' : 'text-saldo' }}">
            <i class="fas {{ $adelantado ? 'fa-gauge-high' : 'fa-gauge' }}"></i>
            Ha pasado el {{ $tiempo }}% del plazo (línea negra): {{ $adelantado ? 'el consumo va adelantado.' : 'el consumo va dentro del ritmo.' }}
        </p>
    @endif
</div>
