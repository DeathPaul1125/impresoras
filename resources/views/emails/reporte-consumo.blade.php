<div style="font-family: Arial, sans-serif; color: #1f2937; font-size: 14px;">
    <h2 style="color: #0d2149; margin-bottom: 4px;">{{ $titulo }}</h2>
    <p style="color: #6b7280; margin-top: 0;">Consumo por impresora según las lecturas SNMP de las 6 pm. Costo = (impresiones + copias) × precio unitario; los escaneos no tienen costo. Se adjunta en Excel y PDF.</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 13px;">
        <thead>
            <tr style="background: #0d2149; color: #fff;">
                @foreach ($headings as $h)
                    <th style="text-align: left; border: 1px solid #d1d5db;">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $r)
                <tr style="{{ $loop->last ? 'font-weight: bold; background: #f3f4f6;' : '' }}">
                    @foreach ($r as $v)
                        <td style="border: 1px solid #e5e7eb; {{ is_int($v) ? 'text-align: right;' : '' }}">{{ is_int($v) ? number_format($v) : $v }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
