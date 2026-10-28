<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

/**
 * Valores iniciales de configuración. Solo se crean si no existen: lo que el admin cambie en
 * Configuración no se pierde en el siguiente despliegue. La cuenta SMTP no va aquí (es un secreto):
 * se configura en la pantalla o con MAIL_* en el .env del servidor.
 */
class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        Configuracion::firstOrCreate(['clave' => 'precio_unitario'], ['valor' => '0.17']);

        Configuracion::firstOrCreate(['clave' => 'contrato'], ['valor' => json_encode([
            'numero' => '4912',
            'bolson' => 781200,
            'inicio' => '2026-01-02',
            'fin' => '2026-12-31',
            'dia_corte' => 25,
        ])]);
    }
}
