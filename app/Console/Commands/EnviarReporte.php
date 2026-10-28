<?php

namespace App\Console\Commands;

use App\Mail\ReporteConsumo;
use App\Mail\ReporteCorte;
use App\Models\Configuracion;
use App\Support\Consumo;
use App\Support\ConsumoCuentas;
use App\Support\Contrato;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EnviarReporte extends Command
{
    protected $signature = 'impresoras:enviar-reporte {tipo : diario|semanal|mensual}';

    protected $description = 'Envía por correo el consumo del día, de la semana (lunes a viernes) o el reporte del corte mensual del contrato';

    private const TITULOS = [
        'diario' => 'Reporte diario',
        'semanal' => 'Reporte de fin de semana (lunes a viernes)',
        'mensual' => 'Reporte de corte mensual',
    ];

    public function handle(): int
    {
        $tipo = $this->argument('tipo');
        if (! isset(self::TITULOS[$tipo])) {
            $this->error('Tipo inválido. Use: diario, semanal o mensual.');

            return self::INVALID;
        }

        $correos = Configuracion::correosReportes();
        if (! $correos) {
            $this->warn('No hay correos configurados (Configuración → Correos para reportes).');

            return self::FAILURE;
        }

        Configuracion::aplicarSmtp();

        // Mensual = corte del contrato (se envía después de registrar el corte del día 25).
        if ($tipo === 'mensual') {
            $mail = new ReporteCorte;
            Mail::to($correos)->send($mail);
            $this->info($mail->envelope()->subject.': enviado a '.implode(', ', $correos));

            return self::SUCCESS;
        }

        // El consumo de cada día = lectura de las 6 pm de ese día − la del día anterior.
        $hoy = today();
        $inicio = $tipo === 'diario' ? $hoy : $hoy->copy()->startOfWeek();   // semanal: desde el lunes

        $consumo = new Consumo(['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $hoy->toDateString()]);
        $titulo = self::TITULOS[$tipo].' · '.($tipo === 'diario' ? $hoy->format('d/m/Y') : $inicio->format('d/m/Y').' – '.$hoy->format('d/m/Y'));

        $impresoras = $consumo->porDispositivo('ubicacion', 'ASC');
        $total = $consumo->totales();
        $rows = $impresoras->map(fn ($r) => [$r->ubicacion, $r->nombre_host, $r->direccion_ip, $r->impresiones, $r->copias, $r->escaneos, $r->total, 'Q'.number_format($r->costo_total, 2)])->all();
        $rows[] = ['TOTAL', '', '', $total['impresiones'], $total['copias'], $total['escaneos'], $total['total'], 'Q'.number_format($total['costo_total'], 2)];

        // Semanal y mensual: además, el detalle de cada día.
        $dias = $tipo === 'diario' ? [] : $consumo->porDia()
            ->map(fn ($d) => [Carbon::parse($d->fecha)->format('d/m/Y'), $d->impresiones, $d->copias, $d->escaneos, $d->total, 'Q'.number_format($d->costo_total, 2)])
            ->all();

        // Por usuario y unidad (contabilidad de trabajos de las impresoras).
        $cuentas = new ConsumoCuentas(['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $hoy->toDateString()]);
        $q = fn ($v) => 'Q'.number_format($v, 2);
        $usuarios = $cuentas->porUsuario()->take(10)->map(fn ($u) => [$u->id_cuenta, $u->nombre_cuenta, $u->unidad, $u->impresiones, $u->copias, $u->escaneos, $q($u->costo_total)])->all();
        $unidades = $cuentas->porUnidad()->map(fn ($u) => [$u->unidad, $u->usuarios, $u->impresiones, $u->copias, $u->escaneos, $q($u->costo_total), $u->porcentaje.'%'])->all();

        $r = (new Contrato)->resumen();
        Mail::to($correos)->send(new ReporteConsumo(
            $titulo,
            ['Ubicación', 'Host', 'IP', 'Impresiones', 'Copias', 'Escaneos', 'Total páginas', 'Costo'],
            $rows,
            $dias,
            $r['bolson'] ? sprintf('Contrato %s: acumulado %s de %s páginas (%s%%), disponibles %s.',
                $r['contrato'], number_format($r['acumulado']), number_format($r['bolson']), $r['porcentaje'], number_format($r['disponible'])) : null,
            $usuarios,
            $unidades,
        ));
        $this->info("{$titulo}: enviado a ".implode(', ', $correos));

        return self::SUCCESS;
    }
}
