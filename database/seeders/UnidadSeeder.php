<?php

namespace Database\Seeders;

use App\Models\Unidad;
use Illuminate\Database\Seeder;

class UnidadSeeder extends Seeder
{
    public function run(): void
    {
        $unidades = [
            'CGC' => 'Contraloría General de Cuentas',
            'DAB' => 'Admon. Bienes',
            'DAF' => 'Financiero',
            'DAJ' => 'Jurídico',
            'DCR' => 'Control y Registro',
            'DIE' => 'Informática y Estadística',
            'Otr' => 'Otra Unidad',
            'SG_' => 'Secretaría General',
            'UAI' => 'Auditoría Interna',
            'UCI' => 'Control Interno',
            'UIS' => 'Inversiones',
            'URC' => 'Contratistas',
        ];

        foreach ($unidades as $codigo => $nombre) {
            Unidad::updateOrCreate(['codigo' => $codigo], ['nombre' => $nombre]);
        }
    }
}
