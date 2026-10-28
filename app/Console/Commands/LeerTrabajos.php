<?php

namespace App\Console\Commands;

use App\Models\Cuenta;
use App\Models\Dispositivo;
use App\Models\Trabajo;
use App\Support\Snmp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Guarda el registro de trabajos de cada impresora. La impresora solo conserva los últimos 16 de cada tipo,
 * por eso corre cada minuto; los ya guardados se ignoran.
 */
class LeerTrabajos extends Command
{
    protected $signature = 'impresoras:leer-trabajos';

    protected $description = 'Guarda los trabajos recientes de cada impresora (quién, tipo, páginas, documento, hora)';

    public function handle(): int
    {
        $cuentas = Cuenta::pluck('id', 'nombre_cuenta');
        $nuevos = 0;

        foreach (Dispositivo::whereNotNull('direccion_ip')->get() as $d) {
            foreach (Snmp::trabajos($d->direccion_ip) as $t) {
                $inicio = $t['inicio'] ? Carbon::createFromTimestamp($t['inicio'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') : null;
                $fila = Trabajo::firstOrCreate(
                    ['dispositivo_id' => $d->id, 'origen' => $t['origen'], 'trabajo' => $t['trabajo'], 'inicio' => $inicio],
                    [
                        'tipo' => $t['tipo'], 'documento' => mb_substr((string) $t['documento'], 0, 255) ?: null,
                        'cuenta' => $t['cuenta'], 'cuenta_id' => $t['cuenta'] ? ($cuentas[$t['cuenta']] ?? null) : null,
                        'usuario' => $t['usuario'], 'paginas' => $t['paginas'], 'originales' => $t['originales'],
                        'juegos' => $t['juegos'], 'estado' => $t['estado'],
                        'fin' => $t['fin'] ? Carbon::createFromTimestamp($t['fin'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') : null,
                    ],
                );
                $nuevos += $fila->wasRecentlyCreated ? 1 : 0;
            }
        }

        $this->line("Trabajos nuevos: {$nuevos}");

        return self::SUCCESS;
    }
}
