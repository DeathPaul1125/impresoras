<?php

namespace App\Mail;

use App\Exports\ControlContadoresExport;
use App\Http\Controllers\ExportController;
use App\Support\Contrato;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Maatwebsite\Excel\Excel as ExcelTipo;
use Maatwebsite\Excel\Facades\Excel;

/** Reporte del corte mensual del contrato: resumen en el cuerpo + Control de contadores (Excel) + resumen (PDF). */
class ReporteCorte extends Mailable
{
    public array $r;

    public array $hallazgos;

    /** Consumo por unidad del último período (de corte a corte). */
    public array $unidades = [];

    public function __construct()
    {
        $c = new Contrato;
        $this->r = $c->resumen();
        $this->hallazgos = $c->hallazgos($this->r);
        if ($p = $this->r['periodos']->last()) {
            $this->unidades = (new \App\Support\ConsumoCuentas(['desde' => $p->desde, 'hasta' => $p->fecha]))->porUnidad()->all();
        }
    }

    public function envelope(): Envelope
    {
        $p = $this->r['periodos']->last();

        return new Envelope(subject: 'Impresoras SENABED · Corte '.($p?->etiqueta ?? '').' · Contrato '.$this->r['contrato']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reporte-corte');
    }

    public function attachments(): array
    {
        $fecha = $this->r['periodos']->last()?->fecha->format('Ymd') ?? now()->format('Ymd');

        return [
            Attachment::fromData(fn () => Excel::raw(new ControlContadoresExport, ExcelTipo::XLSX), "control_contadores_{$fecha}.xlsx")
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            Attachment::fromData(fn () => ExportController::pdfResumen()->output(), "resumen_contrato_{$fecha}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
