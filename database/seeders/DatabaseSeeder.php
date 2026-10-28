<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos base del sistema. Se puede correr en cada despliegue (`php artisan db:seed --force`):
 * todos los seeders son idempotentes y no sobrescriben lo editado en producción.
 */
class DatabaseSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'informatica@senabed.gob.gt';

    // ponytail: contraseña inicial en el código a pedido del usuario; el repositorio es público, cambiarla al entrar.
    public const ADMIN_PASSWORD = 'senabed2026';

    public function run(): void
    {
        $this->call([
            UnidadSeeder::class,
            ConfiguracionSeeder::class,
            ImpresorasSeeder::class,
            CortesHistoricosSeeder::class,
            LecturasSnmpSeeder::class,
        ]);

        // Administrador inicial: solo se crea si no existe (un deploy no pisa la contraseña que se cambie después).
        // Contraseña: ADMIN_PASSWORD del .env, o la inicial por defecto. Cámbiela al entrar (Perfil).
        // Si el admin ya existe y no se conoce su contraseña: php artisan impresoras:admin --password=...
        $email = env('ADMIN_EMAIL', self::ADMIN_EMAIL);
        if (! User::where('email', $email)->exists()) {
            User::create(['name' => 'Administrador', 'email' => $email, 'password' => Hash::make(env('ADMIN_PASSWORD') ?: self::ADMIN_PASSWORD), 'role' => 'admin']);
            $this->command?->warn("Usuario admin creado: {$email}");
        }
    }
}
