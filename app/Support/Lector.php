<?php

namespace App\Support;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lee los contadores de una impresora: primero por SNMP; si no responde (o el servidor no tiene la
 * extensión php-snmp, como pasa en el contenedor), por el panel web (Command Center) con el usuario admin.
 * Impresiones, copias y escaneos coinciden en ambas fuentes; el "total" del panel no es el de SNMP.
 */
class Lector
{
    /** @return array{modelo:?string,serie:?string,total:?int,impresiones:?int,copias:?int,escaneos:?int,fuente:string}|null */
    public static function leer(string $ip): ?array
    {
        if ($l = Snmp::leer($ip)) {
            return $l + ['fuente' => 'snmp'];
        }

        try {
            $panel = Configuracion::panelImpresoras();
            $cc = new CommandCenter($ip, $panel['usuario'], $panel['password']);
            $cc->login();

            return $cc->contadoresEquipo() + ['fuente' => 'panel'];
        } catch (Throwable $e) {
            Log::warning("Lectura de {$ip} falló por SNMP y por el panel web: {$e->getMessage()}");

            return null;
        }
    }
}
