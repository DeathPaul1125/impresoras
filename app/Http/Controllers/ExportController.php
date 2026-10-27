<?php

namespace App\Http\Controllers;

use App\Exports\ConsumoExport;
use App\Support\Consumo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    private const TIPOS = ['usuarios', 'unidades', 'dispositivos', 'tendencias'];

    public function excel(Request $request, string $tipo): Response
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);

        $d = $this->datos($tipo, $request);

        return Excel::download(
            new ConsumoExport($d['headings'], $d['rows'], $d['titulo']),
            $tipo.'_'.now()->format('Ymd_His').'.xlsx',
        );
    }

    public function pdf(Request $request, string $tipo): Response
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);

        $d = $this->datos($tipo, $request);

        return Pdf::loadView('exports.pdf', [
            'titulo' => $d['titulo'],
            'headings' => $d['headings'],
            'rows' => $d['rows'],
            'filtros' => $this->filtrosLegibles($request),
        ])->setPaper('a4', 'landscape')->download($tipo.'_'.now()->format('Ymd_His').'.pdf');
    }

    private function filtros(Request $request): array
    {
        return $request->only([
            'fecha_inicio', 'fecha_fin', 'unidad', 'dispositivo', 'modelo', 'nombre_cuenta',
        ]);
    }

    private function filtrosLegibles(Request $request): string
    {
        $f = $this->filtros($request);

        return 'Del '.($f['fecha_inicio'] ?? '—').' al '.($f['fecha_fin'] ?? '—');
    }

    /** Devuelve headings + filas (arrays planos) según el tipo, reutilizando Consumo. */
    private function datos(string $tipo, Request $request): array
    {
        $consumo = new Consumo($this->filtros($request));
        $q = fn ($v) => 'Q'.number_format((float) $v, 2);
        $n = fn ($v) => (int) $v;

        return match ($tipo) {
            'usuarios' => [
                'titulo' => 'Usuarios por Consumo',
                'headings' => ['ID Cuenta', 'Nombre Cuenta', 'Unidad', 'Impresiones', 'Copias', 'Escaneados', 'Costo Total', 'Último Corte'],
                'rows' => $consumo->porUsuario(
                    $request->get('sort_field', 'costo_total'),
                    $request->get('sort_order', 'DESC'),
                )->map(fn ($r) => [
                    $r->id_cuenta, $r->nombre_cuenta, $r->unidad,
                    $n($r->impresiones), $n($r->copias), $n($r->escaneados),
                    $q($r->costo_total),
                    $r->ultimo_corte ? Carbon::parse($r->ultimo_corte)->format('d/m/Y H:i') : '',
                ])->all(),
            ],
            'unidades' => [
                'titulo' => 'Unidades por Consumo',
                'headings' => ['Unidad', 'Nombre Unidad', 'Impresiones', 'Copias', 'Escaneados', 'Costo Total', 'Porcentaje'],
                'rows' => $consumo->porUnidad()->map(fn ($r) => [
                    $r->unidad, $r->unidad_nombre,
                    $n($r->impresiones), $n($r->copias), $n($r->escaneados),
                    $q($r->costo_total), $r->porcentaje.'%',
                ])->all(),
            ],
            'dispositivos' => [
                'titulo' => 'Dispositivos por Consumo',
                'headings' => ['Nombre Host', 'Dirección IP', 'Modelo', 'Impresiones', 'Copias', 'Escaneados', 'Costo Total'],
                'rows' => $consumo->porDispositivo()->map(fn ($r) => [
                    $r->nombre_host, $r->direccion_ip, $r->modelo,
                    $n($r->impresiones), $n($r->copias), $n($r->escaneados),
                    $q($r->costo_total),
                ])->all(),
            ],
            'tendencias' => [
                'titulo' => 'Tendencias de Consumo Mensual',
                'headings' => ['Período', 'Usuarios Activos', 'Promedio por Usuario', 'Costo Total', 'Variación %'],
                'rows' => $consumo->tendenciaMensual()->map(fn ($r) => [
                    $r->periodo, $n($r->usuarios), $n($r->promedio_usuario),
                    $q($r->costo_total),
                    $r->variacion === null ? '—' : $r->variacion.'%',
                ])->all(),
            ],
        };
    }
}
