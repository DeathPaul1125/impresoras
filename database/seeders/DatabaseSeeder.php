<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnidadSeeder::class,
            ConfiguracionSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'informatica@senabed.gob.gt'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('senabed2026'),
                'role' => 'admin',
            ],
        );
    }
}
