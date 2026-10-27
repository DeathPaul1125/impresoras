<?php

namespace App\Support;

/**
 * Lectura de contadores por SNMP v2c (Kyocera ECOSYS). Son totales del equipo, no por cuenta.
 */
class Snmp
{
    // ponytail: comunidad 'public' fija; hacerla columna si alguna impresora usa otra.
    private const COMUNIDAD = 'public';

    private const OIDS = [
        'modelo' => '1.3.6.1.2.1.25.3.2.1.3.1',
        'serie' => '1.3.6.1.2.1.43.5.1.1.17.1',
        'total' => '1.3.6.1.2.1.43.10.2.1.4.1.1',
        'impresiones' => '1.3.6.1.4.1.1347.42.3.1.1.1.1.1',
        'copias' => '1.3.6.1.4.1.1347.42.3.1.1.1.1.2',
        'escaneos' => '1.3.6.1.4.1.1347.46.10.1.1.5.3',
    ];

    /** @return array{modelo:?string,serie:?string,total:?int,impresiones:?int,copias:?int,escaneos:?int}|null */
    public static function leer(string $ip): ?array
    {
        if (! function_exists('snmp2_get')) {
            return null;
        }

        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

        // Si no responde el total, no tiene caso esperar los demás OIDs.
        $total = @snmp2_get($ip, self::COMUNIDAD, self::OIDS['total'], 1_500_000, 1);
        if ($total === false) {
            return null;
        }

        $datos = [];
        foreach (self::OIDS as $clave => $oid) {
            $v = $clave === 'total' ? $total : @snmp2_get($ip, self::COMUNIDAD, $oid, 1_500_000, 1);
            $v = $v === false ? null : trim((string) $v, "\" \0");
            $datos[$clave] = in_array($clave, ['modelo', 'serie'], true) ? ($v ?: null) : ($v === null ? null : (int) $v);
        }

        return $datos;
    }
}
