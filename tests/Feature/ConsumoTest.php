<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\ContadorEquipo;
use App\Models\Dispositivo;
use App\Support\Consumo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumoTest extends TestCase
{
    use RefreshDatabase;

    /** Guarda lecturas [fecha => [total, impresiones, copias, escaneos]] de una impresora. */
    private function impresora(string $ubicacion, array $lecturas, string $ip = '10.0.0.1'): Dispositivo
    {
        $d = Dispositivo::create(['ubicacion' => $ubicacion, 'nombre_host' => 'KMC'.$ubicacion, 'direccion_ip' => $ip]);
        foreach ($lecturas as $fecha => [$t, $i, $c, $e]) {
            ContadorEquipo::create(['dispositivo_id' => $d->id, 'total' => $t, 'impresiones' => $i, 'copias' => $c, 'escaneos' => $e, 'leido_en' => "$fecha 18:00:02"]);
        }

        return $d;
    }

    public function test_consumo_diario_es_la_diferencia_con_la_lectura_anterior(): void
    {
        $a = $this->impresora('DAB', [
            '2026-10-26' => [1000, 600, 400, 50],
            '2026-10-27' => [1100, 660, 440, 55],   // +60 impr, +40 copias, +5 scan
            '2026-10-29' => [1300, 760, 540, 70],   // falló el 28: la diferencia (2 días) cae el 29
        ]);
        $this->impresora('SG', [
            '2026-10-26' => [500, 300, 200, 0],
            '2026-10-27' => [520, 310, 210, 0],
        ], '10.0.0.2');

        $c = new Consumo(['fecha_inicio' => '2026-10-27', 'fecha_fin' => '2026-10-31'], 0.17);

        $dias = $c->porDia()->keyBy('fecha');
        $this->assertSame(['2026-10-27', '2026-10-29'], $dias->keys()->all());
        $this->assertSame(120, $dias['2026-10-27']->total);          // 100 DAB + 20 SG
        $this->assertSame(200, $dias['2026-10-29']->total);

        $t = $c->totales();
        $this->assertSame(170, $t['impresiones']);                    // DAB 60 + 100, SG 10
        $this->assertSame(150, $t['copias']);                         // DAB 40 + 100, SG 10
        $this->assertEqualsWithDelta(320 * 0.17, $t['costo_total'], 0.001);
        $this->assertSame(2, $t['dias']);

        // La primera lectura (26) no genera consumo: es la base.
        $this->assertSame(0, (new Consumo(['fecha_inicio' => '2026-10-26', 'fecha_fin' => '2026-10-26']))->totales()['total']);

        // Filtro por impresora.
        $soloA = new Consumo(['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31', 'dispositivo' => $a->id], 0.17);
        $this->assertSame(300, $soloA->totales()['total']);
        $this->assertSame(['DAB'], $soloA->porDispositivo()->pluck('ubicacion')->all());
    }

    public function test_reinicio_de_contador_y_oid_vacio_no_dan_negativos_ni_picos(): void
    {
        $this->impresora('DAB', [
            '2026-10-26' => [1000, 600, 400, 50],
            '2026-10-27' => [1010, 605, null, 50],   // copias sin respuesta: no cuenta ese campo
            '2026-10-28' => [1020, 610, 410, 50],   // copias: 410 − 400 (último conocido)
            '2026-10-29' => [5, 3, 2, 0],           // equipo reiniciado → 0, no negativo
            '2026-10-30' => [15, 8, 7, 0],
        ]);

        $dias = (new Consumo(['fecha_inicio' => '2026-10-27', 'fecha_fin' => '2026-10-30']))->porDia()->keyBy('fecha');

        $this->assertSame(0, $dias['2026-10-27']->copias);
        $this->assertSame(10, $dias['2026-10-28']->copias);
        $this->assertSame(0, $dias['2026-10-29']->total);
        $this->assertSame(10, $dias['2026-10-30']->total);
    }

    public function test_precio_configurable_y_tendencia_mensual(): void
    {
        Configuracion::setPrecioUnitario(0.25);
        $this->impresora('DAB', [
            '2026-09-29' => [0, 0, 0, 0],
            '2026-09-30' => [100, 100, 0, 0],
            '2026-10-01' => [300, 300, 0, 0],
        ]);

        $tend = (new Consumo)->tendenciaMensual();

        $this->assertSame(['2026-09', '2026-10'], $tend->pluck('periodo')->all());
        $this->assertEqualsWithDelta(25.0, $tend[0]->costo_total, 0.001);
        $this->assertEqualsWithDelta(50.0, $tend[1]->costo_total, 0.001);
        $this->assertSame(100.0, $tend[1]->variacion);
    }
}
