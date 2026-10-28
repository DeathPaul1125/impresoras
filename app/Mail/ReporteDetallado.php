<?php

namespace App\Mail;

use App\Exports\ConsumoExport;
use App\Support\ReporteMensual;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel as ExcelTipo;
use Maatwebsite\Excel\Facades\Excel;

/** Reporte detallado de fin de mes: resumen en el cuerpo + Excel con todas las hojas + PDF (sin el registro de trabajos). */
class ReporteDetallado extends Mailable
{
    public array $totales;

    public array $impresoras;

    public string $mes;

    public function __construct(public ReporteMensual $reporte)
    {
        $this->mes = $reporte->etiqueta();
        $this->totales = $reporte->totales();
        $this->impresoras = $reporte->impresoras();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Impresoras SENABED · Reporte detallado de {$this->mes}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reporte-detallado');
    }

    public function attachments(): array
    {
        $archivo = 'reporte_detallado_'.$this->reporte->desde->format('Y_m');

        return [
            Attachment::fromData(fn () => Excel::raw(self::excel($this->reporte), ExcelTipo::XLSX), "{$archivo}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            Attachment::fromData(fn () => self::pdf($this->reporte)->output(), "{$archivo}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    public static function excel(ReporteMensual $r): WithMultipleSheets
    {
        return new class($r->hojas()) implements WithMultipleSheets
        {
            public function __construct(private array $hojas) {}

            public function sheets(): array
            {
                return array_map(fn ($h) => new ConsumoExport($h['headings'], $h['rows'], $h['titulo']), $this->hojas);
            }
        };
    }

    public static function pdf(ReporteMensual $r): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('exports.reporte-detallado', ['mes' => $r->etiqueta(), 'hojas' => $r->hojas(conTrabajos: false)])
            ->setPaper('a4', 'landscape');
    }
}
