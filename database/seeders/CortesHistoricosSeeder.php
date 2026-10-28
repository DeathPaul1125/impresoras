<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use App\Models\Corte;
use App\Models\Dispositivo;
use Illuminate\Database\Seeder;

/**
 * Contrato 4912 y cortes ene–sep 2026 tomados de "Control Contadores mensuales enero-diciembre 2026".
 * Contador = B/N producido (impresiones + copias) según el proveedor. Idempotente.
 */
class CortesHistoricosSeeder extends Seeder
{
    /** ip => [nivel, serie según contrato] */
    private const EQUIPOS = [
        '192.100.10.50' => ['1', 'R4P1890099'],
        '192.100.10.110' => ['7.1', 'R4P1890103'],
        '192.100.12.6' => ['7.2', 'R4P1786882'],
        '192.100.10.215' => ['8.1', 'R4P1786886'],
        '192.100.10.212' => ['8.2', 'R4P1890109'],
        '192.100.10.176' => ['9.1', 'R4P1890108'],   // SG: el equipo actual reporta otra serie (pendiente)
        '192.100.10.144' => ['9.2', 'R4P1890100'],
    ];

    /** fecha => contadores en el orden de EQUIPOS */
    private const CORTES = [
        '2026-01-02' => [118136, 226070, 26062, 132700, 170083, 138556, 182205],   // contador inicial
        '2026-01-25' => [120879, 239300, 27687, 141445, 179755, 143281, 191813],
        '2026-02-26' => [123889, 257172, 28735, 147460, 194038, 154660, 206124],
        '2026-03-26' => [126500, 271636, 29718, 155040, 211277, 168095, 222848],
        '2026-04-26' => [128500, 282624, 30469, 159770, 224570, 176598, 236286],
        '2026-05-26' => [130831, 302100, 31731, 166081, 237984, 186819, 251748],
        '2026-06-25' => [134649, 317987, 32819, 174653, 251786, 195772, 270688],
        '2026-07-27' => [136601, 334044, 34073, 181225, 263961, 204423, 283191],
        '2026-08-26' => [139401, 351831, 34863, 189559, 277759, 213202, 297043],
        '2026-09-25' => [141679, 364866, 35796, 194678, 289221, 221383, 315628],
    ];

    public function run(): void
    {
        Configuracion::setContrato(['numero' => '4912', 'bolson' => 781200, 'inicio' => '2026-01-02', 'fin' => '2026-12-31', 'dia_corte' => 25]);

        $ips = array_keys(self::EQUIPOS);
        foreach ($ips as $i => $ip) {
            $d = Dispositivo::where('direccion_ip', $ip)->first();
            if (! $d) {
                continue;
            }
            $d->update(['nivel' => self::EQUIPOS[$ip][0]]);

            foreach (self::CORTES as $fecha => $contadores) {
                Corte::updateOrCreate(
                    ['fecha' => "$fecha 00:00:00", 'dispositivo_id' => $d->id],
                    ['serie' => self::EQUIPOS[$ip][1], 'contador' => $contadores[$i], 'origen' => 'historico'],
                );
            }
        }
    }
}
