<?php

namespace Tests\Feature;

use App\Livewire\ConfiguracionPrecio;
use App\Livewire\DashboardGeneral;
use App\Livewire\Tablero;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Models\User;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaginasTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(): User
    {
        $this->seed(ConfiguracionSeeder::class);

        // Ayer y hoy a las 6 pm: 439 impresiones + 5286 copias → Q973.25 a Q0.17.
        $d = Dispositivo::create(['ubicacion' => 'SG 9o.', 'nombre_host' => 'KMC58AAE', 'direccion_ip' => '192.100.10.176']);
        ContadorEquipo::create(['dispositivo_id' => $d->id, 'total' => 1000, 'impresiones' => 1000, 'copias' => 0, 'escaneos' => 0, 'leido_en' => today()->subDay()->setTime(18, 0)]);
        ContadorEquipo::create(['dispositivo_id' => $d->id, 'total' => 6725, 'impresiones' => 1439, 'copias' => 5286, 'escaneos' => 3921, 'leido_en' => today()->setTime(18, 0)]);

        return User::create(['name' => 'Admin', 'email' => 'a@a.com', 'password' => bcrypt('x'), 'role' => 'admin']);
    }

    public function test_tablero_renderiza_con_datos(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(Tablero::class)
            ->assertOk()
            ->assertSee('Análisis de consumo')
            ->assertSee('SG 9o.')
            ->assertSee('Q973.25')
            ->assertSee('Consumo Diario');
    }

    public function test_dashboard_general_renderiza(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(DashboardGeneral::class)
            ->assertOk()
            ->assertSee('CONSUMO DE COPIAS E IMPRESIONES')
            ->assertSee('SG 9o.');
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

    public function test_exportaciones_descargan(): void
    {
        $this->actingAs($this->preparar());

        foreach (['dispositivos', 'diario', 'tendencias'] as $tipo) {
            $this->get("/export/{$tipo}")->assertOk()->assertDownload();
            $this->get("/export/pdf/{$tipo}")->assertOk()->assertDownload();
        }
        $this->get('/export/usuarios')->assertNotFound();
    }

    public function test_configuracion_guarda_precio_y_correos(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(ConfiguracionPrecio::class)
            ->set('precio_unitario', 0.25)
            ->call('guardar')
            ->assertHasNoErrors()
            ->set('correos_reportes', 'a@senabed.gob.gt; b@senabed.gob.gt')
            ->call('guardarCorreos')
            ->assertHasNoErrors()
            ->set('correos_reportes', 'a@senabed.gob.gt, no-es-correo')
            ->call('guardarCorreos')
            ->assertHasErrors('correos_reportes');

        $this->assertSame(0.25, \App\Models\Configuracion::precioUnitario());
        $this->assertSame(['a@senabed.gob.gt', 'b@senabed.gob.gt'], \App\Models\Configuracion::correosReportes());
    }

    public function test_cuenta_smtp_se_guarda_cifrada_y_se_aplica(): void
    {
        $this->actingAs($this->preparar());

        Livewire::test(ConfiguracionPrecio::class)
            ->set('smtp_host', 'smtp.office365.com')
            ->set('smtp_port', 587)
            ->set('smtp_username', 'informatica@senabed.gob.gt')
            ->set('smtp_password', 'secreta123')
            ->call('guardarSmtp')
            ->assertHasNoErrors()
            ->assertSet('smtp_password', '')
            ->assertSet('smtp_tiene_password', true)
            // Guardar sin escribir contraseña conserva la anterior.
            ->set('smtp_from_name', 'Informática')
            ->call('guardarSmtp')
            ->assertHasNoErrors();

        $this->assertStringNotContainsString('secreta123', \App\Models\Configuracion::find('smtp')->valor);
        $this->assertSame('secreta123', \App\Models\Configuracion::smtp()['password']);

        $this->assertTrue(\App\Models\Configuracion::aplicarSmtp());
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('secreta123', config('mail.mailers.smtp.password'));
        $this->assertSame('informatica@senabed.gob.gt', config('mail.from.address'));   // remitente = usuario
        $this->assertSame('Informática', config('mail.from.name'));
    }

    public function test_layout_tiene_sidebar_y_topbar(): void
    {
        $this->actingAs($this->preparar());

        $this->get('/tablero')
            ->assertOk()
            ->assertSee('Contadores')          // marca del sidebar
            ->assertSee('Cerrar sesión')       // menú de usuario (topbar)
            ->assertSee('Impresoras')
            ->assertDontSee('Carga de Contadores');
    }

    public function test_paginas_admin_bloqueadas_para_consulta(): void
    {
        $this->preparar();
        $consulta = User::create(['name' => 'C', 'email' => 'c@c.com', 'password' => bcrypt('x'), 'role' => 'consulta']);

        $this->actingAs($consulta)->get('/configuracion')->assertForbidden();
        $this->actingAs($consulta)->get('/cortes')->assertForbidden();
        $this->actingAs($consulta)->get('/dashboard')->assertOk();
        $this->actingAs($consulta)->get('/tablero')->assertOk();
        $this->get('/')->assertOk();
    }
}
