<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos base del sistema. Se puede correr en cada despliegue (`php artisan db:seed --force`):
 * todos los seeders son idempotentes y no sobrescriben lo editado en producción.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnidadSeeder::class,
            ConfiguracionSeeder::class,
            ImpresorasSeeder::class,
            CortesHistoricosSeeder::class,
            LecturasSnmpSeeder::class,
        ]);

        // Administrador inicial: solo se crea si no existe. La contraseña NO va en el repositorio:
        // se toma de ADMIN_PASSWORD (.env del servidor); si falta, se genera una y se muestra una sola vez.
        $email = env('ADMIN_EMAIL', 'informatica@senabed.gob.gt');
        if (! User::where('email', $email)->exists()) {
            $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);
            User::create(['name' => 'Administrador', 'email' => $email, 'password' => Hash::make($password), 'role' => 'admin']);
            $this->command?->warn("Usuario admin creado: {$email}".(env('ADMIN_PASSWORD') ? '' : " · contraseña generada: {$password} (cámbiala al entrar)"));
        }
    }
}
