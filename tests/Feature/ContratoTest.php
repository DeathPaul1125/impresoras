<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Corte;
use App\Models\Dispositivo;
use App\Models\User;
use App\Support\Contrato;
use Database\Seeders\CortesHistoricosSeeder;
use App\Livewire\DashboardGeneral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContratoTest extends TestCase
{
    use RefreshDatabase;

    /** Las 7 impresoras con sus IP reales, para que el seeder del histórico las encuentre. */
    private function impresoras(): void
    {
        foreach (['192.100.10.50' => 'Lobby', '192.100.10.110' => 'DAB 7o.', '192.100.12.6' => 'URC 7o.', '192.100.10.215' => 'DAF 8o.',
            '192.100.10.212' => 'DCR 8o.', '192.100.10.176' => 'SG 9o.', '192.100.10.144' => 'DAJ 9o.'] as $ip => $u) {
            Dispositivo::create(['ubicacion' => $u, 'nombre_host' => 'H'.$ip, 'direccion_ip' => $ip]);
        }
        $this->seed(CortesHistoricosSeeder::class);
    }

    public function test_historico_coincide_con_el_control_del_proveedor(): void
    {
        $this->impresoras();

        $r = (new Contrato)->resumen();

        // Cifras del Excel "Control Contadores mensuales" y del dashboard de septiembre.
        $this->assertSame(569439, $r['acumulado']);
        $this->assertSame(211761, $r['disponible']);
        $this->assertSame(72.9, $r['porcentaje']);
        $this->assertSame(65100, $r['mensual_aprox']);
        $this->assertSame(63271, $r['promedio_mensual']);
        $this->assertSame([50348, 67918, 73036, 53703, 68477, 71060, 59164, 66140, 59593], $r['periodos']->pluck('total')->all());
        $this->assertSame(211761, $r['periodos']->last()->saldo);
        $this->assertSame('Septiembre 2026', $r['periodos']->last()->etiqueta);
    }

    public function test_cambio_de_serie_no_se_cuenta_y_genera_alerta(): void
    {
        $this->impresoras();
        $sg = Dispositivo::where('ubicacion', 'SG 9o.')->first();
        $sg->update(['serie' => 'R4P2400504']);
        Corte::create(['fecha' => '2026-10-25 00:00:00', 'dispositivo_id' => $sg->id, 'serie' => 'R4P2400504', 'contador' => 172000]);

        $c = new Contrato;
        $r = $c->resumen();

        $this->assertNull($r['periodos']->last()->producido[$sg->id]);   // 172,000 − 221,383 no es consumo
        $this->assertTrue(collect($r['alertas'])->contains(fn ($a) => str_contains($a, 'R4P1890108 → R4P2400504')));
        $this->assertNotEmpty($c->hallazgos($r));
    }

    public function test_en_curso_usa_la_ultima_lectura_diaria(): void
    {
        $this->impresoras();
        $lobby = Dispositivo::where('ubicacion', 'Lobby')->first();
        ContadorEquipo::create(['dispositivo_id' => $lobby->id, 'impresiones' => 100000, 'copias' => 42679, 'total' => 150000, 'escaneos' => 0, 'leido_en' => '2026-10-10 18:00:00']);

        $r = (new Contrato)->resumen();

        $this->assertSame(1000, $r['en_curso']->producido[$lobby->id]);   // 142,679 − 141,679 (corte 25/09)
        $this->assertSame(570439, $r['acumulado']);
    }

    public function test_pantallas_y_exportes_del_contrato(): void
    {
        $this->impresoras();
        $this->actingAs(User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => bcrypt('x'), 'role' => 'admin']));

        $this->get('/dashboard')->assertOk()->assertSee('569,439')->assertSee('Resumen del contrato');
        $this->get('/cortes')->assertOk()->assertSee('Septiembre 2026');
        $this->get('/export/control')->assertOk()->assertDownload();
        $this->get('/export/pdf/resumen')->assertOk()->assertDownload();
        $this->get('/configuracion')->assertOk()->assertSee('Guardar contrato');
        $this->assertSame(25, Configuracion::contrato()['dia_corte']);
    }

    public function test_dashboard_interactivo_selecciona_impresora_y_corte(): void
    {
        $this->impresoras();
        $this->actingAs(User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => bcrypt('x'), 'role' => 'admin']));
        $dab = Dispositivo::where('ubicacion', 'DAB 7o.')->first();

        Livewire::test(DashboardGeneral::class)
            ->assertDontSee('Producido en cada corte')
            ->call('seleccionarEquipo', $dab->id)
            ->assertSet('equipo', $dab->id)
            ->assertSee('Producido en cada corte')          // ficha de la impresora
            ->assertSee('138,796')                          // su acumulado ene–sep
            ->call('seleccionarPeriodo', '2026-08-26')
            ->assertSet('periodo', '2026-08-26')
            ->assertSee('corte del 26/08/2026')
            ->assertSee('66,140')                           // producido total de agosto
            ->call('seleccionarEquipo', $dab->id)           // segundo clic = quitar
            ->assertSet('equipo', null)
            ->call('limpiar')
            ->assertSet('periodo', null);
    }
}
