<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Support\Consumo;
use App\Support\ImportadorCsv;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\UnidadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumoTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): void
    {
        $this->seed(UnidadSeeder::class);
        $this->seed(ConfiguracionSeeder::class);
    }

    /** Importa un CSV y verifica que el costo sea (impresiones+copias)*0.17. */
    public function test_importa_csv_y_calcula_costo(): void
    {
        $this->seedBase();

        // Fila real del sistema viejo: cuenta 9001, 439 impr + 5286 copias => Q973.25
        $csv = "id_cuenta;nombre_cuenta;unidad;impresiones;copias;escaneados;fecha_corte;host;ip;modelo\n"
             ."9001;SG_Bililin Menendez;SG_;439;5286;3921;31/07/2026 11:09:03;KMC58A0E;192.100.10.50;ECOSYS M3655idn\n"
             ."2022;DAB_Durgui Estrada;DAB;816;2197;2027;06/04/2026 07:43:05;KMC58A0E;192.100.10.50;ECOSYS M3655idn\n";

        $ruta = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($ruta, $csv);

        $imp = (new ImportadorCsv)->procesar(
            $ruta,
            'prueba.csv',
            ['id_cuenta' => 0, 'nombre_cuenta' => 1, 'unidad' => 2, 'impresiones' => 3, 'copias' => 4, 'escaneados' => 5, 'fecha_corte' => 6, 'host' => 7, 'ip' => 8, 'modelo' => 9],
            ';', 'UTF-8', true, null,
        );
        unlink($ruta);

        $this->assertSame(2, $imp->registros);

        $filtros = ['fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31'];
        $consumo = new Consumo($filtros);
        $totales = $consumo->totales();

        // (439+5286+816+2197) * 0.17 = 8738 * 0.17 = 1485.46
        $this->assertSame(1255, $totales['impresiones']);
        $this->assertSame(7483, $totales['copias']);
        $this->assertSame(5948, $totales['escaneados']);
        $this->assertEqualsWithDelta(1485.46, $totales['costo_total'], 0.01);

        // La cuenta 9001 debe costar exactamente Q973.25
        $u9001 = $consumo->porUsuario()->firstWhere('id_cuenta', '9001');
        $this->assertEqualsWithDelta(973.25, (float) $u9001->costo_total, 0.01);
    }

    /** Cambiar el precio recalcula el costo. */
    public function test_precio_configurable_recalcula(): void
    {
        $this->seedBase();

        $csv = "id_cuenta;nombre_cuenta;impresiones;copias\n100;Prueba;10;10\n";
        $ruta = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($ruta, $csv);
        (new ImportadorCsv)->procesar($ruta, 'p.csv',
            ['id_cuenta' => 0, 'nombre_cuenta' => 1, 'impresiones' => 2, 'copias' => 3],
            ';', 'UTF-8', true, null);
        unlink($ruta);

        Configuracion::setPrecioUnitario(0.50);
        $totales = (new Consumo(['fecha_inicio' => '2000-01-01', 'fecha_fin' => '2100-01-01']))->totales();

        // 20 * 0.50 = 10.00
        $this->assertEqualsWithDelta(10.00, $totales['costo_total'], 0.01);
    }
}
