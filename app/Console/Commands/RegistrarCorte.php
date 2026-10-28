<?php

namespace App\Console\Commands;

use App\Support\Contrato;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegistrarCorte extends Command
{
    protected $signature = 'impresoras:corte';

    protected $description = 'Registra el corte mensual del contrato con los contadores (impresiones + copias) leídos por SNMP';

    public function handle(): int
    {
        $fallas = [];
        foreach (Contrato::registrarCorte() as $equipo => $contador) {
            if ($contador === null) {
                $fallas[] = $equipo;
                $this->error("{$equipo}: sin respuesta (ingréselo a mano en Cortes)");
            } else {
                $this->line("{$equipo}: {$contador}");
            }
        }

        if ($fallas) {
            Log::warning('impresoras:corte sin respuesta de: '.implode(', ', $fallas));
        }

        return self::SUCCESS;
    }
}
