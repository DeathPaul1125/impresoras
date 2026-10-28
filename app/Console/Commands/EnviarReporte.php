<?php

namespace App\Console\Commands;

use App\Mail\ReporteConsumo;
use App\Mail\ReporteCorte;
use App\Models\Configuracion;
use App\Support\ReportePeriodo;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EnviarReporte extends Command
{
    protected $signature = 'impresoras:enviar-reporte {tipo : diario|semanal|mensual|detallado} {--mes= : detallado: mes AAAA-MM (por defecto, el actual)}';

    protected $description = 'Envía por correo el reporte diario, semanal (lunes a viernes), el del corte del contrato (mensual) o el detallado de fin de mes';

    public const TIPOS = ['diario', 'semanal', 'mensual', 'detallado'];

    /** El correo de cada tipo. También lo usa la vista previa de Configuración. */
    public static function mailable(string $tipo, ?string $mes = null, ?Carbon $dia = null): Mailable
    {
        $dia ??= today();

        return match ($tipo) {
            'diario' => new ReporteConsumo(ReportePeriodo::dia($dia)),
            'semanal' => new ReporteConsumo(ReportePeriodo::semana($dia)),
            'detallado' => new ReporteConsumo(ReportePeriodo::mes($mes ? Carbon::parse($mes.'-01') : today())),
            'mensual' => new ReporteCorte,   // corte del contrato (después de registrar el corte del día 25)
        };
    }

    public function handle(): int
    {
        $tipo = $this->argument('tipo');
        if (! in_array($tipo, self::TIPOS, true)) {
            $this->error('Tipo inválido. Use: diario, semanal, mensual o detallado.');

            return self::INVALID;
        }

        $correos = Configuracion::correosReportes();
        if (! $correos) {
            $this->warn('No hay correos configurados (Configuración → Correos para reportes).');

            return self::FAILURE;
        }

        Configuracion::aplicarSmtp();
        $mail = self::mailable($tipo, $this->option('mes'));
        Mail::to($correos)->send($mail);
        $this->info($mail->envelope()->subject.': enviado a '.implode(', ', $correos));

        return self::SUCCESS;
    }
}
