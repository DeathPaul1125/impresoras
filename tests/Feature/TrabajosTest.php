<?php

namespace Tests\Feature;

use App\Livewire\DashboardGeneral;
use App\Livewire\Trabajos;
use App\Mail\ReporteConsumo;
use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Models\Trabajo;
use App\Models\User;
use App\Support\AlertasImpresora;
use App\Support\ReportePeriodo;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Registro de trabajos, estado de los equipos y reporte detallado de fin de mes. */
class TrabajosTest extends TestCase
{
    use RefreshDatabase;

    private Dispositivo $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfiguracionSeeder::class);
        Carbon::setTestNow('2026-10-31 18:30:00');

        $this->d = Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1',
            'estado' => ['estado' => 'disponible', 'toner' => ['nombre' => 'TK-3182', 'pct' => 90], 'alertas' => [], 'simplex' => 1500, 'duplex' => 600],
            'estado_en' => now()]);

        // Día anterior al mes (base) y fin de mes: papel y dúplex por diferencia; el tóner sube de 5% a 90% = cartucho nuevo.
        foreach ([['2026-09-30 18:00', 1000, ['Letter' => 800, 'Legal' => 200], 1000, 500, 40],
            ['2026-10-15 18:00', 1100, ['Letter' => 870, 'Legal' => 230], 1060, 520, 5],
            ['2026-10-31 18:00', 1300, ['Letter' => 1000, 'Legal' => 300], 1150, 575, 90]] as [$f, $t, $papel, $s, $dx, $toner]) {
            ContadorEquipo::create(['dispositivo_id' => $this->d->id, 'total' => $t, 'impresiones' => $t, 'copias' => 0, 'escaneos' => 0, 'leido_en' => $f,
                'detalle' => ['papel' => $papel, 'simplex' => $s, 'duplex' => $dx, 'toner' => ['pct' => $toner]]]);
        }

        foreach ([[1, 'impresion', 'Informe.docx', 'DAB_Ana', 4, '2026-10-20 09:15:00'], [2, 'copia', null, 'DAB_Luis', 10, '2026-10-21 14:40:00'],
            [3, 'impresion', 'Viejo.pdf', 'DAB_Ana', 2, '2026-09-28 10:00:00']] as [$n, $tipo, $doc, $cta, $pag, $ini]) {
            Trabajo::create(['dispositivo_id' => $this->d->id, 'origen' => 'impresion', 'trabajo' => $n, 'tipo' => $tipo, 'documento' => $doc,
                'cuenta' => $cta, 'paginas' => $pag, 'originales' => $pag, 'juegos' => 1, 'estado' => 'completado', 'inicio' => $ini]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $rol): User
    {
        return User::create(['name' => $rol, 'email' => "{$rol}@a.com", 'password' => bcrypt('x'), 'role' => $rol]);
    }

    public function test_pagina_filtra_y_solo_admin(): void
    {
        $this->actingAs($this->usuario('consulta'))->get('/trabajos')->assertForbidden();
        $this->get('/export/trabajos')->assertForbidden();

        $this->actingAs($this->usuario('admin'));
        Livewire::test(Trabajos::class)
            ->set('desde', '2026-10-01')->set('hasta', '2026-10-31')
            ->assertSee('Informe.docx')->assertDontSee('Viejo.pdf')
            ->set('buscar', 'Luis')->assertSee('DAB_Luis')->assertDontSee('Informe.docx');
        $this->get('/export/trabajos?desde=2026-10-01')->assertOk()->assertDownload();
    }

    public function test_reporte_mensual_calcula_papel_duplex_toner_y_horas(): void
    {
        $r = ReportePeriodo::mes(today());
        $this->assertSame('Octubre 2026', $r->etiqueta());

        $papel = $r->papel();
        $this->assertSame(['Impresora', 'Carta', 'Oficio (legal)', 'Una cara', 'Doble cara', '% doble cara'], $papel['headings']);
        $this->assertSame(['DAB 7o.', 200, 100, 150, 75, '33.3%'], $papel['rows'][0]);

        $this->assertSame(['DAB 7o.', 'TK-3182', '40%', '90%', 1], $r->toner()['rows'][0]);
        $this->assertSame([['09:00 – 09:59', 4, 0, 0, 0, 0, 1], ['14:00 – 14:59', 0, 10, 0, 0, 0, 1]], $r->porHora()['rows']);
        $this->assertCount(2, collect($r->hojas())->firstWhere('titulo', 'Registro de trabajos')['rows']);
    }

    public function test_reporte_detallado_se_envia_con_adjuntos(): void
    {
        Mail::fake();
        Configuracion::setCorreosReportes(['a@senabed.gob.gt']);

        $this->artisan('impresoras:enviar-reporte detallado')->assertSuccessful();

        Mail::assertSent(ReporteConsumo::class, fn (ReporteConsumo $m) => $m->hasTo('a@senabed.gob.gt')
            && $m->titulo === 'Reporte detallado de Octubre 2026' && count($m->attachments()) === 2 && $m->datos['totales']['trabajos'] === 2);
    }

    public function test_alertas_se_describen_en_espanol(): void
    {
        $a = AlertasImpresora::describir(['severidad' => 5, 'nivel_usuario' => 3, 'grupo' => 8, 'indice' => 2, 'codigo' => 808], ['8.2' => 'Cassette 1']);
        $this->assertSame(['codigo' => 808, 'nivel' => 'advertencia', 'texto' => 'Cassette 1: sin papel', 'accion' => 'Cargar papel en la bandeja.', 'quien' => 'Cualquier usuario'], $a);

        $this->assertSame('info', AlertasImpresora::describir(['severidad' => 4, 'nivel_usuario' => 6, 'grupo' => 5, 'indice' => 1, 'codigo' => 503])['nivel']);
        $this->assertSame('critica', AlertasImpresora::describir(['severidad' => 3, 'nivel_usuario' => 3, 'grupo' => 13, 'indice' => 1, 'codigo' => 8])['nivel']);
        $this->assertSame('Recorrido del papel: atasco de papel', AlertasImpresora::describir(['severidad' => 3, 'nivel_usuario' => 3, 'grupo' => 13, 'indice' => 1, 'codigo' => 8])['texto']);

        // Formato anterior (solo código) y filtro de las de rutina.
        $this->assertSame('Sin papel', AlertasImpresora::texto(['codigo' => 808]));
        $this->assertCount(1, AlertasImpresora::activas([['codigo' => 503], ['codigo' => 808], ['codigo' => 1001, 'nivel' => 'info']]));
    }

    public function test_dashboard_muestra_estado_de_equipos(): void
    {
        $this->actingAs($this->usuario('admin'));
        Livewire::test(DashboardGeneral::class)->assertSee('DAB 7o.')->assertSee('90%');
    }
}
