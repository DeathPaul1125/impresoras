<?php

namespace App\Support;

use App\Models\Configuracion;
use App\Models\ContadorCuenta;
use App\Models\Cuenta;
use App\Models\Dispositivo;
use App\Models\Unidad;
use Illuminate\Support\Carbon;
use Throwable;

/** Guarda los contadores por cuenta (clave) de cada impresora, leídos del Command Center. */
class LecturaCuentas
{
    /** @return array<string, int|string> por impresora: cuentas guardadas, o el mensaje de error */
    public static function registrar(?Carbon $fecha = null): array
    {
        $fecha = ($fecha ?? now())->copy()->startOfMinute();
        $panel = Configuracion::panelImpresoras();
        $unidades = Unidad::pluck('id', 'codigo');
        $resultado = [];

        foreach (Dispositivo::whereNotNull('direccion_ip')->orderBy('nivel')->get() as $d) {
            $nombre = $d->ubicacion ?? $d->nombre_host;
            try {
                $cc = new CommandCenter($d->direccion_ip, $panel['usuario'], $panel['password']);
                $cc->login();
                $n = 0;
                foreach ($cc->cuentas() as $c) {
                    $cuenta = self::cuenta($c['clave'], $c['nombre'], $unidades);
                    ContadorCuenta::create(['cuenta_id' => $cuenta->id, 'dispositivo_id' => $d->id, 'leido_en' => $fecha] + $cc->contadores($c['privado']));
                    $n++;
                }
                $resultado[$nombre] = $n;
            } catch (Throwable $e) {
                $resultado[$nombre] = $e->getMessage();
            }
        }

        return $resultado;
    }

    /** La clave de la impresora es id_cuenta (sin ceros a la izquierda); la unidad es el prefijo del nombre (DAB_, SG_...). */
    public static function cuenta(string $clave, string $nombre, $unidades): Cuenta
    {
        $clave = ltrim($clave, '0') ?: '0';
        $prefijo = strtoupper(strtok($nombre, '_') ?: '');
        $unidadId = str_contains($nombre, '_')
            ? ($unidades[$prefijo] ?? $unidades[$prefijo.'_'] ?? $unidades['Otr'] ?? null)
            : ($unidades['Otr'] ?? null);

        return Cuenta::updateOrCreate(['id_cuenta' => $clave], ['nombre_cuenta' => $nombre, 'unidad_id' => $unidadId]);
    }
}
