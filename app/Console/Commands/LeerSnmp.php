<?php

namespace App\Console\Commands;

use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Support\Lector;
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
            ]);
            $this->line("{$d->ubicacion}: total {$l['total']}");
        }

        if ($fallas) {
            Log::warning('impresoras:leer-snmp sin respuesta de: '.implode(', ', $fallas));
        }

        return self::SUCCESS;
    }
}
