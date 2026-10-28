<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use App\Models\Dispositivo;
use App\Support\Snmp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Estado actual de cada impresora (estado, pantalla, tóner, alertas, ficha) y aviso de tóner bajo. */
class LeerEstado extends Command
{
    protected $signature = 'impresoras:leer-estado';

    protected $description = 'Lee por SNMP estado, tóner, alertas y ficha técnica de cada impresora; avisa por correo si el tóner baja del 10%';

    public const TONER_BAJO = 10;

    public function handle(): int
    {
        foreach (Dispositivo::whereNotNull('direccion_ip')->orderBy('nivel')->get() as $d) {
            $nombre = $d->ubicacion ?? $d->nombre_host;
            $nuevo = Snmp::detalle($d->direccion_ip);
            if (! $nuevo) {
                // Sin respuesta: se conserva el último estado, marcado como fuera de línea.
                $d->update(['estado' => array_merge($d->estado ?? [], ['estado' => 'sin_respuesta'])]);
                $this->warn("{$nombre}: sin respuesta");

                continue;
            }

            $pct = $nuevo['toner']['pct'] ?? null;
            $avisado = $d->estado['aviso_toner'] ?? false;
            // Un solo aviso por cartucho: se rearma cuando el nivel vuelve a subir (cartucho nuevo).
            $nuevo['aviso_toner'] = $pct !== null && $pct < self::TONER_BAJO ? ($avisado ?: $this->avisar($nombre, $nuevo['toner'])) : false;

            $d->update(['estado' => $nuevo, 'estado_en' => now()]);
            $this->line("{$nombre}: {$nuevo['estado']} · tóner ".($pct ?? '?').'%');
        }

        return self::SUCCESS;
    }

    private function avisar(string $impresora, array $toner): bool
    {
        $correos = Configuracion::correosReportes();
        if (! $correos) {
            return false;
        }
        try {
            Configuracion::aplicarSmtp();
            Mail::raw("El tóner de la impresora {$impresora} está al {$toner['pct']}% ({$toner['nombre']}). Conviene solicitar el reemplazo al proveedor.",
                fn ($m) => $m->to($correos)->subject("Impresoras SENABED · Tóner bajo en {$impresora} ({$toner['pct']}%)"));

            return true;
        } catch (Throwable $e) {
            Log::warning("Aviso de tóner bajo de {$impresora} no enviado: {$e->getMessage()}");

            return false;
        }
    }
}
