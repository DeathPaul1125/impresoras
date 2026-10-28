<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #1f2937; margin: 0; }
        .banda { background: #0d2149; color: #fff; padding: 10px 14px; }
        .banda h1 { margin: 0; font-size: 17px; }
        .banda div { font-size: 10px; color: #c7d2fe; }
        h2 { background: #0d2149; color: #fff; font-size: 10px; padding: 5px 8px; margin: 12px 0 0; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 3px 5px; }
        th { background: #e0e7ff; color: #0d2149; font-size: 8px; }
        td.n { text-align: right; }
        tr.total td { background: #0d2149; color: #fff; font-weight: bold; }
        tr.saldo td { background: #dcfce7; font-weight: bold; }
        .kpis td { border: 1px solid #e5e7eb; text-align: center; padding: 6px 2px; width: 12.5%; }
        .kpis .t { font-size: 7px; font-weight: bold; text-transform: uppercase; color: #1e3a8a; }
        .kpis .v { font-size: 14px; font-weight: bold; color: #0d2149; }
        .barra { background: #1d4ed8; height: 9px; display: inline-block; }
        .alerta { background: #fef3c7; border: 1px solid #fcd34d; padding: 4px 6px; margin-top: 6px; }
        .hall td { border: none; vertical-align: top; padding: 4px 8px; width: 25%; }
        .hall b { color: #1e3a8a; }
        .sd { color: #dc2626; }
    </style>
</head>
<body>
    @php
        $n = fn ($v) => $v === null ? 's/d' : number_format((int) $v);
        $f = fn ($d) => $d?->format('d/m/Y');
        $p = $r['periodos'];
        $ultimo = $p->last();
        $max = max(1, (int) $r['por_equipo']->max('acumulado'));
    @endphp

    <div class="banda">
        <h1>CONSUMO DE COPIAS E IMPRESIONES · CONTRATO {{ $r['contrato'] }}</h1>
        <div>Consumo acumulado al {{ $r['corte_al']?->format('d/m/Y H:i') }} Hrs. · Vigencia {{ $f($r['inicio']) }} – {{ $f($r['fin']) }} · Generado {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="kpis" style="margin-top: 8px;">
        <tr>
            @foreach ([
                ['Impresión mensual aprox.', $n($r['mensual_aprox'])],
                ['Consumo promedio / mes', $n($r['promedio_mensual'])],
                ['Consumo acumulado', $n($r['acumulado'])],
                ['Bolsón B/N total', $n($r['bolson'])],
                ['Bolsón disponible', $n($r['disponible'])],
                ['% consumo del bolsón', $r['porcentaje'].'%'],
                ['Producido '.($ultimo?->etiqueta ?? ''), $n($ultimo?->total)],
                ['Se agota (proyección)', $f($r['agotamiento']) ?? '—'],
            ] as [$t, $v])
                <td><div class="t">{{ $t }}</div><div class="v">{{ $v }}</div></td>
            @endforeach
        </tr>
    </table>

    @foreach ($r['alertas'] as $a)
        <div class="alerta">⚠ {{ $a }}</div>
    @endforeach

    <h2>Producido por impresora en cada corte (páginas B/N)</h2>
    <table>
        <thead>
            <tr>
                <th>Ubicación</th><th>Serie</th><th>Nivel</th>
                @foreach ($p as $per)
                    <th>{{ mb_substr($per->etiqueta, 0, 3) }}<br>{{ $per->fecha->format('d/m') }}</th>
                @endforeach
                <th>En curso</th><th>Acumulado</th><th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($r['por_equipo'] as $e)
                <tr>
                    <td>{{ $e->ubicacion }}</td><td>{{ $e->serie_contrato ?? $e->serie }}</td><td>{{ $e->nivel }}</td>
                    @foreach ($p as $per)
                        @php $v = $per->producido[$e->id] ?? null; @endphp
                        <td class="n {{ $v === null ? 'sd' : '' }}">{{ $n($v) }}</td>
                    @endforeach
                    <td class="n">{{ $n($e->en_curso) }}</td>
                    <td class="n"><b>{{ $n($e->acumulado) }}</b></td>
                    <td class="n">{{ $e->porcentaje }}%</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">TOTAL</td>
                @foreach ($p as $per) <td class="n">{{ $n($per->total) }}</td> @endforeach
                <td class="n">{{ $n($r['en_curso']->total) }}</td><td class="n">{{ $n($r['acumulado']) }}</td><td class="n">100%</td>
            </tr>
            <tr class="saldo">
                <td colspan="3">Saldo del bolsón</td>
                @foreach ($p as $per) <td class="n">{{ $n($per->saldo) }}</td> @endforeach
                <td></td><td class="n">{{ $n($r['disponible']) }}</td><td></td>
            </tr>
        </tbody>
    </table>

    <h2>Consumo acumulado por impresora / ubicación</h2>
    <table>
        @foreach ($r['por_equipo']->sortByDesc('acumulado') as $e)
            <tr>
                <td style="width: 14%;">{{ $e->ubicacion }}</td>
                <td><span class="barra" style="width: {{ max(1, round($e->acumulado / $max * 80)) }}%;"></span> {{ $n($e->acumulado) }}</td>
                <td class="n" style="width: 8%;">{{ $e->porcentaje }}%</td>
            </tr>
        @endforeach
    </table>

    @if ($hallazgos)
        <div style="page-break-inside: avoid;">
        <h2>Hallazgos principales</h2>
        <table class="hall">
            <tr>
                @foreach ($hallazgos as [$titulo, $texto])
                    <td><b>{{ $titulo }}</b><br>{{ $texto }}</td>
                @endforeach
            </tr>
        </table>
        </div>
    @endif
</body>
</html>
