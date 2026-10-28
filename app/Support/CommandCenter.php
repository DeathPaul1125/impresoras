<?php

namespace App\Support;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lectura (solo lectura) de la contabilidad de trabajos de una Kyocera vía Command Center RX.
 * Inicia sesión como administrador y lee, por cuenta (clave), copias, impresiones y escaneos.
 * El panel no tiene API: se piden las mismas páginas y "modelos" JS que usa el navegador.
 */
class CommandCenter
{
    private CookieJar $cookies;

    private string $base;

    public function __construct(string $ip, private string $usuario, private string $password)
    {
        $this->base = "https://{$ip}";
        $this->cookies = new CookieJar;
    }

    private function get(string $ruta, ?string $referer = null): string
    {
        $r = Http::withOptions(['verify' => false, 'cookies' => $this->cookies])   // certificado autofirmado de la impresora
            ->withHeaders(['Referer' => $this->base.($referer ?? '/startwlm/Start_Wlm.htm')])
            ->timeout(20)->get($this->base.$ruta);

        return self::utf8($r->body());
    }

    /** Las páginas vienen en Windows-1252 (tildes de los nombres de cuenta). */
    public static function utf8(string $s): string
    {
        return mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }

    public function login(): void
    {
        $this->get('/', '/');
        $pagina = $this->get('/startwlm/Start_Wlm.htm', '/');

        // El CGI responde 404 si falta cualquiera de los campos del formulario: se envían todos.
        $campos = self::camposLogin($pagina);
        $campos = array_merge($campos, [
            'failhtmfile' => '/startwlm/Start_Wlm.htm', 'okhtmfile' => '/startwlm/Start_Wlm.htm',
            'func' => 'authLogin', 'arg01_UserName' => $this->usuario, 'arg02_Password' => $this->password,
            'arg03_LoginType' => '_mode_off', 'arg04_LoginFrom' => '_wlm_login', 'hidden' => 'hiddenvalue',
        ]);

        $r = Http::withOptions(['verify' => false, 'cookies' => $this->cookies])
            ->withHeaders(['Referer' => $this->base.'/startwlm/Start_Wlm.htm', 'Origin' => $this->base])
            ->asForm()->timeout(20)->post($this->base.'/startwlm/login.cgi', $campos);

        if (! str_contains($r->body(), '_login_admin')) {
            throw new RuntimeException('El Command Center rechazó el usuario o la contraseña de administrador.');
        }
    }

    /** @return array<string,string> campos del <form id="login"> con su valor por defecto */
    public static function camposLogin(string $html): array
    {
        $i = strpos($html, 'id="login"');
        if ($i === false) {
            throw new RuntimeException('No se encontró el formulario de inicio de sesión del Command Center.');
        }
        $form = substr($html, $i, (strpos($html, '</form>', $i) ?: strlen($html)) - $i);

        $campos = [];
        preg_match_all('/<(?:input|select|textarea)\b[^>]*>/i', $form, $m);
        foreach ($m[0] as $tag) {
            if (! preg_match('/name="([^"]+)"/', $tag, $n) || isset($campos[$n[1]])
                || preg_match('/type="(checkbox|radio|button|submit|image)"/i', $tag)) {
                continue;
            }
            $campos[$n[1]] = preg_match('/\bvalue="([^"]*)"/', $tag, $v) ? $v[1] : '';
        }

        return $campos;
    }

    /** @return list<array{privado:string,clave:string,nombre:string}> todas las cuentas (10 por página) */
    public function cuentas(): array
    {
        $cuentas = [];
        for ($pag = 1; $pag <= 50; $pag++) {
            $q = "arg1=1&arg2=0&arg3=&arg4=0&arg5=&arg6=&arg7={$pag}&arg8=0&arg9=&arg10=";
            $pagina = '/mngset/jobset/MngSet_JobAcc_JobAccLst.htm';
            $this->get("{$pagina}?{$q}");
            [$lote, $total] = self::parseLista($this->get("/js/jssrc/model/mngset/jobset/MngSet_JobAcc_JobAccLst.model.htm?{$q}", $pagina));

            $nuevas = array_filter($lote, fn ($c) => ! isset($cuentas[$c['privado']]));
            if (! $nuevas) {
                break;
            }
            foreach ($nuevas as $c) {
                $cuentas[$c['privado']] = $c;
            }
            if (count($cuentas) >= $total) {
                break;
            }
        }

        return array_values($cuentas);
    }

    /** @return array{0: list<array{privado:string,clave:string,nombre:string}>, 1: int} */
    public static function parseLista(string $modelo): array
    {
        preg_match_all("/sDeptPrivateID\[index\] = '([^']*)'/", $modelo, $priv);
        preg_match_all("/sDeptPublicID\[index\] = '([^']*)'/", $modelo, $pub);
        preg_match_all("/sDeptName\[index\] = '([^']*)'/", $modelo, $nom);
        $total = preg_match("/TotsearchResult = '(\d+)'/", $modelo, $t) ? (int) $t[1] : 0;

        $lote = [];
        foreach ($priv[1] as $i => $p) {
            $lote[] = ['privado' => $p, 'clave' => $pub[1][$i] ?? '', 'nombre' => trim(stripcslashes($nom[1][$i] ?? ''))];
        }

        return [$lote, $total];
    }

    /**
     * Contadores generales del equipo (Información del dispositivo → Contador). Respaldo de SNMP.
     * total = impresiones + copias + fax (el panel no expone el contador de vida de SNMP).
     *
     * @return array{modelo:null,serie:null,total:int,impresiones:int,copias:int,escaneos:int}
     */
    public function contadoresEquipo(): array
    {
        $leer = function (string $pagina): string {
            $this->get("/dvcinfo/dvccounter/{$pagina}.htm");

            return $this->get("/js/jssrc/model/dvcinfo/dvccounter/{$pagina}.model.htm", "/dvcinfo/dvccounter/{$pagina}.htm");
        };

        return self::parseEquipo($leer('DvcInfo_Counter_PrnCounter'), $leer('DvcInfo_Counter_ScanCounter'));
    }

    public static function parseEquipo(string $prn, string $scn): array
    {
        $n = fn (string $s, string $clave) => preg_match("/_pp\.{$clave}\s*=\s*(?:parseInt\()?\(?'(\d*)'/", $s, $m) ? (int) $m[1] : 0;
        $copias = $n($prn, 'copytotal');
        $impresiones = $n($prn, 'printertotal');
        if (! preg_match('/_pp\.printertotal/', $prn)) {
            throw new RuntimeException('El panel no devolvió los contadores del equipo.');
        }

        return [
            'modelo' => null, 'serie' => null,
            'total' => $copias + $impresiones + $n($prn, 'faxTotal'),
            'impresiones' => $impresiones,
            'copias' => $copias,
            'escaneos' => $n($scn, 'scanCopy') + $n($scn, 'scanBlackWhite') + $n($scn, 'scanOther'),
        ];
    }

    /** @return array{impresiones:int,copias:int,escaneos:int} contadores acumulados de una cuenta */
    public function contadores(string $privado): array
    {
        $q = "arg1=0&arg2=0&arg3=&arg4=0&arg5=&arg6=&arg7=1&arg8=0&arg9=&arg10=&arg11={$privado}";
        $lista = '/mngset/jobset/MngSet_JobAcc_JobAccLst.htm';

        $this->get("/mngset/jobset/MngSet_JobAcc_EchJobPrnCnt.htm?{$q}", $lista);
        $prn = $this->get('/js/jssrc/model/mngset/jobset/MngSet_JobAcc_EchJobPrnCnt.model.htm?'.str_replace('arg8=0&', 'arg8=0&arg8=0&', $q), '/mngset/jobset/MngSet_JobAcc_EchJobPrnCnt.htm');
        $this->get("/mngset/jobset/MngSet_JobAcc_EchJobScnCnt.htm?{$q}", $lista);
        $scn = $this->get("/js/jssrc/model/mngset/jobset/MngSet_JobAcc_EchJobScnCnt.model.htm?{$q}", '/mngset/jobset/MngSet_JobAcc_EchJobScnCnt.htm');

        return self::parseContadores($prn, $scn);
    }

    /** El último elemento de cada arreglo es el total de la cuenta. */
    public static function parseContadores(string $prn, string $scn): array
    {
        $ultimo = function (string $s, string $clave): int {
            if (! preg_match('/_pp\.'.$clave.'\s*=\s*\[(.*?)\];/s', $s, $m) || ! preg_match_all("/'(\d+)'/", $m[1], $n) || ! $n[1]) {
                return 0;
            }

            return (int) end($n[1]);
        };

        // duplexCounter = [una cara, doble cara, total]
        $duplex = preg_match('/_pp\.duplexCounter\s*=\s*\[(.*?)\];/s', $prn, $m) && preg_match_all("/'(\d+)'/", $m[1], $d) && count($d[1]) >= 2
            ? ['simplex' => (int) $d[1][0], 'duplex' => (int) $d[1][1]] : ['simplex' => null, 'duplex' => null];

        return [
            'impresiones' => $ultimo($prn, 'printerCounter'),
            'copias' => $ultimo($prn, 'copyCounter'),
            'escaneos' => $ultimo($scn, 'counterScanCounter'),
        ] + $duplex;
    }
}
