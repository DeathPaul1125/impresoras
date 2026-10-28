{{--
    Marco de los correos: tablas y estilos en línea (Outlook y Gmail ignoran <style> y flex).
    $message solo existe al enviar: ahí el logo va incrustado; en la vista previa va como data URI.
--}}
@props(['titulo', 'subtitulo' => null, 'message' => null, 'pie' => null])
@php
    $ruta = public_path('img/logo-senabed-die.png');
    $logo = $message ? $message->embed($ruta) : 'data:image/png;base64,'.base64_encode(file_get_contents($ruta));
@endphp
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: #eef1f6; padding: 24px 0; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <tr>
        <td align="center">
            <table width="680" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 680px; width: 100%; background: #ffffff; border: 1px solid #dbe1ea;">
                <tr>
                    <td style="background: #0d2149; padding: 18px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td width="56" valign="middle"><img src="{{ $logo }}" alt="SENABED" height="44" style="display: block; height: 44px; width: auto; background: #ffffff; border-radius: 4px; padding: 3px;"></td>
                                <td valign="middle" style="padding-left: 12px; color: #c7d2fe; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase;">
                                    SENABED · Servicios de Impresión y Fotocopiado
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 22px 24px 6px;">
                        <div style="font-family: Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: bold; color: #0d2149; line-height: 1.25;">{{ $titulo }}</div>
                        @if ($subtitulo) <div style="margin-top: 4px; font-size: 13px; color: #64748b;">{{ $subtitulo }}</div> @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 24px 24px; font-size: 14px; line-height: 1.5;">
                        {{ $slot }}
                    </td>
                </tr>
                <tr>
                    <td style="background: #f6f8fb; border-top: 1px solid #e5e9f0; padding: 14px 24px; font-size: 11px; color: #64748b; line-height: 1.5;">
                        @if ($pie) {{ $pie }}<br> @endif
                        Mensaje automático del sistema de control de impresoras de SENABED · generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}.
                        Los destinatarios se configuran en Configuración → Correos para reportes.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
