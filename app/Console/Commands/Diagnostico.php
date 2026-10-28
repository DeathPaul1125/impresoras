<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Support\CommandCenter;
use App\Support\Snmp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Revisa por qué no se guardan lecturas: extensión SNMP, programador, red y acceso al panel de cada impresora. */
class Diagnostico extends Command
{
    protected $signature = 'impresoras:diagnostico';

    protected $description = 'Diagnóstico de lecturas: extensión snmp, programador de tareas, red y panel web de cada impresora';

    public function handle(): int
    {
        $this->line('Hora del servidor: '.now()->format('d/m/Y H:i:s').' ('.config('app.timezone').')');

        $snmp = function_exists('snmp2_get');
        $this->line('Extensión PHP snmp: '.($snmp ? '<info>instalada</info>' : '<comment>NO instalada</comment> → se usará el panel web como respaldo'));

        $latido = Cache::get('programador:ultimo');
        if (! $latido) {
            $this->line('Programador de tareas: <error>nunca corrió</error> → falta `* * * * * php artisan schedule:run` (cron) o `php artisan schedule:work`');
        } else {
            $min = (int) Carbon::parse($latido)->diffInMinutes(now());
            $this->line('Programador de tareas: '.($min <= 2 ? '<info>activo</info>' : "<error>detenido</error> (último hace {$min} min)").' · último: '.$latido);
        }

        $ultima = ContadorEquipo::max('leido_en');
        $this->line('Última lectura guardada: '.($ultima ?? 'ninguna'));

        $panel = Configuracion::panelImpresoras();
        $filas = [];
        foreach (Dispositivo::whereNotNull('direccion_ip')->orderBy('nivel')->get() as $d) {
            $porSnmp = $snmp && Snmp::leer($d->direccion_ip) ? 'ok' : ($snmp ? 'sin respuesta' : '—');
            try {
                $cc = new CommandCenter($d->direccion_ip, $panel['usuario'], $panel['password']);
                $cc->login();
                $porPanel = 'ok · impresiones '.number_format($cc->contadoresEquipo()['impresiones']);
            } catch (Throwable $e) {
                $porPanel = 'falla: '.mb_substr($e->getMessage(), 0, 60);
            }
            $filas[] = [$d->ubicacion ?? $d->nombre_host, $d->direccion_ip, $porSnmp, $porPanel,
                ContadorEquipo::where('dispositivo_id', $d->id)->max('leido_en') ?? '—'];
        }
        $this->table(['Impresora', 'IP', 'SNMP', 'Panel web', 'Última lectura'], $filas);

        return self::SUCCESS;
    }
}
