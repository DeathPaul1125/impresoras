<?php

namespace App\Support;

use App\Models\Configuracion;

/**
 * Lectura de contadores por SNMP v2c (Kyocera ECOSYS). Son totales del equipo, no por cuenta.
 */
class Snmp
{
    /** Configuración (Configuración → SNMP), leída una vez por proceso. */
    public static ?array $cfg = null;

    /** Consulta un OID con la comunidad, tiempo de espera y reintentos configurados. false si no responde. */
    private static function get(string $ip, string $oid): string|false
    {
        $c = self::$cfg ??= Configuracion::snmp();

        return @snmp2_get($ip, $c['comunidad'], $oid, (int) ($c['timeout'] * 1_000_000), $c['reintentos']);
    }

    /** Recorre una tabla; espera un segundo más que una consulta simple. */
    private static function walk(string $ip, string $oid): array|false
    {
        $c = self::$cfg ??= Configuracion::snmp();

        return @snmp2_real_walk($ip, $c['comunidad'], $oid, (int) (($c['timeout'] + 1) * 1_000_000), $c['reintentos']);
    }

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
        $total = self::get($ip, self::OIDS['total']);
        if ($total === false) {
            return null;
        }

        $datos = [];
        foreach (self::OIDS as $clave => $oid) {
            $v = $clave === 'total' ? $total : self::get($ip, $oid);
            $v = $v === false ? null : trim((string) $v, "\" \0");
            $datos[$clave] = in_array($clave, ['modelo', 'serie'], true) ? ($v ?: null) : ($v === null ? null : (int) $v);
        }

        return $datos;
    }

    /** Recorre una columna SNMP y devuelve [índice => valor]. */
    private static function columna(string $ip, string $oid): array
    {
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
        $r = self::walk($ip, $oid);
        $out = [];
        foreach (is_array($r) ? $r : [] as $k => $v) {
            $out[substr(strrchr($k, '.'), 1)] = trim((string) $v, "\" \0");
        }

        return $out;
    }

    private static function valor(string $ip, string $oid): ?string
    {
        $v = self::get($ip, $oid);

        return $v === false ? null : trim((string) $v, "\" \0");
    }

    /**
     * Estado, suministros, papel, dúplex y ficha técnica (Printer-MIB + MIB privada Kyocera 1347).
     * null si la impresora no responde.
     */
    public static function detalle(string $ip): ?array
    {
        if (! function_exists('snmp2_get') || self::valor($ip, '1.3.6.1.2.1.25.3.5.1.1.1') === null) {
            return null;
        }
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

        // Suministros: nombre, nivel y capacidad (-3 = hay existencia, -2 = desconocido).
        $nombres = self::columna($ip, '1.3.6.1.2.1.43.11.1.1.6.1');
        $max = self::columna($ip, '1.3.6.1.2.1.43.11.1.1.8.1');
        $nivel = self::columna($ip, '1.3.6.1.2.1.43.11.1.1.9.1');
        $suministros = [];
        foreach ($nombres as $i => $n) {
            $m = (int) ($max[$i] ?? 0);
            $l = (int) ($nivel[$i] ?? 0);
            $suministros[] = ['nombre' => $n, 'nivel' => $l, 'max' => $m, 'pct' => $m > 0 && $l >= 0 ? round($l / $m * 100) : null];
        }
        $toner = collect($suministros)->first(fn ($s) => $s['pct'] !== null);

        // Páginas por tamaño de papel (Kyocera): nombre y contador por índice.
        $papelNombre = self::columna($ip, '1.3.6.1.4.1.1347.42.2.1.1.1.2.1');
        $papelPaginas = self::columna($ip, '1.3.6.1.4.1.1347.42.2.1.1.1.6.1');
        $papel = [];
        foreach ($papelNombre as $i => $n) {
            if ((int) ($papelPaginas[$i] ?? 0) > 0) {
                $papel[$n] = (int) $papelPaginas[$i];
            }
        }

        // Bandejas de papel: nombre (MP Tray, Cassette 1...), nivel y capacidad (-3 = tiene papel, cantidad desconocida).
        $bandejaNombre = self::columna($ip, '1.3.6.1.2.1.43.8.2.1.18.1');
        $bandejaMax = self::columna($ip, '1.3.6.1.2.1.43.8.2.1.9.1');
        $bandejaNivel = self::columna($ip, '1.3.6.1.2.1.43.8.2.1.10.1');
        $bandejas = [];
        foreach ($bandejaNombre as $i => $n) {
            $m = (int) ($bandejaMax[$i] ?? 0);
            $l = (int) ($bandejaNivel[$i] ?? -2);
            $bandejas[] = ['nombre' => $n, 'nivel' => $l, 'max' => $m, 'pct' => $m > 0 && $l >= 0 ? round($l / $m * 100) : null];
        }

        // Nombres por grupo.índice para decir a qué bandeja, salida o suministro se refiere cada alerta.
        $partes = [];
        foreach ([8 => $bandejaNombre, 9 => self::columna($ip, '1.3.6.1.2.1.43.9.2.1.12.1'), 11 => $nombres] as $g => $lista) {
            foreach ($lista as $i => $n) {
                $partes["{$g}.{$i}"] = $n;
            }
        }

        // Alertas (prtAlertTable): severidad, quién la resuelve, grupo, índice y código. La hora de la alerta (col. 9)
        // no sigue el sysUpTime en las Kyocera: cuándo apareció lo anota leer-estado.
        $col = fn (int $c) => self::columna($ip, "1.3.6.1.2.1.43.18.1.1.{$c}.1");
        [$sev, $nivelUsuario, $grupo, $indice, $cod] = [$col(2), $col(3), $col(4), $col(5), $col(7)];
        $alertas = [];
        foreach ($cod as $i => $c) {
            $alertas[] = AlertasImpresora::describir([
                'severidad' => (int) ($sev[$i] ?? 0), 'nivel_usuario' => (int) ($nivelUsuario[$i] ?? 0),
                'grupo' => (int) ($grupo[$i] ?? 0), 'indice' => (int) ($indice[$i] ?? 0), 'codigo' => (int) $c,
            ], $partes);
        }
        $alertas = collect($alertas)->unique('texto')->values()->all();   // el fusor repite la misma alerta por cada unidad

        $mac = self::get($ip, '1.3.6.1.2.1.2.2.1.6.1');   // 6 bytes crudos: no recortar
        $uptime = (int) self::valor($ip, '1.3.6.1.2.1.1.3.0');   // centésimas de segundo

        return [
            'estado' => match ((int) self::valor($ip, '1.3.6.1.2.1.25.3.5.1.1.1')) { 3 => 'disponible', 4 => 'imprimiendo', 5 => 'calentando', default => 'otro' },
            'mensaje' => implode(' · ', self::columna($ip, '1.3.6.1.2.1.43.16.5.1.2.1')) ?: null,
            'toner' => $toner,
            'suministros' => $suministros,
            'alertas' => $alertas,
            'bandejas' => $bandejas,
            'papel' => $papel,
            'simplex' => (int) self::valor($ip, '1.3.6.1.4.1.1347.42.2.5.1.1.1.1'),
            'duplex' => (int) self::valor($ip, '1.3.6.1.4.1.1347.42.2.5.1.1.2.1'),
            'firmware' => self::valor($ip, '1.3.6.1.4.1.1347.43.5.4.1.5.1.1'),
            'firmware_motor' => self::valor($ip, '1.3.6.1.4.1.1347.43.5.4.1.5.1.2'),
            'mac' => $mac !== false && strlen((string) $mac) === 6 ? implode(':', str_split(bin2hex($mac), 2)) : null,
            'encendido_horas' => round($uptime / 360_000, 1),
            'ubicacion_configurada' => self::valor($ip, '1.3.6.1.2.1.1.6.0') ?: null,
            'instalada' => self::valor($ip, '1.3.6.1.4.1.1347.42.2.6.3.0.0'),
        ];
    }

    /**
     * Registro de trabajos de la impresora (solo guarda los últimos 16 de cada tabla).
     * Tabla 6 = impresión/copia (col 9 = páginas impresas = originales × juegos); tabla 7 = escaneo/envío (col 15 = páginas).
     *
     * @return list<array{origen:string,trabajo:int,tipo:string,documento:?string,cuenta:?string,usuario:?string,paginas:int,originales:int,juegos:int,estado:string,inicio:?int,fin:?int}>
     */
    public static function trabajos(string $ip): array
    {
        if (! function_exists('snmp2_get')) {
            return [];
        }
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
        snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

        $trabajos = [];
        foreach (['impresion' => 6, 'envio' => 7] as $origen => $tabla) {
            $r = self::walk($ip, "1.3.6.1.4.1.1347.47.{$tabla}.1.1");
            $filas = [];
            foreach (is_array($r) ? $r : [] as $k => $v) {
                $p = explode('.', ltrim($k, '.'));
                $idx = array_pop($p);
                $filas[$idx][array_pop($p)] = trim((string) $v, "\" \0");
            }
            foreach ($filas as $c) {
                if (empty($c[2])) {
                    continue;
                }
                $tipo = match ($origen.':'.($c[3] ?? '')) {
                    'impresion:1' => 'impresion', 'impresion:2' => 'copia', 'envio:8' => 'escaneo', default => $origen === 'envio' ? 'envio' : 'otro',
                };
                $trabajos[] = [
                    'origen' => $origen,
                    'trabajo' => (int) $c[2],
                    'tipo' => $tipo,
                    'documento' => CommandCenter::utf8($c[4] ?? '') ?: null,
                    'cuenta' => CommandCenter::utf8($c[8] ?? '') ?: null,
                    'usuario' => CommandCenter::utf8($c[12] ?? '') ?: null,
                    'paginas' => (int) ($origen === 'impresion' ? ($c[9] ?? 0) : ($c[15] ?? 0)),
                    'originales' => (int) ($c[15] ?? 0),
                    'juegos' => $origen === 'impresion' ? (int) ($c[10] ?? 1) : 1,
                    'estado' => ($c[5] ?? '0') === '0' ? 'completado' : 'cancelado',
                    'inicio' => isset($c[6]) && (int) $c[6] > 0 ? (int) $c[6] : null,
                    'fin' => isset($c[7]) && (int) $c[7] > 0 ? (int) $c[7] : null,
                ];
            }
        }

        return $trabajos;
    }
}
