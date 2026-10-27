<?php

namespace App\Support;

use App\Models\Cuenta;
use App\Models\Dispositivo;
use App\Models\Importacion;
use App\Models\Lectura;
use App\Models\Modelo;
use App\Models\Unidad;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Importa un CSV de contadores. El mapeo asocia cada campo destino al índice de
 * columna del CSV. Crea cuentas/dispositivos/modelos faltantes e inserta lecturas.
 *
 * $mapeo = ['id_cuenta'=>0, 'nombre_cuenta'=>1, 'unidad'=>2, 'impresiones'=>3,
 *           'copias'=>4, 'escaneados'=>5, 'fecha_corte'=>6, 'host'=>7, 'ip'=>8, 'modelo'=>9]
 * (los campos no mapeados valen null y se omiten)
 */
class ImportadorCsv
{
    public const DELIMITADORES = [';' => ';', ',' => ',', "\t" => 'Tabulación', '|' => '|'];

    public const CODIFICACIONES = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];

    /** Lee sólo la primera fila (encabezados o primeros valores) de un archivo. */
    public static function encabezados(string $ruta, string $delimitador, string $codificacion): array
    {
        $fh = fopen($ruta, 'r');
        $fila = fgetcsv($fh, 0, $delimitador === "\t" ? "\t" : $delimitador);
        fclose($fh);

        return array_map(fn ($v) => self::aUtf8((string) $v, $codificacion), $fila ?: []);
    }

    /**
     * Procesa el archivo completo y devuelve la Importacion creada.
     */
    public function procesar(
        string $ruta,
        string $nombreArchivo,
        array $mapeo,
        string $delimitador,
        string $codificacion,
        bool $tieneEncabezado,
        ?int $userId = null,
    ): Importacion {
        $importacion = Importacion::create([
            'archivo' => $nombreArchivo,
            'registros' => 0,
            'estado' => 'procesado',
            'user_id' => $userId,
        ]);

        $insertados = 0;
        $del = $delimitador === "\t" ? "\t" : $delimitador;

        DB::transaction(function () use ($ruta, $mapeo, $del, $codificacion, $tieneEncabezado, $importacion, &$insertados) {
            $fh = fopen($ruta, 'r');
            $primera = true;

            while (($fila = fgetcsv($fh, 0, $del)) !== false) {
                if ($primera && $tieneEncabezado) {
                    $primera = false;
                    continue;
                }
                $primera = false;

                if ($this->filaVacia($fila)) {
                    continue;
                }

                $get = fn ($campo) => isset($mapeo[$campo]) && $mapeo[$campo] !== null && isset($fila[$mapeo[$campo]])
                    ? self::aUtf8((string) $fila[$mapeo[$campo]], $codificacion)
                    : null;

                $idCuenta = trim((string) $get('id_cuenta'));
                if ($idCuenta === '') {
                    continue;
                }

                $cuenta = $this->cuenta($idCuenta, $get('nombre_cuenta'), $get('unidad'));
                $dispositivo = $this->dispositivo($get('host'), $get('ip'), $get('modelo'));

                Lectura::create([
                    'cuenta_id' => $cuenta->id,
                    'dispositivo_id' => $dispositivo?->id,
                    'impresiones' => $this->entero($get('impresiones')),
                    'copias' => $this->entero($get('copias')),
                    'escaneados' => $this->entero($get('escaneados')),
                    'fecha_corte' => $this->fecha($get('fecha_corte')),
                    'importacion_id' => $importacion->id,
                ]);
                $insertados++;
            }
            fclose($fh);
        });

        $importacion->update(['registros' => $insertados]);

        return $importacion->fresh();
    }

    private function cuenta(string $idCuenta, ?string $nombre, ?string $unidadTxt): Cuenta
    {
        $unidadId = $this->unidadId($unidadTxt) ?? $this->unidadId($nombre); // deriva del prefijo si hace falta

        return Cuenta::updateOrCreate(
            ['id_cuenta' => $idCuenta],
            array_filter([
                'nombre_cuenta' => $nombre ?: $idCuenta,
                'unidad_id' => $unidadId,
            ], fn ($v) => $v !== null),
        );
    }

    /** Busca una unidad por código exacto o por el prefijo del texto (ej. "SG_Nombre" -> SG_). */
    private function unidadId(?string $txt): ?int
    {
        if (! $txt) {
            return null;
        }
        $txt = trim($txt);

        $u = Unidad::where('codigo', $txt)->first()
            ?? Unidad::where('codigo', substr($txt, 0, 3))->first();

        return $u?->id;
    }

    private function dispositivo(?string $host, ?string $ip, ?string $modelo): ?Dispositivo
    {
        $host = $host ? trim($host) : null;
        if (! $host) {
            return null;
        }

        $modeloId = null;
        if ($modelo && trim($modelo) !== '') {
            $modeloId = Modelo::firstOrCreate(['nombre' => trim($modelo)])->id;
        }

        return Dispositivo::updateOrCreate(
            ['nombre_host' => $host],
            array_filter([
                'direccion_ip' => $ip ? trim($ip) : null,
                'modelo_id' => $modeloId,
            ], fn ($v) => $v !== null),
        );
    }

    private function entero(?string $v): int
    {
        return (int) preg_replace('/[^\d-]/', '', (string) $v);
    }

    private function fecha(?string $v): Carbon
    {
        $v = trim((string) $v);
        if ($v === '') {
            return Carbon::now();
        }

        // Formatos día/mes primero (formato del sistema viejo), con día/hora sin cero inicial.
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'j/n/Y G:i:s', 'j/n/Y G:i', 'j/n/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $v);
                if ($d !== null) {
                    return $d;
                }
            } catch (\Throwable) {
                // probar el siguiente formato
            }
        }

        try {
            return Carbon::parse($v);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }

    private function filaVacia(array $fila): bool
    {
        return count(array_filter($fila, fn ($v) => trim((string) $v) !== '')) === 0;
    }

    private static function aUtf8(string $valor, string $codificacion): string
    {
        if ($codificacion === 'UTF-8' || $valor === '') {
            return $valor;
        }

        return mb_convert_encoding($valor, 'UTF-8', $codificacion);
    }
}
