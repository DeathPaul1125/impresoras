<?php

namespace Tests\Feature;

use App\Mail\ReporteConsumo;
use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Support\ReportePeriodo;
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
            $fila = $m->datos['impresoras']->first();

            return $m->hasTo('a@senabed.gob.gt')
                && str_contains($m->titulo, '29/10/2026')
                && [$fila->ubicacion, $fila->impresiones, $fila->copias, $fila->escaneos, $fila->total] === ['DAB 7o.', 30, 30, 5, 60]
                && round($m->datos['totales']['costo_total'], 2) === 10.2
                && $m->datos['variacion']['total'] === -40.0   // 60 páginas vs. 100 del día anterior
                && $m->datos['dias']->isEmpty();
        });
    }

    public function test_semanal_incluye_detalle_por_dia(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-30 18:05:00'));   // viernes
        $this->artisan('impresoras:enviar-reporte semanal')->assertSuccessful();

        Mail::assertSent(ReporteConsumo::class, fn (ReporteConsumo $m) => str_contains($m->titulo, '26/10/2026 – 30/10/2026')
            && [$m->datos['totales']['impresiones'], $m->datos['totales']['copias'], $m->datos['totales']['total']] === [90, 60, 160]
            && $m->datos['dias']->pluck('fecha')->all() === ['2026-10-28', '2026-10-29']);
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

        Mail::to('a@senabed.gob.gt')->send(new ReporteConsumo(ReportePeriodo::dia(Carbon::parse('2026-10-28'))));

        $correo = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $adjuntos = $correo->getAttachments();
        $this->assertSame(['reporte-diario-28102026.xlsx', 'reporte-diario-28102026.pdf'], array_map(fn ($a) => $a->getFilename(), array_values(array_filter($adjuntos, fn ($a) => $a->getFilename() !== 'logo-senabed-die.png'))));
        $this->assertStringStartsWith('%PDF', end($adjuntos)->getBody());
        $this->assertStringContainsString('Atención de equipos', $correo->getHtmlBody());
    }

    /** Texto de todas las hojas de un .xlsx (cadenas compartidas y celdas). */
    private static function textoExcel(string $xlsx): string
    {
        $archivo = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($archivo, $xlsx);
        $z = new \ZipArchive;
        $z->open($archivo);
        $texto = '';
        for ($i = 0; $i < $z->numFiles; $i++) {
            if (str_starts_with($z->getNameIndex($i), 'xl/')) {
                $texto .= $z->getFromIndex($i);
            }
        }
        $z->close();
        unlink($archivo);

        return $texto;
    }

    public function test_los_reportes_no_muestran_claves_y_dicen_que_imprimio_cada_usuario(): void
    {
        $this->travelTo(Carbon::parse('2026-10-29 18:30:00'));
        $d = Dispositivo::first();
        $cuenta = \App\Models\Cuenta::create(['id_cuenta' => '9395', 'nombre_cuenta' => 'DAF_Víctor López']);
        foreach ([['2026-10-28 18:02:00', 100], ['2026-10-29 18:02:00', 109]] as [$f, $copias]) {
            \App\Models\ContadorCuenta::create(['cuenta_id' => $cuenta->id, 'dispositivo_id' => $d->id, 'impresiones' => 0, 'copias' => $copias, 'escaneos' => 0, 'leido_en' => $f]);
        }
        foreach ([[1, 'Informe mensual.docx', 4, '09:15'], [2, 'Informe mensual.docx', 4, '11:00'], [3, 'Oficio 25-2026.pdf', 1, '14:40']] as [$n, $doc, $pag, $h]) {
            \App\Models\Trabajo::create(['dispositivo_id' => $d->id, 'origen' => 'impresion', 'trabajo' => $n, 'tipo' => 'impresion', 'documento' => $doc,
                'usuario' => 'lopez.victor', 'paginas' => $pag, 'originales' => $pag, 'juegos' => 1, 'estado' => 'completado', 'inicio' => "2026-10-29 {$h}:00"]);
        }

        $m = new ReporteConsumo(ReportePeriodo::dia(today()));
        $html = $m->render();
        $excel = self::textoExcel(\Maatwebsite\Excel\Facades\Excel::raw(ReporteConsumo::excel($m->reporte), \Maatwebsite\Excel\Excel::XLSX));

        // Sin claves personales en el correo ni en el Excel adjunto (el PDF usa las mismas hojas).
        foreach ([$html, $excel] as $texto) {
            $this->assertStringNotContainsString('9395', $texto);
            $this->assertStringNotContainsString('>Clave<', $texto);
        }
        $this->assertStringContainsString('DAF_Víctor López', $html);

        // Qué imprimió cada usuario: agrupado por usuario de Windows cuando el trabajo no trae cuenta.
        $u = $m->datos['por_usuario']->first();
        $this->assertSame(['lopez.victor', 3, 9, ['DAB 7o.']], [$u->quien, $u->trabajos, $u->paginas, $u->impresoras]);
        $this->assertSame(['Informe mensual.docx', 2, 8], [$u->documentos[0]->documento, $u->documentos[0]->veces, $u->documentos[0]->paginas]);
        $this->assertStringContainsString('Qué imprimió cada usuario', $html);
        $this->assertStringContainsString('Oficio 25-2026.pdf', $html);
        $this->assertStringContainsString('Trabajos por usuario', $excel);

        // El Excel del reporte de corte tampoco lleva la clave.
        \App\Models\Corte::create(['fecha' => '2026-09-25 00:00:00', 'dispositivo_id' => $d->id, 'serie' => 'S1', 'contador' => 100]);
        \App\Models\Corte::create(['fecha' => '2026-10-29 18:10:00', 'dispositivo_id' => $d->id, 'serie' => 'S1', 'contador' => 340]);
        $corte = self::textoExcel(\Maatwebsite\Excel\Facades\Excel::raw(new \App\Exports\ControlContadoresExport, \Maatwebsite\Excel\Excel::XLSX));
        $this->assertStringContainsString('DAF_Víctor López', $corte);
        $this->assertStringNotContainsString('9395', $corte);
    }
}
