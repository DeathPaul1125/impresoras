@php
    $n = fn ($v) => $v === null ? 's/d' : number_format((int) $v);
    $p = $r['periodos']->last();
    $h2 = 'font-family: Georgia, serif; font-size: 16px; color: #0d2149; margin: 26px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #e5e9f0;';
    $th = 'background: #0d2149; color: #ffffff; font-size: 11px; font-weight: bold; text-align: left; padding: 7px 8px; text-transform: uppercase; letter-spacing: .5px;';
    $td = 'padding: 7px 8px; border-bottom: 1px solid #edf0f5; font-size: 13px;';
    $num = $td.' text-align: right; white-space: nowrap;';
    $tabla = 'width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: collapse;"';
    $barra = fn ($pct, $color) => '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#e8edf5;"><tr><td style="background:'.$color.'; height:8px; font-size:0; line-height:0;" width="'.max(1, min(100, round($pct))).'%">&nbsp;</td><td style="font-size:0; line-height:0;">&nbsp;</td></tr></table>';
    $kpis = [
        ['Producido en el período', $n($p?->total), 'páginas B/N (impresiones + copias)'],
        ['Acumulado del contrato', $r['porcentaje'].'%', $n($r['acumulado']).' de '.$n($r['bolson'])],
        ['Bolsón disponible', $n($r['disponible']), 'páginas'],
        ['Se agota (proyección)', $r['agotamiento']?->format('d/m/Y') ?? '—', 'promedio '.$n($r['promedio_mensual']).' al mes'],
    ];
@endphp
<x-correo :titulo="'Corte '.($p?->etiqueta ?? '').' · Contrato '.$r['contrato']" :subtitulo="'Contadores al '.$p?->fecha->format('d/m/Y H:i').' Hrs.'" :message="$message ?? null"
          pie="Adjuntos: Control de contadores en Excel (resumen, consumo por usuario y una hoja por corte, con el formato del proveedor) y resumen del contrato en PDF.">

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: collapse; margin-top: 10px;">
        <tr>
            @foreach ($kpis as [$k, $v, $sub])
                <td width="25%" valign="top" style="padding: 12px 10px; background: #f6f8fb; border: 1px solid #e5e9f0;">
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .5px;">{{ $k }}</div>
                    <div style="font-size: 20px; font-weight: bold; color: #0d2149; margin-top: 2px;">{{ $v }}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $sub }}</div>
                </td>
            @endforeach
        </tr>
    </table>
    <div style="margin-top: 10px;">{!! $barra($r['porcentaje'], $r['porcentaje'] >= 90 ? '#b91c1c' : ($r['porcentaje'] >= 75 ? '#d97706' : '#1d4ed8')) !!}</div>

    @foreach ($r['alertas'] as $a)
        <div style="margin-top: 10px; padding: 8px 10px; background: #fef3c7; border-left: 4px solid #d97706; font-size: 13px;">{{ $a }}</div>
    @endforeach

    <div style="{{ $h2 }}">Producido por impresora</div>
    <table {!! $tabla !!}>
        <tr><td style="{{ $th }}">Ubicación</td><td style="{{ $th }}">Serie</td><td style="{{ $th }} text-align: right;">{{ $p?->etiqueta }}</td><td style="{{ $th }} text-align: right;">Acumulado</td><td style="{{ $th }} text-align: right;">%</td></tr>
        @foreach ($r['por_equipo'] as $e)
            <tr>
                <td style="{{ $td }} font-weight: bold;">{{ $e->ubicacion }}</td>
                <td style="{{ $td }} color: #64748b;">{{ $e->serie }}</td>
                <td style="{{ $num }} {{ $e->ultimo_periodo === null ? 'color: #b91c1c;' : '' }}">{{ $n($e->ultimo_periodo) }}</td>
                <td style="{{ $num }}">{{ $n($e->acumulado) }}</td>
                <td style="{{ $num }}">{{ $e->porcentaje }}%</td>
            </tr>
        @endforeach
        <tr style="background: #f1f5f9; font-weight: bold;">
            <td style="{{ $td }}" colspan="2">TOTAL</td>
            <td style="{{ $num }}">{{ $n($p?->total) }}</td>
            <td style="{{ $num }}">{{ $n($r['acumulado']) }}</td>
            <td style="{{ $num }}">100%</td>
        </tr>
    </table>

    @if ($unidades)
        <div style="{{ $h2 }}">Consumo por unidad en el período</div>
        <table {!! $tabla !!}>
            <tr><td style="{{ $th }}">Unidad</td><td style="{{ $th }} text-align: right;">Usuarios</td><td style="{{ $th }} text-align: right;">Impresiones</td><td style="{{ $th }} text-align: right;">Copias</td><td style="{{ $th }} text-align: right;">Costo</td><td style="{{ $th }} text-align: right;">%</td></tr>
            @foreach ($unidades as $u)
                <tr>
                    <td style="{{ $td }}"><b>{{ $u->unidad }}</b> <span style="color: #64748b;">{{ $u->unidad_nombre }}</span></td>
                    <td style="{{ $num }}">{{ $u->usuarios }}</td>
                    <td style="{{ $num }}">{{ $n($u->impresiones) }}</td>
                    <td style="{{ $num }}">{{ $n($u->copias) }}</td>
                    <td style="{{ $num }}">Q{{ number_format($u->costo_total, 2) }}</td>
                    <td style="{{ $num }}">{{ $u->porcentaje }}%</td>
                </tr>
            @endforeach
        </table>
        <p style="color: #64748b; font-size: 12px;">El detalle por usuario va en la hoja «Consumo por usuario» del Excel adjunto.</p>
    @endif

    <div style="{{ $h2 }}">Atención de equipos</div>
    @forelse ($atencion as $a)
        <div style="margin-bottom: 6px; padding: 7px 10px; background: {{ $a['grave'] ? '#fef2f2' : '#fffbeb' }}; border-left: 4px solid {{ $a['grave'] ? '#b91c1c' : '#d97706' }}; font-size: 13px;">
            <b>{{ $a['impresora'] }}</b> · {{ $a['texto'] }}
            @if ($a['accion']) <div style="color: #475569; font-size: 12px;">Qué hacer: {{ $a['accion'] }}</div> @endif
        </div>
    @empty
        <div style="padding: 8px 10px; background: #ecfdf5; color: #047857; font-size: 13px;">Todos los equipos funcionan con normalidad.</div>
    @endforelse

    @if ($hallazgos)
        <div style="{{ $h2 }}">Hallazgos</div>
        @foreach ($hallazgos as [$tituloH, $texto])
            <p style="margin: 0 0 8px; font-size: 13px;"><b>{{ $tituloH }}:</b> {{ $texto }}</p>
        @endforeach
    @endif
</x-correo>
