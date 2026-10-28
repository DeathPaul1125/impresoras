<?php

namespace App\Console\Commands;

use App\Support\LecturaCuentas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LeerCuentas extends Command
{
    protected $signature = 'impresoras:leer-cuentas';

    protected $description = 'Lee del panel web de cada impresora los contadores por cuenta (clave) y los guarda';

    public function handle(): int
    {
        foreach (LecturaCuentas::registrar() as $impresora => $r) {
            if (is_int($r)) {
                $this->line("{$impresora}: {$r} cuentas");
            } else {
                $this->error("{$impresora}: {$r}");
                Log::warning("impresoras:leer-cuentas {$impresora}: {$r}");
            }
        }

        return self::SUCCESS;
    }
}
