@php
    $th = 'text-align: left; border: 1px solid #d1d5db;';
    $celda = fn ($v) => '<td style="border: 1px solid #e5e7eb;'.(is_int($v) || is_float($v) ? ' text-align: right;' : '').'">'.e(is_int($v) ? number_format($v) : (is_float($v) ? number_format($v, 2) : $v)).'</td>';
@endphp
<div style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <h2 style="color: #0d2149; margin-bottom: 4px;">Reporte detallado de {{ $mes }}</h2>
    <p style="background: #eef2ff; border-left: 4px solid #1d4ed8; padding: 8px 10px; margin: 8px 0;">
        <b>{{ number_format($totales['total']) }}</b> páginas ({{ number_format($totales['impresiones']) }} impresiones, {{ number_format($totales['copias']) }} copias, {{ number_format($totales['escaneos']) }} escaneos) ·
        costo <b>Q{{ number_format($totales['costo_total'], 2) }}</b> ·
        {{ number_format($totales['usuarios']) }} usuarios con consumo · {{ number_format($totales['trabajos']) }} trabajos registrados.
    </p>
    <p style="color: #6b7280; margin-top: 0;">Se adjunta el Excel con todas las hojas (por impresora, diario, papel y doble cara, tóner, usuarios, unidades, trabajos por hora, registro completo de trabajos y ficha técnica) y el PDF con el resumen.</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #0d2149; color: #fff;">
                @foreach ($impresoras['headings'] as $h) <th style="{{ $th }}">{{ $h }}</th> @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($impresoras['rows'] as $r)
                <tr style="{{ $loop->last ? 'font-weight: bold; background: #f3f4f6;' : '' }}">
                    @foreach ($r as $v) {!! $celda($v) !!} @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
