<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 2px; color: #2c3e50; }
        .sub { color: #777; font-size: 9px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #2c3e50; color: #fff; text-align: left; font-size: 9px; }
        tr:nth-child(even) td { background: #f5f6fa; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>SENABED — {{ $titulo }}</h1>
    <div class="sub">Servicios de Impresión y Fotocopiado · {{ $filtros }}</div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td class="{{ is_numeric(str_replace([',', 'Q', '%', '.'], '', (string) $cell)) ? 'num' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headings) }}">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
