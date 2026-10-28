<?php

namespace Tests\Feature;

use App\Mail\ReporteConsumo;
use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Configuracion::setCorreosReportes(['a@senabed.gob.gt']);

        $d = Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1']);
        foreach ([
            ['2026-10-27 18:00:03', 100, 60, 40, 10],
            ['2026-10-28 18:00:03', 200, 120, 70, 20],
            ['2026-10-29 18:00:03', 260, 150, 100, 25],
        ] as [$f, $t, $i, $c, $e]) {
            ContadorEquipo::create(['dispositivo_id' => $d->id, 'total' => $t, 'impresiones' => $i, 'copias' => $c, 'escaneos' => $e, 'leido_en' => $f]);
        }
    }

    public function test_diario_envia_la_diferencia_con_el_dia_anterior(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-29 18:05:00'));

        $this->artisan('impresoras:enviar-reporte diario')->assertSuccessful();

        Mail::assertSent(ReporteConsumo::class, function (ReporteConsumo $m) {
            [$fila, $total] = $m->rows;

            return $m->hasTo('a@senabed.gob.gt')
                && str_contains($m->titulo, '29/10/2026')
                && $fila === ['DAB 7o.', 'KMC1', '10.0.0.1', 30, 30, 5, 60, 'Q10.20']
                && $total[7] === 'Q10.20'
                && $m->dias === [];
        });
    }

    public function test_semanal_incluye_detalle_por_dia(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-30 18:05:00'));   // viernes
        $this->artisan('impresoras:enviar-reporte semanal')->assertSuccessful();

        Mail::assertSent(ReporteConsumo::class, fn (ReporteConsumo $m) => str_contains($m->titulo, '26/10/2026 – 30/10/2026')
            && array_slice($m->rows[0], 3, 4) === [90, 60, 15, 160]
            && array_column($m->dias, 0) === ['28/10/2026', '29/10/2026']);
    }

    public function test_mensual_envia_el_reporte_de_corte(): void
    {
        Mail::fake();
        \App\Models\Configuracion::setContrato(['numero' => '4912', 'bolson' => 1000, 'inicio' => '2026-09-01', 'fin' => '2026-12-31', 'dia_corte' => 25]);
        $d = Dispositivo::first();
        \App\Models\Corte::create(['fecha' => '2026-09-25 00:00:00', 'dispositivo_id' => $d->id, 'serie' => 'S1', 'contador' => 100]);
        \App\Models\Corte::create(['fecha' => '2026-10-25 00:00:00', 'dispositivo_id' => $d->id, 'serie' => 'S1', 'contador' => 340]);

        $this->artisan('impresoras:enviar-reporte mensual')->assertSuccessful();

        Mail::assertSent(\App\Mail\ReporteCorte::class, fn ($m) => $m->hasTo('a@senabed.gob.gt')
            && $m->r['periodos']->last()->total === 240
            && str_contains($m->envelope()->subject, 'Corte Octubre 2026'));
    }

    public function test_sin_correos_no_envia(): void
    {
        Mail::fake();
        Configuracion::setCorreosReportes([]);

        $this->artisan('impresoras:enviar-reporte semanal')->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_adjuntos_excel_y_pdf_se_generan(): void
    {
        config(['mail.default' => 'array']);

        Mail::to('a@senabed.gob.gt')->send(new ReporteConsumo('Reporte diario', ['A', 'B'], [['x', 1], ['TOTAL', 1]], [['28/10/2026', 1]]));

        $adjuntos = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage()->getAttachments();
        $this->assertSame(['reporte-diario.xlsx', 'reporte-diario.pdf'], array_map(fn ($a) => $a->getFilename(), $adjuntos));
        $this->assertStringStartsWith('%PDF', $adjuntos[1]->getBody());
    }
}
