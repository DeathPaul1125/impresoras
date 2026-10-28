<x-correo :titulo="'Tóner bajo en '.$impresora" subtitulo="Aviso automático: se envía una sola vez por cartucho." :message="$message ?? null">
    <div style="margin-top: 10px; padding: 14px 16px; background: #fef2f2; border-left: 4px solid #b91c1c;">
        <div style="font-size: 30px; font-weight: bold; color: #b91c1c;">{{ $toner['pct'] }}%</div>
        <div style="font-size: 13px;">queda de tóner en <b>{{ $impresora }}</b> (cartucho {{ $toner['nombre'] ?? '—' }}).</div>
    </div>
    <p style="font-size: 14px;"><b>Qué hacer:</b> solicitar al proveedor un cartucho {{ $toner['nombre'] ?? '' }} y tenerlo listo; con menos del 5% la impresora puede detenerse.</p>
    @if ($atencion)
        <div style="font-family: Georgia, serif; font-size: 15px; color: #0d2149; margin: 20px 0 8px;">Otros equipos que requieren atención</div>
        @foreach ($atencion as $a)
            <div style="margin-bottom: 5px; font-size: 13px;"><b>{{ $a['impresora'] }}</b> · {{ $a['texto'] }}</div>
        @endforeach
    @endif
</x-correo>
