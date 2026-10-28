<?php

namespace App\Console\Commands;

use App\Models\ConsumoMensual;
use App\Models\Cuenta;
use App\Models\Dispositivo;
use App\Models\Unidad;
use App\Support\LecturaCuentas;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Trae del sistema anterior (192.100.10.209) el consumo de cada usuario en cada impresora, mes por mes.
 * Solo lectura sobre el sistema anterior; se puede correr las veces que haga falta (reemplaza cada mes).
 *
 *   php artisan impresoras:importar-historia --desde=2025-01
 */
class ImportarHistoria extends Command
{
    protected $signature = 'impresoras:importar-historia {--desde=2025-01 : Primer mes AAAA-MM} {--hasta= : Último mes AAAA-MM (por defecto, el actual)}';

    protected $description = 'Importa del sistema anterior el consumo mensual por impresora y usuario';

    /** id de la impresora en el sistema anterior => IP. */
    private const IDS = ['1' => '192.100.10.50', '2' => '192.100.10.110', '3' => '192.100.10.212', '4' => '192.100.10.215',
        '5' => '192.100.12.6', '8' => '192.100.10.144', '52' => '192.100.10.176'];

    public function handle(): int
    {
        $base = env('LEGACY_BASE_URL', 'http://192.100.10.209/sistema_impresoras1/dashboard/export.php');
        $dispositivos = Dispositivo::pluck('id', 'direccion_ip');
        $unidades = Unidad::pluck('id', 'codigo');
        $mes = Carbon::parse($this->option('desde').'-01');
        $fin = Carbon::parse(($this->option('hasta') ?: now()->format('Y-m')).'-01');

        for (; $mes <= $fin; $mes->addMonth()) {
            $paginas = 0;
            foreach (self::IDS as $idViejo => $ip) {
                if (! isset($dispositivos[$ip])) {
                    continue;
                }
                $r = Http::timeout(30)->get($base, ['export_type' => 'usuarios', 'dispositivo' => $idViejo,
                    'fecha_inicio' => $mes->toDateString(), 'fecha_fin' => $mes->copy()->endOfMonth()->toDateString()]);
                if (! $r->ok()) {
                    $this->error("{$mes->format('Y-m')} {$ip}: el sistema anterior respondió {$r->status()}");

                    return self::FAILURE;
                }

                // La misma clave puede venir dos veces (con nombres distintos): se suman.
                $porCuenta = [];
                foreach (self::parse($r->body()) as $f) {
                    $cuenta = Cuenta::where('id_cuenta', ltrim($f['clave'], '0') ?: '0')->first()
                        ?? LecturaCuentas::cuenta($f['clave'], $f['nombre'], $unidades);   // no cambia el nombre de cuentas existentes
                    foreach (['impresiones', 'copias', 'escaneos'] as $k) {
                        $porCuenta[$cuenta->id][$k] = ($porCuenta[$cuenta->id][$k] ?? 0) + $f[$k];
                    }
                    $paginas += $f['impresiones'] + $f['copias'];
                }

                // Se reemplaza el mes completo de esa impresora.
                ConsumoMensual::whereDate('periodo', $mes)->where('dispositivo_id', $dispositivos[$ip])->delete();
                foreach ($porCuenta as $cuentaId => $v) {
                    ConsumoMensual::create(['periodo' => $mes->toDateString(), 'dispositivo_id' => $dispositivos[$ip], 'cuenta_id' => $cuentaId] + $v);
                }
            }
            $this->line($mes->format('Y-m').': '.number_format($paginas).' páginas (impresiones + copias)');
        }

        return self::SUCCESS;
    }

    /** CSV del sistema anterior: "ID Cuenta","Nombre Cuenta",Unidad,Impresiones,Copias,Escaneos,"Costo Total","Último Corte". */
    public static function parse(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $lineas = array_filter(preg_split('/\r?\n/', $csv));
        array_shift($lineas);

        return array_values(array_filter(array_map(function ($l) {
            $c = str_getcsv($l);

            return count($c) >= 6 ? ['clave' => trim($c[0]), 'nombre' => trim($c[1]), 'impresiones' => (int) $c[3], 'copias' => (int) $c[4], 'escaneos' => (int) $c[5]] : null;
        }, $lineas)));
    }
}
