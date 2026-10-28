<?php

namespace App\Http\Controllers;

use App\Console\Commands\EnviarReporte;
use App\Console\Commands\LeerEstado;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/** Vista previa de los correos programados y de sus adjuntos, con los datos de hoy. No envía nada. */
class VistaCorreoController extends Controller
{
    public function ver(string $tipo): Renderable
    {
        if ($tipo === 'toner') {
            $d = Dispositivo::whereNotNull('estado')->get()->sortBy(fn ($d) => $d->estado['toner']['pct'] ?? 101)->first();
            abort_unless($d, 404, 'Aún no hay lecturas de estado.');

            return view('emails.aviso-toner', ['impresora' => $d->ubicacion, 'toner' => $d->estado['toner'] ?? ['pct' => '?', 'nombre' => null], 'atencion' => LeerEstado::otros($d->ubicacion)]);
        }
        abort_unless(in_array($tipo, EnviarReporte::TIPOS, true), 404);

        return EnviarReporte::mailable($tipo, dia: self::ultimoDia());
    }

    /** Último día con lectura de contadores: antes de las 6 pm el de hoy todavía no existe. */
    private static function ultimoDia(): Carbon
    {
        $ultima = ContadorEquipo::max('leido_en');

        return $ultima ? Carbon::parse($ultima)->startOfDay() : today();
    }

    /** Descarga el adjunto Excel o PDF tal como saldría en el correo. */
    public function adjunto(string $tipo, string $formato): Response
    {
        abort_unless(in_array($tipo, EnviarReporte::TIPOS, true) && in_array($formato, ['xlsx', 'pdf'], true), 404);

        $adjunto = collect(EnviarReporte::mailable($tipo, dia: self::ultimoDia())->attachments())->first(fn ($a) => str_ends_with($a->as, ".{$formato}"));
        $datos = $adjunto->attachWith(fn () => null, fn ($data) => $data());

        return response($datos, 200, [
            'Content-Type' => $formato === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => ($formato === 'pdf' ? 'inline' : 'attachment').'; filename="'.$adjunto->as.'"',
        ]);
    }
}
