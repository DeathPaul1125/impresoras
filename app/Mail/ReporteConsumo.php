<?php

namespace App\Mail;

use App\Exports\HojasExport;
use App\Support\ReportePeriodo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelTipo;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reporte diario, semanal o detallado de fin de mes: resumen en el cuerpo, Excel con todas las hojas
 * (incluido el registro de trabajos) y PDF con las mismas hojas salvo el registro de trabajos.
 */
class ReporteConsumo extends Mailable
{
    public string $titulo;

    /** Datos del cuerpo del correo (ReportePeriodo::correo()). */
    public array $datos;

    public function __construct(public ReportePeriodo $reporte)
    {
        $this->titulo = $reporte->titulo();
        $this->datos = $reporte->correo();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Impresoras SENABED · '.$this->titulo);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reporte-consumo');
    }

    public function attachments(): array
    {
        $archivo = Str::slug($this->titulo);

        return [
            Attachment::fromData(fn () => Excel::raw(self::excel($this->reporte), ExcelTipo::XLSX), "{$archivo}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            Attachment::fromData(fn () => self::pdf($this->reporte)->output(), "{$archivo}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    public static function excel(ReportePeriodo $r): HojasExport
    {
        return new HojasExport($r->hojas());
    }

    public static function pdf(ReportePeriodo $r): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('exports.reporte-periodo', ['titulo' => $r->titulo(), 'datos' => $r->correo(), 'hojas' => $r->hojas(conTrabajos: false)])
            ->setPaper('a4', 'landscape');
    }
}
