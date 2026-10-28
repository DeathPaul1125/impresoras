<?php

namespace Database\Seeders;

use App\Models\Dispositivo;
use App\Models\Modelo;
use Illuminate\Database\Seeder;

/**
 * Las 7 multifuncionales de SENABED. Idempotente: crea las que falten y solo completa campos vacíos,
 * así un despliegue no pisa lo que se edite en la pantalla Impresoras.
 */
class ImpresorasSeeder extends Seeder
{
    /** ip => [host, ubicación, nivel, serie que reporta el equipo] */
    public const IMPRESORAS = [
        '192.100.10.50' => ['KMC58A0E', 'Lobby', '1', 'R4P1890099'],
        '192.100.10.110' => ['KMC58AC2', 'DAB 7o.', '7.1', 'R4P1890103'],
        '192.100.12.6' => ['KMC52A69', 'URC 7o.', '7.2', 'R4P1786882'],
        '192.100.10.215' => ['KMC52980', 'DAF 8o.', '8.1', 'R4P1786886'],
        '192.100.10.212' => ['KMC58AB2', 'DCR 8o.', '8.2', 'R4P1890109'],
        '192.100.10.176' => ['KMC58AAE', 'SG 9o.', '9.1', 'R4P2400504'],
        '192.100.10.144' => ['KMC58AC3', 'DAJ 9o.', '9.2', 'R4P1890100'],
    ];

    public function run(): void
    {
        $modelo = Modelo::firstOrCreate(['nombre' => 'ECOSYS M3655idn']);

        foreach (self::IMPRESORAS as $ip => [$host, $ubicacion, $nivel, $serie]) {
            $d = Dispositivo::firstOrCreate(['nombre_host' => $host], [
                'direccion_ip' => $ip, 'ubicacion' => $ubicacion, 'nivel' => $nivel, 'serie' => $serie, 'modelo_id' => $modelo->id,
            ]);

            // Ya existía (p. ej. importada antes): completar solo lo que esté vacío.
            $faltantes = array_filter(
                ['direccion_ip' => $ip, 'ubicacion' => $ubicacion, 'nivel' => $nivel, 'serie' => $serie, 'modelo_id' => $modelo->id],
                fn ($v, $campo) => blank($d->$campo), ARRAY_FILTER_USE_BOTH,
            );
            if ($faltantes) {
                $d->update($faltantes);
            }
        }
    }
}
