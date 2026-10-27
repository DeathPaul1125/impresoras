<?php

namespace App\Console\Commands;

use App\Models\Cuenta;
use App\Models\Dispositivo;
use App\Models\Importacion;
use App\Models\Lectura;
use App\Support\ImportadorCsv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Importa datos reales desde las exportaciones CSV del sistema viejo, una por
 * impresora (export.php?export_type=usuarios&dispositivo=ID), reconstruyendo el
 * grano real cuenta × dispositivo. Reutiliza App\Support\ImportadorCsv.
 *
 *   php artisan impresoras:importar-remoto --fresh
 */
class ImportarRemoto extends Command
{
    protected $signature = 'impresoras:importar-remoto
                            {--fresh : Borra cuentas, dispositivos y lecturas antes de importar}
                            {--desde=2020-01-01 : Fecha inicio}
                            {--hasta=2027-12-31 : Fecha fin}';

    protected $description = 'Importa los contadores reales desde las exportaciones del sistema viejo';

    /** id del dispositivo en el sistema viejo => [host, ip]. Modelo: ECOSYS M3655idn. */
    private const DISPOSITIVOS = [
        '1' => ['KMC58A0E', '192.100.10.50'],
        '2' => ['KMC58AC2', '192.100.10.110'],
        '3' => ['KMC58AB2', '192.100.10.212'],
        '4' => ['KMC52980', '192.100.10.215'],
        '5' => ['KMC52A69', '192.100.12.6'],
        '8' => ['KMC58AC3', '192.100.10.144'],
        '52' => ['KMC58AAE', '192.100.10.176'],
    ];

    private const MODELO = 'ECOSYS M3655idn';

    public function handle(): int
    {
        $base = env('LEGACY_BASE_URL', 'http://192.100.10.209/sistema_impresoras1/dashboard/export.php');
        $desde = $this->option('desde');
        $hasta = $this->option('hasta');

        if ($this->option('fresh')) {
            $this->warn('Borrando datos existentes...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Lectura::truncate();
            Cuenta::truncate();
            Importacion::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $importador = new ImportadorCsv;
        $totalFilas = 0;

        foreach (self::DISPOSITIVOS as $id => [$host, $ip]) {
            $this->line("Impresora {$host} ({$ip})...");

            $resp = Http::timeout(60)->get($base, [
                'export_type' => 'usuarios',
                'fecha_inicio' => $desde,
                'fecha_fin' => $hasta,
                'dispositivo' => $id,
            ]);

            if (! $resp->ok()) {
                $this->error("  Falló la descarga (HTTP {$resp->status()}).");

                continue;
            }

            $ruta = $this->prepararCsv($resp->body(), $host, $ip);
            if ($ruta === null) {
                $this->line('  Sin filas.');

                continue;
            }

            // Columnas del temp: 0 id,1 nombre,2 unidad,3 impr,4 copias,5 escaneos,6 costo,7 corte,8 host,9 ip,10 modelo
            $imp = $importador->procesar($ruta, "remoto_{$host}.csv", [
                'id_cuenta' => 0, 'nombre_cuenta' => 1, 'unidad' => 2,
                'impresiones' => 3, 'copias' => 4, 'escaneados' => 5,
                'fecha_corte' => 7, 'host' => 8, 'ip' => 9, 'modelo' => 10,
            ], ',', 'UTF-8', false, null);

            unlink($ruta);
            $totalFilas += $imp->registros;
            $this->info("  {$imp->registros} registros.");
        }

        $this->newLine();
        $this->info("Listo. {$totalFilas} lecturas importadas. Cuentas: ".Cuenta::count().
            ', Dispositivos: '.Dispositivo::count().'.');

        return self::SUCCESS;
    }

    /**
     * Convierte el CSV del sistema viejo (con encabezado y BOM) en un temp con las
     * columnas host/ip/modelo añadidas y sin fila de encabezado.
     */
    private function prepararCsv(string $csv, string $host, string $ip): ?string
    {
        $csv = ltrim($csv, "\xEF\xBB\xBF");           // quita BOM
        $lineas = preg_split('/\r\n|\r|\n/', trim($csv));
        array_shift($lineas);                          // descarta encabezado

        $ruta = tempnam(sys_get_temp_dir(), 'imp');
        $fh = fopen($ruta, 'w');
        $filas = 0;

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $campos = str_getcsv($linea);
            if (count($campos) < 8) {
                continue;
            }
            $campos[8] = $host;
            $campos[9] = $ip;
            $campos[10] = self::MODELO;
            fputcsv($fh, $campos);
            $filas++;
        }
        fclose($fh);

        if ($filas === 0) {
            unlink($ruta);

            return null;
        }

        return $ruta;
    }
}
