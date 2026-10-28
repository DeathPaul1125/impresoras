<?php

namespace App\Mail;

use App\Exports\ConsumoExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelTipo;
use Maatwebsite\Excel\Facades\Excel;

class ReporteConsumo extends Mailable
{
    public function __construct(
        public string $titulo,
        public array $headings,
        public array $rows,
        public array $dias = [],   // [fecha, impresiones, copias, escaneos, total, costo] (semanal)
        public ?string $bolson = null,   // estado del bolsón del contrato
        public array $usuarios = [],     // top usuarios: [clave, usuario, unidad, impresiones, copias, escaneos, costo]
        public array $unidades = [],     // por unidad: [unidad, usuarios, impresiones, copias, escaneos, costo, %]
    ) {}

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
            Attachment::fromData(
                fn () => Excel::raw(new ConsumoExport($this->headings, $this->rows, 'Consumo por impresora'), ExcelTipo::XLSX),
                $archivo.'.xlsx',
            )->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            Attachment::fromData(
                fn () => Pdf::loadView('exports.pdf', [
                    'titulo' => $this->titulo,
                    'headings' => $this->headings,
                    'rows' => $this->rows,
                    'filtros' => 'Consumo por impresora (lecturas SNMP de las 6 pm)',
                ])->setPaper('a4', 'landscape')->output(),
                $archivo.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
