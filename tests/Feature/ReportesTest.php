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

    private function lecturas(): void
    {
        $d = Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1']);
        foreach ([
            ['2026-10-27 18:00:03', 100, 60, 40, 10],
            ['2026-10-28 18:00:03', 200, 120, 70, 20],
            ['2026-10-29 18:00:03', 260, 150, 100, 25],
        ] as [$f, $t, $i, $c, $e]) {
            ContadorEquipo::create(['dispositivo_id' => $d->id, 'total' => $t, 'impresiones' => $i, 'copias' => $c, 'escaneos' => $e, 'leido_en' => $f]);
        }
    }

    public function test_diario_envia_diferencia_del_ultimo_dia(): void
    {
        Mail::fake();
        $this->lecturas();
        Configuracion::setCorreosReportes(['a@senabed.gob.gt']);
        $this->travelTo(Carbon::parse('2026-10-30 00:05:00', 'UTC'));   // 29/10 18:05 Guatemala

        $this->artisan('impresoras:enviar-reporte diario')->assertSuccessful();

        Mail::assertSent(ReporteConsumo::class, function (ReporteConsumo $m) {
            [$fila, $total] = $m->rows;

            return $m->hasTo('a@senabed.gob.gt')
                && $fila[3] === '28/10/2026 18:00' && $fila[4] === '29/10/2026 18:00'
                && array_slice($fila, 5) === [30, 30, 5, 60, 'Q10.20']
                && $total[9] === 'Q10.20';
        });
    }

    public function test_mensual_sin_lectura_previa_usa_la_primera_del_periodo(): void
    {
        Mail::fake();
        $this->lecturas();
        Configuracion::setCorreosReportes(['a@senabed.gob.gt']);
        $this->travelTo(Carbon::parse('2026-11-01 00:05:00', 'UTC'));   // 31/10 18:05 Guatemala

        $this->artisan('impresoras:enviar-reporte mensual')->assertSuccessful();

        Mail::assertSent(ReporteConsumo::class, fn (ReporteConsumo $m) => $m->rows[0][3] === '27/10/2026 18:00'
            && array_slice($m->rows[0], 5, 4) === [90, 60, 15, 160]);
    }

    public function test_sin_correos_no_envia(): void
    {
        Mail::fake();

        $this->artisan('impresoras:enviar-reporte semanal')->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_adjuntos_excel_y_pdf_se_generan(): void
    {
        config(['mail.default' => 'array']);

        Mail::to('a@senabed.gob.gt')->send(new ReporteConsumo('Reporte diario', ['A', 'B'], [['x', 1], ['TOTAL', 1]]));

        $adjuntos = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage()->getAttachments();
        $this->assertSame(['reporte-diario.xlsx', 'reporte-diario.pdf'], array_map(fn ($a) => $a->getFilename(), $adjuntos));
        $this->assertStringStartsWith('%PDF', $adjuntos[1]->getBody());
    }
}
