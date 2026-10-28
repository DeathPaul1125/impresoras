<?php

namespace Tests\Feature;

use App\Models\ContadorCuenta;
use App\Models\ContadorEquipo;
use App\Models\Cuenta;
use App\Models\Dispositivo;
use App\Models\User;
use App\Support\CommandCenter;
use App\Support\ConsumoCuentas;
use App\Support\Lector;
use Database\Seeders\UnidadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CuentasTest extends TestCase
{
    use RefreshDatabase;

    // Fragmentos reales (recortados) de lo que devuelve el Command Center de una ECOSYS M3655idn.
    private const FORM = '<form action="../startwlm/login.cgi" method="post" id="login" name="login">
        <input type="hidden" name="failhtmfile" data-bind="value: failhtmlfileValue">
        <input type="hidden" name="func" data-bind="value: funcValue"/>
        <input type="hidden" id="arg03_LoginType" name="arg03_LoginType" />
        <input type="hidden" name="hidden" value="hiddenvalue">
        <input type="checkbox" class="medium" id="Auto">
        <input type="text" name="arg01_UserName" id="arg01_UserName"><input type="password" name="arg02_Password">
        <input type="submit" name="Login" value="mes()[20]"></form>';

    private const LISTA = "_pp.searchResult = parseInt('2', 10);\n_pp.TotsearchResult = '2';\n"
        ."_pp.sDeptPrivateID[index] = '1';\n_pp.sDeptPublicID[index] = '808';\n_pp.sDeptName[index] = 'UAI_Lizbeth Mazariegos';\n"
        ."_pp.sDeptPrivateID[index] = '31';\n_pp.sDeptPublicID[index] = '0214';\n_pp.sDeptName[index] = 'DAF_Suygly Barreno';\n";

    private const PRN = "_pp.copyCounter =\n[\n'127', '0', '0', '127'\n];\n_pp.printerCounterMax =\n[\n'_limit_off'\n];\n_pp.printerCounter =\n[\n'5', '0', '40'\n];\n";

    private const SCN = "_pp.counterMax = '_limit_off';\n_pp.counterScanCounter =\n[\n'127', '0', '3', '130'\n];\n";

    private const EQUIPO_PRN = "_pp.copytotal = ('171475').toString();\n_pp.printertotal = ('211869').toString();\n_pp.faxTotal = ('0').toString();\n";

    private const EQUIPO_SCN = "_pp.scanCopy = parseInt('134236', 10);\n_pp.scanBlackWhite = parseInt('0', 10);\n_pp.scanOther = parseInt('144274', 10);\n";

    private function fakePanel(): void
    {
        Http::fake([
            '*/startwlm/Start_Wlm.htm' => Http::response(self::FORM),
            '*/startwlm/login.cgi' => Http::response('Start_Wlm.model.htm?arg1=7&arg8=_login_admin'),
            '*/MngSet_JobAcc_JobAccLst.model.htm*' => Http::response(self::LISTA),
            '*/MngSet_JobAcc_EchJobPrnCnt.model.htm*' => Http::response(self::PRN),
            '*/MngSet_JobAcc_EchJobScnCnt.model.htm*' => Http::response(self::SCN),
            '*/DvcInfo_Counter_PrnCounter.model.htm' => Http::response(self::EQUIPO_PRN),
            '*/DvcInfo_Counter_ScanCounter.model.htm' => Http::response(self::EQUIPO_SCN),
            '*' => Http::response(''),
        ]);
    }

    public function test_parsers_del_command_center(): void
    {
        $this->assertSame(['failhtmfile', 'func', 'arg03_LoginType', 'hidden', 'arg01_UserName', 'arg02_Password'], array_keys(CommandCenter::camposLogin(self::FORM)));

        [$lote, $total] = CommandCenter::parseLista(self::LISTA);
        $this->assertSame(2, $total);
        $this->assertSame(['privado' => '1', 'clave' => '808', 'nombre' => 'UAI_Lizbeth Mazariegos'], $lote[0]);

        // El último elemento de cada arreglo es el total de la cuenta.
        $this->assertSame(['impresiones' => 40, 'copias' => 127, 'escaneos' => 130], CommandCenter::parseContadores(self::PRN, self::SCN));

        $equipo = CommandCenter::parseEquipo(self::EQUIPO_PRN, self::EQUIPO_SCN);
        $this->assertSame([211869, 171475, 278510], [$equipo['impresiones'], $equipo['copias'], $equipo['escaneos']]);

        // Nombres en Windows-1252 se pasan a UTF-8.
        $this->assertSame('DAF_José', CommandCenter::utf8(mb_convert_encoding('DAF_José', 'Windows-1252', 'UTF-8')));
    }

    public function test_leer_cuentas_guarda_por_clave_con_su_unidad(): void
    {
        $this->seed(UnidadSeeder::class);
        Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1']);
        $this->fakePanel();

        $this->artisan('impresoras:leer-cuentas')->assertSuccessful();

        $this->assertSame(2, ContadorCuenta::count());
        $suygly = Cuenta::with('unidad')->where('id_cuenta', '214')->first();   // '0214' se guarda sin ceros
        $this->assertSame('DAF', $suygly->unidad->codigo);
        $this->assertSame(['impresiones' => 40, 'copias' => 127, 'escaneos' => 130],
            ContadorCuenta::where('cuenta_id', $suygly->id)->first()->only(['impresiones', 'copias', 'escaneos']));
    }

    public function test_consumo_por_usuario_tolera_reinicios(): void
    {
        $this->seed(UnidadSeeder::class);
        $d = Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1']);
        $a = Cuenta::create(['id_cuenta' => '9377', 'nombre_cuenta' => 'DAB_Erick Gatica', 'unidad_id' => \App\Models\Unidad::where('codigo', 'DAB')->value('id')]);
        foreach ([
            ['2026-10-26 18:02', 100, 50, 10],
            ['2026-10-27 18:02', 130, 60, 15],   // +30 impresiones, +10 copias
            ['2026-10-28 18:02', 20, 5, 3],      // reinicio en la impresora: cuenta lo nuevo (20, 5, 3)
        ] as [$f, $i, $c, $e]) {
            ContadorCuenta::create(['cuenta_id' => $a->id, 'dispositivo_id' => $d->id, 'impresiones' => $i, 'copias' => $c, 'escaneos' => $e, 'leido_en' => $f]);
        }

        $c = new ConsumoCuentas(['fecha_inicio' => '2026-10-27', 'fecha_fin' => '2026-10-28'], 0.17);
        $u = $c->porUsuario()->first();
        $this->assertSame([50, 15, 8], [$u->impresiones, $u->copias, $u->escaneos]);
        $this->assertEqualsWithDelta(65 * 0.17, $u->costo_total, 0.001);
        $this->assertSame('DAB', $c->porUnidad()->first()->unidad);
        $this->assertSame([$d->id => 65], $c->promedioPorUsuario());

        // Rango exacto de corte a corte (fecha y hora).
        $corte = new ConsumoCuentas(['desde' => '2026-10-26 18:02', 'hasta' => '2026-10-27 18:02']);
        $this->assertSame(30, $corte->porUsuario()->first()->impresiones);
    }

    public function test_lector_usa_el_panel_cuando_snmp_no_responde(): void
    {
        $this->fakePanel();

        $l = Lector::leer('127.0.0.1');   // sin agente SNMP en localhost

        $this->assertSame('panel', $l['fuente']);
        $this->assertSame([211869, 171475, 278510], [$l['impresiones'], $l['copias'], $l['escaneos']]);
    }

    public function test_analisis_muestra_aviso_y_luego_usuarios(): void
    {
        $this->seed(UnidadSeeder::class);
        $this->actingAs(User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => bcrypt('x'), 'role' => 'admin']));
        $this->get('/tablero')->assertOk()->assertSee('después de la segunda lectura');

        $d = Dispositivo::create(['ubicacion' => 'DAB 7o.', 'nombre_host' => 'KMC1', 'direccion_ip' => '10.0.0.1']);
        $a = Cuenta::create(['id_cuenta' => '9377', 'nombre_cuenta' => 'DAB_Erick Gatica']);
        ContadorCuenta::create(['cuenta_id' => $a->id, 'dispositivo_id' => $d->id, 'impresiones' => 1, 'copias' => 1, 'escaneos' => 0, 'leido_en' => today()->subDay()]);
        ContadorCuenta::create(['cuenta_id' => $a->id, 'dispositivo_id' => $d->id, 'impresiones' => 11, 'copias' => 1, 'escaneos' => 0, 'leido_en' => today()]);

        $this->get('/tablero')->assertOk()->assertSee('DAB_Erick Gatica')->assertSee('Consumo por unidad');
        $this->get('/export/usuarios')->assertOk()->assertDownload();
        $this->get('/export/pdf/unidades')->assertOk()->assertDownload();
    }
}
