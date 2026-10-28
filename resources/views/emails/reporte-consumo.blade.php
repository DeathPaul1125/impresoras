@php
    $th = 'text-align: left; border: 1px solid #d1d5db;';
    $celda = fn ($v) => '<td style="border: 1px solid #e5e7eb;'.(is_int($v) ? ' text-align: right;' : '').'">'.e(is_int($v) ? number_format($v) : $v).'</td>';
@endphp
<div style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <h2 style="color: #0d2149; margin-bottom: 4px;">{{ $titulo }}</h2>
    @if ($bolson)
        <p style="background: #eef2ff; border-left: 4px solid #1d4ed8; padding: 8px 10px; margin: 8px 0;"><b>Bolsón B/N.</b> {{ $bolson }}</p>
    @endif
    <p style="color: #6b7280; margin-top: 0;">Consumo de cada día = lectura de las 6 pm − lectura del día anterior. Costo = (impresiones + copias) × precio unitario; los escaneos no tienen costo. Se adjunta en Excel y PDF.</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #0d2149; color: #fff;">
                @foreach ($headings as $h) <th style="{{ $th }}">{{ $h }}</th> @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr style="{{ $loop->last ? 'font-weight: bold; background: #f3f4f6;' : '' }}">
                    @foreach ($r as $v) {!! $celda($v) !!} @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($dias)
        <h3 style="color: #0d2149; margin: 24px 0 8px;">Consumo por día</h3>
        <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #0d2149; color: #fff;">
                    @foreach (['Fecha', 'Impresiones', 'Copias', 'Escaneos', 'Total páginas', 'Costo'] as $h) <th style="{{ $th }}">{{ $h }}</th> @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dias as $r)
                    <tr>@foreach ($r as $v) {!! $celda($v) !!} @endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
