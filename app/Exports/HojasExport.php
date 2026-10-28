<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Libro de Excel a partir de hojas ['titulo', 'headings', 'rows'] ya calculadas. */
class HojasExport implements WithMultipleSheets
{
    public function __construct(private array $hojas) {}

    public function sheets(): array
    {
        return array_map(fn ($h) => new ConsumoExport($h['headings'], $h['rows'], $h['titulo']), $this->hojas);
    }
}
