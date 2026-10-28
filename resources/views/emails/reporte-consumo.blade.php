@php
    $t = $datos['totales'];
    $n = fn ($v) => number_format((int) $v);
    $q = fn ($v) => 'Q'.number_format((float) $v, 2);
    $h2 = 'font-family: Georgia, serif; font-size: 16px; color: #0d2149; margin: 26px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #e5e9f0;';
    $th = 'background: #0d2149; color: #ffffff; font-size: 11px; font-weight: bold; text-align: left; padding: 7px 8px; text-transform: uppercase; letter-spacing: .5px;';
    $td = 'padding: 7px 8px; border-bottom: 1px solid #edf0f5; font-size: 13px;';
    $num = $td.' text-align: right; white-space: nowrap;';
    $tabla = 'width="100%" cellpadding="0" cellspacing="0" role="presentation"';
    $barra = fn ($pct, $color = '#1d4ed8') => '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#e8edf5;"><tr><td style="background:'.$color.'; height:6px; font-size:0; line-height:0;" width="'.max(1, min(100, round($pct))).'%">&nbsp;</td><td style="font-size:0; line-height:0;">&nbsp;</td></tr></table>';
    $variacion = function ($v) {
        if ($v === null) return '<span style="color:#94a3b8;">sin dato anterior</span>';
        $color = $v > 0 ? '#b45309' : '#047857';
        return '<span style="color:'.$color.'; font-weight:bold;">'.($v > 0 ? '▲ +' : ($v < 0 ? '▼ ' : '')).$v.'%</span>';
    };
    $kpis = [
        ['Páginas', $n($t['total']), $datos['variacion']['total'] === null
            ? '<span style="color:#94a3b8;">sin lectura de '.e($datos['anterior']['etiqueta']).'</span>'
            : $variacion($datos['variacion']['total']).' vs. '.e($datos['anterior']['etiqueta'])],
        ['Costo', $q($t['costo_total']), $variacion($datos['variacion']['costo_total'])],
        ['Impresiones / copias', $n($t['impresiones']).' / '.$n($t['copias']), $n($t['escaneos']).' escaneos (sin costo)'],
        ['Trabajos', $n($t['trabajos']), $n($t['usuarios']).' usuarios con consumo'],
    ];
@endphp
<x-correo :titulo="$titulo" :subtitulo="'Consumo de las impresoras · '.$reporte->etiqueta()" :message="$message ?? null"
          pie="Adjuntos: Excel con todas las hojas (resumen, atención, impresoras, por día, papel y doble cara, tóner, usuarios, unidades, trabajos por hora, registro de trabajos y ficha técnica) y PDF con el mismo contenido salvo el registro de trabajos.">

    {{-- Indicadores --}}
    <table {!! $tabla !!} style="border-collapse: collapse; margin-top: 10px;">
        <tr>
            @foreach ($kpis as [$k, $v, $sub])
                <td width="25%" valign="top" style="padding: 12px 10px; background: #f6f8fb; border: 1px solid #e5e9f0;">
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .5px;">{{ $k }}</div>
                    <div style="font-size: 20px; font-weight: bold; color: #0d2149; margin-top: 2px;">{{ $v }}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{!! $sub !!}</div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- Bolsón del contrato --}}
    @if ($b = $datos['bolson'])
        <div style="margin-top: 16px; padding: 12px 14px; background: #eef2ff; border-left: 4px solid #1d4ed8;">
            <div style="font-size: 13px;"><b>Bolsón del contrato {{ $b['contrato'] }}:</b> {{ $n($b['acumulado']) }} de {{ $n($b['bolson']) }} páginas usadas ({{ $b['porcentaje'] }}%) · quedan <b>{{ $n($b['disponible']) }}</b>@if ($b['agotamiento']) · al ritmo actual se agota cerca del <b>{{ $b['agotamiento']->format('d/m/Y') }}</b>@endif.</div>
            <div style="margin-top: 8px;">{!! $barra($b['porcentaje'], $b['porcentaje'] >= 90 ? '#b91c1c' : ($b['porcentaje'] >= 75 ? '#d97706' : '#1d4ed8')) !!}</div>
        </div>
    @endif

    {{-- Atención de equipos --}}
    <div style="{{ $h2 }}">Atención de equipos</div>
    @forelse ($datos['atencion'] as $a)
        <table {!! $tabla !!} style="border-collapse: collapse; margin-bottom: 6px;">
            <tr>
                <td width="4" style="background: {{ $a['grave'] ? '#b91c1c' : '#d97706' }};"></td>
                <td style="padding: 7px 10px; background: {{ $a['grave'] ? '#fef2f2' : '#fffbeb' }}; font-size: 13px;">
                    <b>{{ $a['impresora'] }}</b> · {{ $a['texto'] }}
                    @if ($a['accion']) <div style="color: #475569; font-size: 12px;">Qué hacer: {{ $a['accion'] }}</div> @endif
                </td>
            </tr>
        </table>
    @empty
        <div style="padding: 8px 10px; background: #ecfdf5; color: #047857; font-size: 13px;">Todos los equipos funcionan con normalidad: sin alertas y con tóner suficiente.</div>
    @endforelse

    {{-- Por impresora --}}
    <div style="{{ $h2 }}">Consumo por impresora</div>
    <table {!! $tabla !!} style="border-collapse: collapse;">
        <tr><td style="{{ $th }}">Impresora</td><td style="{{ $th }} text-align: right;">Impresiones</td><td style="{{ $th }} text-align: right;">Copias</td><td style="{{ $th }} text-align: right;">Páginas</td><td style="{{ $th }} text-align: right;">Costo</td><td style="{{ $th }}" width="110">Participación</td><td style="{{ $th }} text-align: right;">Tóner</td></tr>
        @foreach ($datos['impresoras'] as $i)
            @php $tn = $datos['toner'][$i->id] ?? null; @endphp
            <tr>
                <td style="{{ $td }} font-weight: bold;">{{ $i->ubicacion }}</td>
                <td style="{{ $num }}">{{ $n($i->impresiones) }}</td>
                <td style="{{ $num }}">{{ $n($i->copias) }}</td>
                <td style="{{ $num }} font-weight: bold;">{{ $n($i->total) }}</td>
                <td style="{{ $num }}">{{ $q($i->costo_total) }}</td>
                <td style="{{ $td }}">{!! $barra($i->porcentaje) !!}<div style="font-size: 11px; color: #64748b;">{{ $i->porcentaje }}%</div></td>
                <td style="{{ $num }} {{ $tn !== null && $tn < 25 ? 'color: #b91c1c; font-weight: bold;' : '' }}">{{ $tn === null ? '—' : $tn.'%' }}</td>
            </tr>
        @endforeach
        <tr style="background: #f1f5f9; font-weight: bold;">
            <td style="{{ $td }}">TOTAL</td><td style="{{ $num }}">{{ $n($t['impresiones']) }}</td><td style="{{ $num }}">{{ $n($t['copias']) }}</td>
            <td style="{{ $num }}">{{ $n($t['total']) }}</td><td style="{{ $num }}">{{ $q($t['costo_total']) }}</td><td style="{{ $td }}"></td><td style="{{ $td }}"></td>
        </tr>
    </table>

    {{-- Por día (semanal y mensual) --}}
    @if ($datos['dias']->isNotEmpty())
        @php $maxDia = max(1, $datos['dias']->max('total')); @endphp
        <div style="{{ $h2 }}">Consumo por día</div>
        <table {!! $tabla !!} style="border-collapse: collapse;">
            <tr><td style="{{ $th }}">Fecha</td><td style="{{ $th }} text-align: right;">Páginas</td><td style="{{ $th }} text-align: right;">Costo</td><td style="{{ $th }}" width="45%"></td></tr>
            @foreach ($datos['dias'] as $d)
                <tr>
                    <td style="{{ $td }}">{{ ucfirst(\Illuminate\Support\Carbon::parse($d->fecha)->translatedFormat('D d/m')) }}</td>
                    <td style="{{ $num }}">{{ $n($d->total) }}</td>
                    <td style="{{ $num }}">{{ $q($d->costo_total) }}</td>
                    <td style="{{ $td }}">{!! $barra($d->total / $maxDia * 100, '#0d2149') !!}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Usuarios y unidades --}}
    @if ($datos['unidades']->isNotEmpty())
        <div style="{{ $h2 }}">Consumo por unidad</div>
        <table {!! $tabla !!} style="border-collapse: collapse;">
            <tr><td style="{{ $th }}">Unidad</td><td style="{{ $th }} text-align: right;">Usuarios</td><td style="{{ $th }} text-align: right;">Impr. + copias</td><td style="{{ $th }} text-align: right;">Costo</td><td style="{{ $th }}" width="110">%</td></tr>
            @foreach ($datos['unidades'] as $u)
                <tr>
                    <td style="{{ $td }}"><b>{{ $u->unidad }}</b> <span style="color: #64748b;">{{ $u->unidad_nombre }}</span></td>
                    <td style="{{ $num }}">{{ $u->usuarios }}</td>
                    <td style="{{ $num }}">{{ $n($u->impresiones + $u->copias) }}</td>
                    <td style="{{ $num }}">{{ $q($u->costo_total) }}</td>
                    <td style="{{ $td }}">{!! $barra($u->porcentaje, '#047857') !!}<div style="font-size: 11px; color: #64748b;">{{ $u->porcentaje }}%</div></td>
                </tr>
            @endforeach
        </table>

        <div style="{{ $h2 }}">Usuarios con mayor consumo</div>
        <table {!! $tabla !!} style="border-collapse: collapse;">
            <tr><td style="{{ $th }}">#</td><td style="{{ $th }}">Usuario</td><td style="{{ $th }}">Clave</td><td style="{{ $th }} text-align: right;">Impresiones</td><td style="{{ $th }} text-align: right;">Copias</td><td style="{{ $th }} text-align: right;">Costo</td></tr>
            @foreach ($datos['usuarios'] as $u)
                <tr>
                    <td style="{{ $td }} color: #94a3b8;">{{ $loop->iteration }}</td>
                    <td style="{{ $td }}">{{ $u->nombre_cuenta }}</td>
                    <td style="{{ $td }} color: #64748b;">{{ $u->id_cuenta }}</td>
                    <td style="{{ $num }}">{{ $n($u->impresiones) }}</td>
                    <td style="{{ $num }}">{{ $n($u->copias) }}</td>
                    <td style="{{ $num }}">{{ $q($u->costo_total) }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p style="margin-top: 20px; font-size: 12px; color: #64748b;">El consumo por usuario y por unidad aparece desde la segunda lectura de las cuentas de las impresoras (6:02 pm).</p>
    @endif

    @if ($datos['hora_pico'])
        <p style="margin-top: 18px; font-size: 13px; color: #334155;">Hora de mayor uso: <b>{{ $datos['hora_pico'] }}</b>. El detalle por hora y cada trabajo (quién, impresora, páginas y documento) va en el Excel adjunto.</p>
    @endif

    <p style="margin-top: 14px; font-size: 11px; color: #94a3b8;">Páginas del día = lectura de las 6 pm − lectura del día anterior. Costo = (impresiones + copias) × precio unitario; los escaneos no tienen costo.</p>
</x-correo>
