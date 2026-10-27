<?php

namespace App\Console\Commands;

use App\Mail\ReporteConsumo;
use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EnviarReporte extends Command
{
    protected $signature = 'impresoras:enviar-reporte {tipo : diario|semanal|mensual}';

    protected $description = 'Envía por correo el consumo por impresora (diferencia entre lecturas SNMP de las 6 pm)';

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

        // Mismo criterio que leer-snmp: hora local de Guatemala guardada tal cual.
        $fin = Carbon::parse(now('America/Guatemala')->format('Y-m-d H:i:s'));
        $inicio = match ($tipo) {
            'diario' => $fin->copy()->subDay(),
            'semanal' => $fin->copy()->subDays(5),                          // domingo 6 pm → viernes 6 pm
            'mensual' => $fin->copy()->startOfMonth()->subDay()->setTimeFrom($fin), // último día del mes anterior
        };

        $datos = $this->consumo($inicio, $fin);
        $titulo = self::TITULOS[$tipo].' · '.$inicio->format('d/m/Y').' – '.$fin->format('d/m/Y');

        Mail::to($correos)->send(new ReporteConsumo($titulo, $datos['headings'], $datos['rows']));
        $this->info("{$titulo}: enviado a ".implode(', ', $correos));

        return self::SUCCESS;
    }

    /**
     * Consumo de cada impresora = última lectura ≤ fin − última lectura ≤ inicio.
     * Si aún no hay lectura antes del inicio, se usa la primera del período (columna "Desde").
     */
    public function consumo(Carbon $inicio, Carbon $fin): array
    {
        $precio = Configuracion::precioUnitario();
        $rows = [];
        $suma = ['impresiones' => 0, 'copias' => 0, 'escaneos' => 0, 'total' => 0, 'costo' => 0.0];

        foreach (Dispositivo::orderBy('ubicacion')->get() as $d) {
            $q = fn () => ContadorEquipo::where('dispositivo_id', $d->id);
            $hasta = $q()->where('leido_en', '<=', $fin)->latest('leido_en')->first();
            $desde = $q()->where('leido_en', '<=', $inicio)->latest('leido_en')->first()
                ?? $q()->where('leido_en', '>', $inicio)->oldest('leido_en')->first();

            if (! $hasta || ! $desde || $hasta->is($desde)) {
                $rows[] = [$d->ubicacion, $d->nombre_host, $d->direccion_ip, '—', '—', '', '', '', '', 'Sin lecturas suficientes'];

                continue;
            }

            // ponytail: max(0) por si se cambia/resetea el equipo; el período con reset sale en 0.
            $dif = fn (string $c) => max(0, (int) $hasta->$c - (int) $desde->$c);
            $fila = ['impresiones' => $dif('impresiones'), 'copias' => $dif('copias'), 'escaneos' => $dif('escaneos'), 'total' => $dif('total')];
            $fila['costo'] = ($fila['impresiones'] + $fila['copias']) * $precio;
            foreach ($fila as $k => $v) {
                $suma[$k] += $v;
            }

            $rows[] = [
                $d->ubicacion, $d->nombre_host, $d->direccion_ip,
                $desde->leido_en->format('d/m/Y H:i'), $hasta->leido_en->format('d/m/Y H:i'),
                $fila['impresiones'], $fila['copias'], $fila['escaneos'], $fila['total'],
                'Q'.number_format($fila['costo'], 2),
            ];
        }

        $rows[] = ['TOTAL', '', '', '', '', $suma['impresiones'], $suma['copias'], $suma['escaneos'], $suma['total'], 'Q'.number_format($suma['costo'], 2)];

        return [
            'headings' => ['Ubicación', 'Host', 'IP', 'Desde', 'Hasta', 'Impresiones', 'Copias', 'Escaneos', 'Total páginas', 'Costo'],
            'rows' => $rows,
        ];
    }
}
