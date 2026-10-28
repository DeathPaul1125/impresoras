<?php

namespace App\Console\Commands;

use App\Mail\ReporteConsumo;
use App\Models\Configuracion;
use App\Support\Consumo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EnviarReporte extends Command
{
    protected $signature = 'impresoras:enviar-reporte {tipo : diario|semanal|mensual}';

    protected $description = 'Envía por correo el consumo por impresora del día, la semana (lunes a viernes) o el mes';

    private const TITULOS = [
        'diario' => 'Reporte diario',
        'semanal' => 'Reporte de fin de semana (lunes a viernes)',
        'mensual' => 'Reporte mensual',
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

        // El consumo de cada día = lectura de las 6 pm de ese día − la del día anterior.
        $hoy = today();
        $inicio = match ($tipo) {
            'diario' => $hoy,
            'semanal' => $hoy->copy()->startOfWeek(),   // lunes
            'mensual' => $hoy->copy()->startOfMonth(),
        };

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

        Configuracion::aplicarSmtp();
        Mail::to($correos)->send(new ReporteConsumo(
            $titulo,
            ['Ubicación', 'Host', 'IP', 'Impresiones', 'Copias', 'Escaneos', 'Total páginas', 'Costo'],
            $rows,
            $dias,
        ));
        $this->info("{$titulo}: enviado a ".implode(', ', $correos));

        return self::SUCCESS;
    }
}
