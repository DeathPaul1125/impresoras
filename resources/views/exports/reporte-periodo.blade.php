<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 30px 36px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 8.5px; color: #1f2937; }
        .cab { background: #0d2149; color: #fff; padding: 10px 14px; }
        .cab img { height: 34px; background: #fff; padding: 2px; border-radius: 3px; vertical-align: middle; }
        .cab .org { display: inline-block; vertical-align: middle; margin-left: 10px; font-size: 8px; letter-spacing: 1px; color: #c7d2fe; text-transform: uppercase; }
        h1 { font-family: DejaVu Serif, serif; font-size: 17px; color: #0d2149; margin: 12px 0 2px; }
        .sub { color: #64748b; font-size: 9px; margin-bottom: 10px; }
        h2 { font-family: DejaVu Serif, serif; font-size: 11.5px; color: #0d2149; margin: 16px 0 5px; padding-bottom: 2px; border-bottom: 1.5px solid #dbe1ea; }
        table { width: 100%; border-collapse: collapse; }
        tr { page-break-inside: avoid; }
        th { background: #0d2149; color: #fff; text-align: left; font-size: 7.5px; padding: 4px 5px; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e9f0; padding: 3.5px 5px; }
        tr:nth-child(even) td { background: #f8fafc; }
        td.num { text-align: right; }
        .kpi td { border: 1px solid #e5e9f0; background: #f6f8fb; padding: 7px 8px; width: 25%; vertical-align: top; }
        .kpi .k { font-size: 7.5px; color: #64748b; text-transform: uppercase; }
        .kpi .v { font-size: 15px; font-weight: bold; color: #0d2149; }
        .aviso { padding: 5px 8px; margin-bottom: 3px; border-left: 3px solid #d97706; background: #fffbeb; }
        .aviso.grave { border-color: #b91c1c; background: #fef2f2; }
        .ok { padding: 5px 8px; background: #ecfdf5; color: #047857; }
        .pie { position: fixed; bottom: -22px; left: 0; right: 0; font-size: 7px; color: #94a3b8; }
    </style>
</head>
<body>
    @php
        $t = $datos['totales'];
        $n = fn ($v) => number_format((int) $v);
        $var = fn ($v) => $v === null ? 'sin dato anterior' : (($v > 0 ? '+' : '').$v.'% vs. '.$datos['anterior']['etiqueta']);
    @endphp
    <div class="pie">SENABED · {{ $titulo }} · generado {{ now()->format('d/m/Y H:i') }} · el registro completo de trabajos va en el Excel</div>

    <div class="cab">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logo-senabed-die.png'))) }}" alt="">
        <span class="org">SENABED · Servicios de Impresión y Fotocopiado</span>
    </div>
    <h1>{{ $titulo }}</h1>
    <div class="sub">Consumo de las impresoras · lecturas por SNMP y del panel de las impresoras</div>

    <table class="kpi">
        <tr>
            <td><div class="k">Páginas</div><div class="v">{{ $n($t['total']) }}</div>{{ $var($datos['variacion']['total']) }}</td>
            <td><div class="k">Costo</div><div class="v">Q{{ number_format($t['costo_total'], 2) }}</div>{{ $var($datos['variacion']['costo_total']) }}</td>
            <td><div class="k">Impresiones / copias</div><div class="v">{{ $n($t['impresiones']) }} / {{ $n($t['copias']) }}</div>{{ $n($t['escaneos']) }} escaneos (sin costo)</td>
            <td><div class="k">Trabajos</div><div class="v">{{ $n($t['trabajos']) }}</div>{{ $n($t['usuarios']) }} usuarios · hora pico {{ $datos['hora_pico'] ?? '—' }}</td>
        </tr>
    </table>

    @if ($b = $datos['bolson'])
        <p style="margin: 8px 0 0;"><b>Bolsón del contrato {{ $b['contrato'] }}:</b> {{ $n($b['acumulado']) }} de {{ $n($b['bolson']) }} páginas ({{ $b['porcentaje'] }}%) · quedan {{ $n($b['disponible']) }}@if ($b['agotamiento']) · se agota cerca del {{ $b['agotamiento']->format('d/m/Y') }}@endif.</p>
    @endif

    <h2>Atención de equipos</h2>
    @forelse ($datos['atencion'] as $a)
        <div class="aviso {{ $a['grave'] ? 'grave' : '' }}"><b>{{ $a['impresora'] }}</b> · {{ $a['texto'] }}@if ($a['accion']) — {{ $a['accion'] }}@endif</div>
    @empty
        <div class="ok">Todos los equipos funcionan con normalidad.</div>
    @endforelse

    @foreach ($hojas as $h)
        @continue(in_array($h['titulo'], ['Resumen', 'Atención de equipos'], true))
        <h2>{{ $h['titulo'] }}</h2>
        <table>
            <thead><tr>@foreach ($h['headings'] as $c) <th>{{ $c }}</th> @endforeach</tr></thead>
            <tbody>
                @forelse ($h['rows'] as $fila)
                    <tr @if (($fila[0] ?? null) === 'TOTAL') style="font-weight: bold;" @endif>
                        @foreach ($fila as $v)
                            <td class="{{ is_int($v) || is_float($v) ? 'num' : '' }}">{{ is_int($v) ? number_format($v) : (is_float($v) ? number_format($v, 2) : $v) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($h['headings']) }}" style="color: #94a3b8;">Sin datos en el período.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
