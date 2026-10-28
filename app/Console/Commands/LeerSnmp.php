<?php

namespace App\Console\Commands;

use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Support\Lector;
use App\Support\Snmp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LeerSnmp extends Command
{
    protected $signature = 'impresoras:leer-snmp';

    protected $description = 'Lee por SNMP los totales de cada impresora y los guarda en contadores_equipo';

    public function handle(): int
    {
        $ahora = now();
        $fallas = [];

        foreach (Dispositivo::whereNotNull('direccion_ip')->get() as $d) {
            $l = Lector::leer($d->direccion_ip);
            if (! $l) {
                $fallas[] = "{$d->ubicacion} ({$d->direccion_ip})";
                $this->error("{$d->ubicacion} ({$d->direccion_ip}): sin respuesta");

                continue;
            }

            ContadorEquipo::create([
                'dispositivo_id' => $d->id,
                'total' => $l['total'],
                'impresiones' => $l['impresiones'],
                'copias' => $l['copias'],
                'escaneos' => $l['escaneos'],
                'leido_en' => $ahora,
                // Foto del día para el reporte mensual: tóner, papel por tamaño y simplex/dúplex.
                'detalle' => ($det = Snmp::detalle($d->direccion_ip))
                    ? array_intersect_key($det, array_flip(['toner', 'papel', 'simplex', 'duplex', 'alertas']))
                    : null,
            ]);
            $this->line("{$d->ubicacion}: total {$l['total']}");
        }

        if ($fallas) {
            Log::warning('impresoras:leer-snmp sin respuesta de: '.implode(', ', $fallas));
        }

        return self::SUCCESS;
    }
}
