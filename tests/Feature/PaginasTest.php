<?php

namespace Tests\Feature;

use App\Livewire\ConfiguracionPrecio;
use App\Livewire\DashboardGeneral;
use App\Livewire\Tablero;
use App\Models\User;
use App\Support\ImportadorCsv;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\UnidadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaginasTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(): User
    {
        $this->seed(UnidadSeeder::class);
        $this->seed(ConfiguracionSeeder::class);

        $csv = "id_cuenta;nombre_cuenta;unidad;impresiones;copias;escaneados;fecha_corte;host;ip;modelo\n"
             ."9001;SG_Bililin;SG_;439;5286;3921;31/07/2026 11:09:03;KMC58A0E;192.100.10.50;ECOSYS M3655idn\n"
             ."2022;DAB_Durgui;DAB;816;2197;2027;06/03/2026 07:43:05;KMC58A0E;192.100.10.50;ECOSYS M3655idn\n";
        $ruta = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($ruta, $csv);
        (new ImportadorCsv)->procesar($ruta, 't.csv',
            ['id_cuenta' => 0, 'nombre_cuenta' => 1, 'unidad' => 2, 'impresiones' => 3, 'copias' => 4, 'escaneados' => 5, 'fecha_corte' => 6, 'host' => 7, 'ip' => 8, 'modelo' => 9],
            ';', 'UTF-8', true, null);
        unlink($ruta);

        return User::create(['name' => 'Admin', 'email' => 'a@a.com', 'password' => bcrypt('x'), 'role' => 'admin']);
    }

    public function test_tablero_renderiza_con_datos(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(Tablero::class)
            ->assertOk()
            ->assertSee('Panel de Consumo')
            ->assertSee('SG_Bililin')
            ->assertSee('Q973.25');
    }

    public function test_dashboard_general_renderiza(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(DashboardGeneral::class)
            ->assertOk()
            ->assertSee('Dashboard General');
    }

    public function test_ordenar_cambia_campo(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(Tablero::class)
            ->call('ordenar', 'impresiones')
            ->assertSet('sort_field', 'impresiones')
            ->assertSet('sort_order', 'DESC')
            ->call('ordenar', 'impresiones')
            ->assertSet('sort_order', 'ASC');
    }

    public function test_configuracion_guarda_precio(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(ConfiguracionPrecio::class)
            ->set('precio_unitario', 0.25)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(0.25, \App\Models\Configuracion::precioUnitario());
    }

    public function test_layout_tiene_sidebar_y_topbar(): void
    {
        $this->actingAs($this->preparar());

        $this->get('/tablero')
            ->assertOk()
            ->assertSee('Contadores')          // marca del sidebar
            ->assertSee('Cerrar sesión')       // menú de usuario (topbar)
            ->assertSee('Tablero de Totales')  // enlace de navegación
            ->assertSee('Dashboard');
    }

    public function test_ruta_carga_bloqueada_para_consulta(): void
    {
        $this->preparar();
        $consulta = User::create(['name' => 'C', 'email' => 'c@c.com', 'password' => bcrypt('x'), 'role' => 'consulta']);
        $admin = User::create(['name' => 'A2', 'email' => 'a2@a.com', 'password' => bcrypt('x'), 'role' => 'admin']);

        $this->actingAs($consulta)->get('/carga')->assertForbidden();
        $this->actingAs($admin)->get('/carga')->assertOk();
    }
}
