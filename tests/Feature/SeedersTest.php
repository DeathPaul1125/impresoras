<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Corte;
use App\Models\Dispositivo;
use App\Models\User;
use App\Support\Contrato;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** El despliegue corre `db:seed` en cada deploy: debe dejar todo listo y no pisar cambios hechos en producción. */
class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_vacia_queda_con_todos_los_datos_y_cuadra_con_el_proveedor(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Dispositivo::count());
        $this->assertSame(75, Corte::count());              // 7 × 10 cortes ene–sep + 5 de octubre
        $this->assertSame(19, ContadorEquipo::count());
        $this->assertTrue(User::where('email', 'informatica@senabed.gob.gt')->where('role', 'admin')->exists());

        $r = (new Contrato)->resumen();
        $this->assertSame(211761, $r['periodos']->firstWhere('etiqueta', 'Septiembre 2026')->saldo);
        $this->assertSame('9.1', Dispositivo::where('ubicacion', 'SG 9o.')->value('nivel'));
    }

    public function test_correr_de_nuevo_no_duplica_ni_pisa_cambios(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Cambios hechos en producción después del primer despliegue.
        Configuracion::setPrecioUnitario(0.25);
        Dispositivo::where('ubicacion', 'Lobby')->update(['ubicacion' => 'Lobby principal']);
        Corte::where('fecha', '2026-09-25 00:00:00')->first()->update(['contador' => 999999, 'origen' => 'manual']);
        User::first()->update(['password' => bcrypt('nueva')]);
        $hash = User::first()->password;

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Dispositivo::count());
        $this->assertSame(75, Corte::count());
        $this->assertSame(19, ContadorEquipo::count());
        $this->assertSame(1, User::count());
        $this->assertSame(0.25, Configuracion::precioUnitario());
        $this->assertTrue(Dispositivo::where('ubicacion', 'Lobby principal')->exists());
        $this->assertSame(1, Corte::where('contador', 999999)->count());
        $this->assertSame($hash, User::first()->password);
    }

    public function test_admin_inicial_entra_con_la_contrasena_por_defecto_y_se_puede_restablecer(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', DatabaseSeeder::ADMIN_EMAIL)->first();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(DatabaseSeeder::ADMIN_PASSWORD, $admin->password));

        // Contraseña desconocida en el servidor: el comando la restablece.
        $admin->update(['password' => bcrypt('otra-que-nadie-sabe')]);
        $this->artisan('impresoras:admin')->assertSuccessful();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(DatabaseSeeder::ADMIN_PASSWORD, $admin->fresh()->password));

        $this->artisan('impresoras:admin', ['--email' => 'nuevo@senabed.gob.gt', '--password' => 'Clave-Segura-1'])->assertSuccessful();
        $this->assertSame('admin', User::where('email', 'nuevo@senabed.gob.gt')->value('role'));
    }
}
