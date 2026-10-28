<?php

namespace Tests\Feature;

use App\Livewire\Historico;
use App\Models\ConsumoMensual;
use App\Models\Dispositivo;
use App\Models\User;
use App\Support\Historia;
use Database\Seeders\ConfiguracionSeeder;
use Database\Seeders\UnidadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HistoricoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ConfiguracionSeeder::class, UnidadSeeder::class]);
        Carbon::setTestNow('2026-10-28 12:00:00');
        Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC58AC2', 'direccion_ip' => '192.100.10.110']);

        // El sistema anterior devuelve, para cada mes, el consumo por usuario de la impresora (solo DAB tiene datos).
        Http::fake(function ($req) {
            parse_str(parse_url($req->url(), PHP_URL_QUERY), $q);
            $mes = substr($q['fecha_inicio'], 0, 7);
            $cab = "\xEF\xBB\xBF\"ID Cuenta\",\"Nombre Cuenta\",Unidad,Impresiones,Copias,Escaneos,\"Costo Total\",\"Último Corte\"\n";
            $filas = match ($mes) {
                '2025-01' => "2846,\"DAB_Marylin Castro\",DAB,100,50,10,25.5,\"1/02/2025 10:00:00\"\n",
                '2026-01' => "2846,\"DAB_Marylin Castro\",DAB,120,60,5,30.6,\"1/02/2026 10:00:00\"\n0808,\"UAI_Lizbeth Mazariegos\",UAI,0,30,2,5.1,\"1/02/2026 10:00:00\"\n",
                default => '',
            };

            return Http::response($q['dispositivo'] === '2' ? $cab.$filas : $cab);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_importa_y_compara_los_mismos_meses(): void
    {
        $this->artisan('impresoras:importar-historia --desde=2025-01 --hasta=2026-01')->assertSuccessful();
        $this->artisan('impresoras:importar-historia --desde=2026-01 --hasta=2026-01')->assertSuccessful();   // repetir no duplica

        $this->assertSame(3, ConsumoMensual::count());
        $h = new Historia;
        $this->assertSame([2025, 2026], $h->anios());
        $c = $h->comparativo();
        $this->assertSame(1, $c['meses']);   // 2026 solo tiene enero: se compara enero contra enero
        $this->assertSame([150, 210, 2], [$c['anterior']->bn, $c['actual']->bn, $c['actual']->usuarios]);
        $this->assertSame(['DAB', 'UAI'], $h->porUnidad()->pluck('unidad')->all());
        $this->assertSame('DAB_Marylin Castro', $h->usuarios(2026)->first()->nombre);
    }

    public function test_pagina_y_excel(): void
    {
        $this->artisan('impresoras:importar-historia --desde=2025-01 --hasta=2026-01');
        $this->actingAs(User::create(['name' => 'C', 'email' => 'c@a.com', 'password' => bcrypt('x'), 'role' => 'consulta']));

        Livewire::test(Historico::class)->assertOk()->assertSee('Impresoras mes a mes')->assertSee('DAB_Marylin Castro')
            ->call('$set', 'anio', 2025)->assertDontSee('UAI_Lizbeth Mazariegos');
        $this->get('/historico')->assertOk();
        $this->get('/export/historia')->assertOk()->assertDownload();
    }
}
