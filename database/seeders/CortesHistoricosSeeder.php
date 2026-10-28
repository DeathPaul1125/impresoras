<?php

namespace Database\Seeders;

use App\Models\Corte;
use App\Models\Dispositivo;
use Illuminate\Database\Seeder;

/**
 * Cortes del contrato 4912. Ene–sep 2026 tomados de "Control Contadores mensuales enero-diciembre 2026"
 * (contador B/N = impresiones + copias según el proveedor) y el corte de octubre leído por SNMP.
 * Idempotente: solo crea los que falten, no pisa correcciones hechas en la pantalla Cortes.
 */
class CortesHistoricosSeeder extends Seeder
{
    /** ip => serie según el contrato (SG: el equipo actual reporta otra serie, pendiente de confirmar). */
    private const SERIES_CONTRATO = [
        '192.100.10.50' => 'R4P1890099',
        '192.100.10.110' => 'R4P1890103',
        '192.100.12.6' => 'R4P1786882',
        '192.100.10.215' => 'R4P1786886',
        '192.100.10.212' => 'R4P1890109',
        '192.100.10.176' => 'R4P1890108',
        '192.100.10.144' => 'R4P1890100',
    ];

    /** fecha => contadores en el orden de SERIES_CONTRATO */
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

    /**
     * Corte de octubre registrado por SNMP el 27/10/2026 (el sistema aún no corría el 25).
     * URC y DAJ estaban apagadas: sus contadores se ingresan a mano en Cortes.
     * [fecha, ip, serie leída, contador]
     */
    private const CORTES_SNMP = [
        ['2026-10-27 17:57:00', '192.100.10.50', 'R4P1890099', 144220],
        ['2026-10-27 17:57:00', '192.100.10.110', 'R4P1890103', 383069],
        ['2026-10-27 17:57:00', '192.100.10.215', 'R4P1786886', 201722],
        ['2026-10-27 17:57:00', '192.100.10.212', 'R4P1890109', 304825],
        ['2026-10-27 17:57:00', '192.100.10.176', 'R4P2400504', 172908],
    ];

    public function run(): void
    {
        $porIp = Dispositivo::whereIn('direccion_ip', array_keys(self::SERIES_CONTRATO))->get()->keyBy('direccion_ip');

        foreach (array_keys(self::SERIES_CONTRATO) as $i => $ip) {
            if (! $d = $porIp->get($ip)) {
                continue;
            }
            foreach (self::CORTES as $fecha => $contadores) {
                Corte::firstOrCreate(
                    ['fecha' => "$fecha 00:00:00", 'dispositivo_id' => $d->id],
                    ['serie' => self::SERIES_CONTRATO[$ip], 'contador' => $contadores[$i], 'origen' => 'historico'],
                );
            }
        }

        foreach (self::CORTES_SNMP as [$fecha, $ip, $serie, $contador]) {
            if ($d = $porIp->get($ip)) {
                Corte::firstOrCreate(
                    ['fecha' => $fecha, 'dispositivo_id' => $d->id],
                    ['serie' => $serie, 'contador' => $contador, 'origen' => 'snmp'],
                );
            }
        }
    }
}
