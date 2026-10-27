<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copia única de datos desde la MySQL del sistema PHP viejo (conexión 'legacy').
 *
 * El esquema exacto del sistema viejo aún no se conoce, por eso el comando primero
 * INSPECCIONA. Configura las credenciales en .env:
 *   LEGACY_DB_HOST, LEGACY_DB_PORT, LEGACY_DB_DATABASE, LEGACY_DB_USERNAME, LEGACY_DB_PASSWORD
 *
 * Uso:
 *   php artisan legacy:importar --inspect            # lista tablas, columnas y conteos
 *   php artisan legacy:importar --sample=tabla       # muestra 5 filas de una tabla
 *   php artisan legacy:importar                       # ejecuta el mapeo (ver mapear())
 *
 * ponytail: el mapeo real de mapear() se completa cuando se conozca el esquema
 * (con --inspect). Hasta entonces el comando aborta con instrucciones claras.
 */
class ImportarLegacy extends Command
{
    protected $signature = 'legacy:importar {--inspect : Lista tablas y columnas de la BD vieja}
                                             {--sample= : Muestra filas de una tabla}';

    protected $description = 'Copia los datos del sistema de impresoras viejo a la BD nueva';

    public function handle(): int
    {
        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->error('No se pudo conectar a la BD legacy. Configura LEGACY_DB_* en .env.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('inspect')) {
            return $this->inspeccionar();
        }

        if ($tabla = $this->option('sample')) {
            return $this->muestra($tabla);
        }

        return $this->mapear();
    }

    private function inspeccionar(): int
    {
        $db = DB::connection('legacy')->getDatabaseName();
        $this->info("Base de datos legacy: {$db}");

        $tablas = DB::connection('legacy')->select('SHOW TABLES');
        foreach ($tablas as $t) {
            $nombre = array_values((array) $t)[0];
            $cols = Schema::connection('legacy')->getColumnListing($nombre);
            $count = DB::connection('legacy')->table($nombre)->count();
            $this->line("• <fg=cyan>{$nombre}</> ({$count} filas): ".implode(', ', $cols));
        }

        return self::SUCCESS;
    }

    private function muestra(string $tabla): int
    {
        $rows = DB::connection('legacy')->table($tabla)->limit(5)->get();
        $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function mapear(): int
    {
        $this->warn('Mapeo pendiente de definir.');
        $this->line('Ejecuta primero:  php artisan legacy:importar --inspect');
        $this->line('Con el esquema real, completa el método mapear() para volcar');
        $this->line('cuentas, dispositivos, modelos y lecturas a la BD nueva.');

        return self::SUCCESS;
    }
}
