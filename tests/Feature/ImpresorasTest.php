<?php

namespace Tests\Feature;

use App\Livewire\Impresoras;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ImpresorasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => bcrypt('x'), 'role' => 'admin']);
    }

    public function test_admin_crea_edita_y_valida_ip_unica(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Impresoras::class)
            ->set('ubicacion', 'DAB 7o.')
            ->set('nombre_host', 'KMC1')
            ->set('direccion_ip', '192.100.10.110')
            ->set('modelo', 'ECOSYS M3655idn')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('DAB 7o.')
            // misma IP otra vez -> rechazada
            ->set('ubicacion', 'Otra')
            ->set('nombre_host', 'KMC2')
            ->set('direccion_ip', '192.100.10.110')
            ->call('guardar')
            ->assertHasErrors(['direccion_ip' => 'unique'])
            ->set('direccion_ip', 'no-es-ip')
            ->call('guardar')
            ->assertHasErrors(['direccion_ip' => 'ip']);

        $d = Dispositivo::sole();
        $this->assertSame('ECOSYS M3655idn', $d->modelo->nombre);

        // Editar conserva su propia IP sin chocar con la regla unique.
        Livewire::test(Impresoras::class)
            ->call('editar', $d->id)
            ->set('ubicacion', 'DAB 8o.')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('DAB 8o.', $d->fresh()->ubicacion);
        $this->assertSame(1, Dispositivo::count());
    }

    public function test_no_elimina_si_tiene_historico(): void
    {
        $this->actingAs($this->admin());

        $con = Dispositivo::create(['ubicacion' => 'Lobby', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1']);
        $sin = Dispositivo::create(['ubicacion' => 'Prueba', 'nombre_host' => 'KMC2', 'direccion_ip' => '10.0.0.2']);
        ContadorEquipo::create(['dispositivo_id' => $con->id, 'total' => 10, 'leido_en' => now()]);

        Livewire::test(Impresoras::class)
            ->call('eliminar', $con->id)
            ->call('eliminar', $sin->id);

        $this->assertNotNull($con->fresh());
        $this->assertNull($sin->fresh());
    }

    public function test_ruta_solo_admin(): void
    {
        $consulta = User::create(['name' => 'C', 'email' => 'c@c.com', 'password' => bcrypt('x'), 'role' => 'consulta']);

        $this->actingAs($consulta)->get('/impresoras')->assertForbidden();
        $this->actingAs($this->admin())->get('/impresoras')->assertOk()->assertSee('Impresoras registradas');
    }
}
