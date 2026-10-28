<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/** Crea el usuario administrador o le restablece la contraseña (p. ej. cuando no se conoce la del servidor). */
class Admin extends Command
{
    protected $signature = 'impresoras:admin {--email= : Correo del admin (por defecto el del seeder)} {--password= : Nueva contraseña (por defecto la inicial del seeder)}';

    protected $description = 'Crea el usuario administrador o restablece su contraseña';

    public function handle(): int
    {
        $email = $this->option('email') ?: DatabaseSeeder::ADMIN_EMAIL;
        $password = $this->option('password') ?: DatabaseSeeder::ADMIN_PASSWORD;

        $user = User::firstOrNew(['email' => $email], ['name' => 'Administrador']);
        $nuevo = ! $user->exists;
        $user->fill(['password' => Hash::make($password), 'role' => 'admin'])->save();

        $this->info(($nuevo ? 'Admin creado' : 'Contraseña restablecida').": {$email}");

        return self::SUCCESS;
    }
}
