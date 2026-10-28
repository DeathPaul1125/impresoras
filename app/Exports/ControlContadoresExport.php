<?php

namespace App\Exports;

use App\Support\Contrato;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * "Control Contadores mensuales" con la misma estructura del proveedor:
 * hoja resumen (contador por corte + producido por mes + saldo) y una hoja por corte.
 */
class ControlContadoresExport implements WithMultipleSheets
{
    private Contrato $c;

    private array $r;

    public function __construct()
    {
        $this->c = new Contrato;
        $this->r = $this->c->resumen();
    }

    public function sheets(): array
    {
        $hojas = [$this->hoja('Control Copias e Impresiones', $this->resumen(), 6)];
        foreach ($this->r['periodos']->reverse() as $p) {
            $hojas[] = $this->hoja(mb_substr($p->etiqueta, 0, 31), $this->corte($p), 6);
        }

        return $hojas;
    }

    private function resumen(): array
    {
        $r = $this->r;
        $c = $this->c;
        $p = $r['periodos'];
        $inicial = $c->fechas->first();

        $filas = [
            ['CONTADORES COPIAS E IMPRESIONES · CONTRATO '.$r['contrato']],
            ['Consumo acumulado al '.$r['corte_al']?->format('d/m/Y H:i').' Hrs. · generado '.now()->format('d/m/Y H:i')],
            ['# de Contrato:', $r['contrato'], 'Impresión mensual aprox. (12 meses):', $r['mensual_aprox'], 'Consumo promedio mensual:', $r['promedio_mensual'], 'Se agota (proyección):', $r['agotamiento']?->format('d/m/Y') ?? '—'],
            ['Bolsón B/N:', $r['bolson'], 'Consumo acumulado:', $r['acumulado'], 'Bolsón disponible:', $r['disponible'], '% consumo del bolsón:', $r['porcentaje'] / 100],
            [],
        ];

        $enc = ['Modelo', 'Serie', 'Ubicación', 'Nivel', 'Contador inicial '.$inicial?->format('d/m/Y')];
        foreach ($p as $per) {
            array_push($enc, 'Contador al '.$per->fecha->format('d/m/Y'), 'Producido '.$per->etiqueta);
        }
        array_push($enc, 'Producido en curso', 'Acumulado', '% del total');
        $filas[] = $enc;

        $porId = $r['por_equipo']->keyBy('id');
        foreach ($c->dispositivos as $id => $d) {
            $fila = [$d->modelo?->nombre, $porId[$id]->serie_contrato ?? $d->serie, $d->ubicacion, $d->nivel, $inicial ? $c->contadorEnCorte($inicial, $id)?->contador : null];
            foreach ($p as $per) {
                array_push($fila, $c->contadorEnCorte($per->fecha, $id)?->contador, $per->producido[$id] ?? 's/d');
            }
            array_push($fila, $r['en_curso']->producido[$id] ?? 's/d', $porId[$id]->acumulado, $porId[$id]->porcentaje / 100);
            $filas[] = $fila;
        }

        $tot = ['Totales', '', '', '', ''];
        $saldo = ['Saldo del bolsón', '', '', '', $r['bolson']];
        foreach ($p as $per) {
            array_push($tot, '', $per->total);
            array_push($saldo, '', $per->saldo);
        }
        array_push($tot, $r['en_curso']->total, $r['acumulado'], 1);
        array_push($saldo, '', $r['disponible'], '');
        $filas[] = $tot;
        $filas[] = $saldo;

        if ($r['alertas']) {
            $filas[] = [];
            foreach ($r['alertas'] as $a) {
                $filas[] = ['⚠ '.$a];
            }
        }

        return $filas;
    }

    private function corte(object $p): array
    {
        $r = $this->r;
        $c = $this->c;
        $saldoAnterior = $p->saldo + $p->total;

        $filas = [
            ['CONTADORES FOTOCOPIADORAS · CONTRATO '.$r['contrato']],
            ['CONTADORES al '.$p->fecha->format('d/m/Y H:i').' Hrs. ('.$p->etiqueta.')'],
            ['# de Contrato:', $r['contrato'], 'Bolsón contratado:', $r['bolson'], 'Bolsón al corte anterior:', $saldoAnterior],
            ['Producido en el mes:', $p->total, 'Acumulado:', $r['bolson'] - $p->saldo, 'Bolsón disponible:', $p->saldo],
            [],
            ['Modelo', 'Serie', 'Ubicación', 'Nivel', 'Contador inicial '.$p->desde->format('d/m/Y'), 'Contador actual '.$p->fecha->format('d/m/Y'), 'Producido'],
        ];
        $ini = $fin = 0;
        foreach ($c->dispositivos as $id => $d) {
            $a = $c->contadorEnCorte($p->desde, $id);
            $b = $c->contadorEnCorte($p->fecha, $id);
            $ini += $a?->contador ?? 0;
            $fin += $b?->contador ?? 0;
            $filas[] = [$d->modelo?->nombre, $b?->serie ?? $d->serie, $d->ubicacion, $d->nivel, $a?->contador, $b?->contador, $p->producido[$id] ?? 's/d'];
        }
        $filas[] = ['Totales', '', '', '', $ini, $fin, $p->total];

        return $filas;
    }

    /** Hoja con formato: título, bloque de datos del contrato, encabezado en fila $filaEnc y totales al final. */
    private function hoja(string $titulo, array $filas, int $filaEnc): object
    {
        return new class($titulo, $filas, $filaEnc) implements FromArray, WithEvents, WithTitle
        {
            public function __construct(private string $titulo, private array $filas, private int $filaEnc) {}

            public function array(): array
            {
                return $this->filas;
            }

            public function title(): string
            {
                return $this->titulo;
            }

            public function registerEvents(): array
            {
                return [AfterSheet::class => function (AfterSheet $e) {
                    /** @var Worksheet $s */
                    $s = $e->sheet->getDelegate();
                    $ultCol = Coordinate::stringFromColumnIndex(max(array_map('count', $this->filas)));
                    $navy = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D2149']];

                    $s->mergeCells("A1:{$ultCol}1");
                    $s->mergeCells("A2:{$ultCol}2");
                    $s->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $navy]);
                    $s->getStyle('A2')->getFont()->setItalic(true);
                    $s->getStyle('A3:H4')->getFont()->setBold(true);
                    foreach (['B', 'D', 'F', 'H'] as $col) {
                        $s->getStyle("{$col}3:{$col}4")->getFont()->setBold(false)->getColor()->setRGB('1D4ED8');
                    }
                    if (is_float($this->filas[3][7] ?? null)) {
                        $s->getStyle('H4')->getNumberFormat()->setFormatCode('0.0%');
                    }

                    $enc = $this->filaEnc;
                    $s->getStyle("A{$enc}:{$ultCol}{$enc}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => $navy,
                        'alignment' => ['wrapText' => true, 'vertical' => 'center', 'horizontal' => 'center'],
                    ]);
                    $s->getRowDimension($enc)->setRowHeight(32);

                    // Fin de la tabla = última fila que empieza con "Totales" o "Saldo".
                    $fin = $enc;
                    foreach ($this->filas as $i => $f) {
                        if (in_array($f[0] ?? null, ['Totales', 'Saldo del bolsón'], true)) {
                            $fin = $i + 1;
                            $s->getStyle("A{$fin}:{$ultCol}{$fin}")->applyFromArray(['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']]]);
                        }
                    }
                    $s->getStyle("A{$enc}:{$ultCol}{$fin}")->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setRGB('CBD5E1');
                    $s->getStyle("E{$enc}:{$ultCol}{$fin}")->getNumberFormat()->setFormatCode('#,##0');
                    $s->getStyle('B3:F4')->getNumberFormat()->setFormatCode('#,##0');

                    // Columnas de "Producido" resaltadas y % con formato.
                    foreach ($this->filas[$enc - 1] as $i => $h) {
                        $col = Coordinate::stringFromColumnIndex($i + 1);
                        if (str_starts_with((string) $h, 'Producido')) {
                            $s->getStyle("{$col}".($enc + 1).":{$col}{$fin}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                        }
                        if ($h === '% del total') {
                            $s->getStyle("{$col}".($enc + 1).":{$col}{$fin}")->getNumberFormat()->setFormatCode('0.0%');
                        }
                        $s->getColumnDimension($col)->setWidth($i < 4 ? 14 : 13);
                    }
                    $s->getColumnDimension('A')->setWidth(18);
                    $s->freezePane('E'.($enc + 1));
                }];
            }
        };
    }
}
