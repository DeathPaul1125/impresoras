<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 9px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 2px; color: #0d2149; }
        h2 { font-size: 12px; margin: 16px 0 6px; color: #0d2149; }
        .sub { color: #777; font-size: 9px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; }
        th { background: #0d2149; color: #fff; text-align: left; font-size: 8px; }
        tr:nth-child(even) td { background: #f5f6fa; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>SENABED — Reporte detallado de {{ $mes }}</h1>
    <div class="sub">Servicios de Impresión y Fotocopiado · generado {{ now()->format('d/m/Y H:i') }} · el registro completo de trabajos va en el Excel adjunto</div>

    @foreach ($hojas as $h)
        <h2>{{ $h['titulo'] }}</h2>
        <table>
            <thead><tr>@foreach ($h['headings'] as $c) <th>{{ $c }}</th> @endforeach</tr></thead>
            <tbody>
                @forelse ($h['rows'] as $fila)
                    <tr>
                        @foreach ($fila as $v)
                            <td class="{{ is_int($v) || is_float($v) ? 'num' : '' }}">{{ is_int($v) ? number_format($v) : (is_float($v) ? number_format($v, 2) : $v) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($h['headings']) }}">Sin datos en el mes.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
