<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exportación genérica: recibe encabezados y filas ya calculados.
 * Reutilizada para usuarios, unidades, dispositivos y tendencias.
 */
class ConsumoExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private array $headings,
        private array $rows,
        private string $titulo,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->titulo;
    }
}
