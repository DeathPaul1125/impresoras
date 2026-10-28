@php
    $n = fn ($v) => $v === null ? 's/d' : number_format((int) $v);
    $p = $r['periodos']->last();
    $td = 'border: 1px solid #e5e7eb; padding: 6px;';
    $th = 'border: 1px solid #d1d5db; padding: 6px; text-align: left;';
@endphp
<div style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <div style="background: #0d2149; color: #fff; padding: 14px 18px;">
        <div style="font-size: 18px; font-weight: bold;">Corte {{ $p?->etiqueta }} · Contrato {{ $r['contrato'] }}</div>
        <div style="color: #c7d2fe;">Contadores al {{ $p?->fecha->format('d/m/Y H:i') }} Hrs.</div>
    </div>

    <table cellspacing="0" style="border-collapse: collapse; margin: 14px 0; font-size: 13px;">
        @foreach ([
            ['Producido en el período', $n($p?->total)],
            ['Consumo acumulado', $n($r['acumulado']).' de '.$n($r['bolson']).' ('.$r['porcentaje'].'%)'],
            ['Bolsón disponible', $n($r['disponible'])],
            ['Promedio mensual', $n($r['promedio_mensual'])],
            ['Se agota (proyección)', $r['agotamiento']?->format('d/m/Y') ?? '—'],
        ] as [$k, $v])
            <tr><td style="{{ $td }} font-weight: bold; background: #eef2ff;">{{ $k }}</td><td style="{{ $td }}">{{ $v }}</td></tr>
        @endforeach
    </table>

    @foreach ($r['alertas'] as $a)
        <p style="background: #fef3c7; border: 1px solid #fcd34d; padding: 8px;">⚠ {{ $a }}</p>
    @endforeach

    <table cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
        <tr style="background: #0d2149; color: #fff;">
            <th style="{{ $th }}">Ubicación</th><th style="{{ $th }}">Serie</th><th style="{{ $th }}">Producido {{ $p?->etiqueta }}</th><th style="{{ $th }}">Acumulado</th><th style="{{ $th }}">%</th>
        </tr>
        @foreach ($r['por_equipo'] as $e)
            <tr>
                <td style="{{ $td }}">{{ $e->ubicacion }}</td>
                <td style="{{ $td }}">{{ $e->serie }}</td>
                <td style="{{ $td }} text-align: right; {{ $e->ultimo_periodo === null ? 'color: #dc2626;' : '' }}">{{ $n($e->ultimo_periodo) }}</td>
                <td style="{{ $td }} text-align: right;">{{ $n($e->acumulado) }}</td>
                <td style="{{ $td }} text-align: right;">{{ $e->porcentaje }}%</td>
            </tr>
        @endforeach
        <tr style="font-weight: bold; background: #f3f4f6;">
            <td style="{{ $td }}" colspan="2">TOTAL</td>
            <td style="{{ $td }} text-align: right;">{{ $n($p?->total) }}</td>
            <td style="{{ $td }} text-align: right;">{{ $n($r['acumulado']) }}</td>
            <td style="{{ $td }} text-align: right;">100%</td>
        </tr>
    </table>

    @if ($unidades)
        <h3 style="color: #0d2149; margin: 20px 0 6px;">Consumo por unidad (contabilidad de trabajos)</h3>
        <table cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
            <tr style="background: #0d2149; color: #fff;">
                <th style="{{ $th }}">Unidad</th><th style="{{ $th }}">Usuarios</th><th style="{{ $th }}">Impresiones</th><th style="{{ $th }}">Copias</th><th style="{{ $th }}">Costo</th><th style="{{ $th }}">%</th>
            </tr>
            @foreach ($unidades as $u)
                <tr>
                    <td style="{{ $td }}"><b>{{ $u->unidad }}</b> {{ $u->unidad_nombre }}</td>
                    <td style="{{ $td }} text-align: right;">{{ $u->usuarios }}</td>
                    <td style="{{ $td }} text-align: right;">{{ $n($u->impresiones) }}</td>
                    <td style="{{ $td }} text-align: right;">{{ $n($u->copias) }}</td>
                    <td style="{{ $td }} text-align: right;">Q{{ number_format($u->costo_total, 2) }}</td>
                    <td style="{{ $td }} text-align: right;">{{ $u->porcentaje }}%</td>
                </tr>
            @endforeach
        </table>
        <p style="color: #6b7280; font-size: 12px;">El detalle por usuario va en la hoja «Consumo por usuario» del Excel adjunto.</p>
    @endif

    @if ($hallazgos)
        <h3 style="color: #0d2149; margin: 20px 0 6px;">Hallazgos</h3>
        <ul style="padding-left: 18px;">
            @foreach ($hallazgos as [$titulo, $texto])
                <li style="margin-bottom: 6px;"><b>{{ $titulo }}:</b> {{ $texto }}</li>
            @endforeach
        </ul>
    @endif

    <p style="color: #6b7280; font-size: 12px;">Se adjunta el Control de contadores (Excel, una hoja por corte) y el resumen del contrato (PDF).</p>
</div>
