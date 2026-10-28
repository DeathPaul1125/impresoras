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
    private const TIPOS = ['dispositivos', 'diario', 'tendencias'];

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
        return $request->only(['fecha_inicio', 'fecha_fin', 'dispositivo', 'modelo']);
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
            'dispositivos' => [
                'titulo' => 'Consumo por Impresora',
                'headings' => ['Ubicación', 'Nombre Host', 'Dirección IP', 'Modelo', 'Impresiones', 'Copias', 'Escaneos', 'Total Páginas', 'Costo Total', 'Porcentaje'],
                'rows' => $consumo->porDispositivo(
                    $request->get('sort_field', 'costo_total'),
                    $request->get('sort_order', 'DESC'),
                )->map(fn ($r) => [
                    $r->ubicacion, $r->nombre_host, $r->direccion_ip, $r->modelo,
                    $n($r->impresiones), $n($r->copias), $n($r->escaneos), $n($r->total),
                    $q($r->costo_total), $r->porcentaje.'%',
                ])->all(),
            ],
            'diario' => $this->diario($consumo, $q),
            'tendencias' => [
                'titulo' => 'Tendencias de Consumo Mensual',
                'headings' => ['Período', 'Días', 'Impresiones', 'Copias', 'Escaneos', 'Costo Total', 'Variación %'],
                'rows' => $consumo->tendenciaMensual()->map(fn ($r) => [
                    $r->periodo, $r->dias, $n($r->impresiones), $n($r->copias), $n($r->escaneos),
                    $q($r->costo_total),
                    $r->variacion === null ? '—' : $r->variacion.'%',
                ])->all(),
            ],
        };
    }

    /** Páginas por día × impresora, más total y costo del día. */
    private function diario(Consumo $consumo, \Closure $q): array
    {
        $columnas = $consumo->dispositivos();
        $matriz = $consumo->porDiaDispositivo();

        return [
            'titulo' => 'Consumo Diario (páginas)',
            'headings' => ['Fecha', ...$columnas->map(fn ($d) => $d->ubicacion ?? $d->nombre_host)->values(), 'Total', 'Costo'],
            'rows' => $consumo->porDia()->map(fn ($dia) => [
                Carbon::parse($dia->fecha)->format('d/m/Y'),
                ...$columnas->map(fn ($d) => (int) ($matriz[$dia->fecha][$d->id]->total ?? 0))->values(),
                $dia->total,
                $q($dia->costo_total),
            ])->all(),
        ];
    }
}
