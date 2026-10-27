<?php

namespace App\Livewire;

use App\Models\Importacion;
use App\Support\ImportadorCsv;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Carga de Contadores')]
class CargarContadores extends Component
{
    use WithFileUploads;

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public $files = [];

    public string $delimitador = ';';

    public string $codificacion = 'UTF-8';

    public bool $tieneEncabezado = true;

    public array $headers = [];

    /** field => índice de columna (o '' si no mapeado) */
    public array $mapeo = [];

    /** Campos destino (clave => etiqueta, requerido). */
    public array $campos = [
        'id_cuenta' => 'ID Cuenta *',
        'nombre_cuenta' => 'Nombre Cuenta *',
        'unidad' => 'Unidad',
        'impresiones' => 'Impresiones *',
        'copias' => 'Copias *',
        'escaneados' => 'Escaneados',
        'fecha_corte' => 'Último Corte / Fecha',
        'host' => 'Nombre Host',
        'ip' => 'Dirección IP',
        'modelo' => 'Modelo',
    ];

    protected function rules(): array
    {
        return [
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:csv,txt|max:20480',
        ];
    }

    public function leerEncabezados(): void
    {
        $this->validate();

        $primero = $this->files[0];
        $this->headers = ImportadorCsv::encabezados(
            $primero->getRealPath(),
            $this->delimitador,
            $this->codificacion,
        );

        $this->autoMapear();
    }

    /** Adivina el mapeo comparando los encabezados con palabras clave. */
    private function autoMapear(): void
    {
        $claves = [
            'id_cuenta' => ['id cuenta', 'idcuenta', 'id_cuenta', 'cuenta id', 'account'],
            'nombre_cuenta' => ['nombre', 'name', 'usuario', 'cuenta'],
            'unidad' => ['unidad', 'depto', 'departamento'],
            'impresiones' => ['impres', 'print', 'impresion'],
            'copias' => ['copia', 'copy'],
            'escaneados' => ['escane', 'scan'],
            'fecha_corte' => ['fecha', 'corte', 'date'],
            'host' => ['host', 'equipo', 'device'],
            'ip' => ['ip', 'direccion ip'],
            'modelo' => ['modelo', 'model'],
        ];

        foreach ($this->campos as $campo => $_) {
            $this->mapeo[$campo] = '';
            foreach ($this->headers as $i => $h) {
                $hl = mb_strtolower(trim($h));
                foreach ($claves[$campo] as $kw) {
                    if (str_contains($hl, $kw)) {
                        $this->mapeo[$campo] = (string) $i;
                        continue 3;
                    }
                }
            }
        }
    }

    public function procesar(): void
    {
        $this->validate();
        $this->validate([
            'mapeo.id_cuenta' => 'required',
            'mapeo.nombre_cuenta' => 'required',
            'mapeo.impresiones' => 'required',
            'mapeo.copias' => 'required',
        ], [], ['mapeo.id_cuenta' => 'ID Cuenta', 'mapeo.nombre_cuenta' => 'Nombre Cuenta', 'mapeo.impresiones' => 'Impresiones', 'mapeo.copias' => 'Copias']);

        $mapeo = array_map(
            fn ($v) => $v === '' ? null : (int) $v,
            $this->mapeo,
        );

        $importador = new ImportadorCsv;
        $total = 0;
        $archivos = 0;

        foreach ($this->files as $file) {
            $imp = $importador->procesar(
                $file->getRealPath(),
                $file->getClientOriginalName(),
                $mapeo,
                $this->delimitador,
                $this->codificacion,
                $this->tieneEncabezado,
                auth()->id(),
            );
            $total += $imp->registros;
            $archivos++;
        }

        $this->reset(['files', 'headers', 'mapeo']);
        session()->flash('ok', "Se procesaron {$archivos} archivo(s) con {$total} registro(s).");
    }

    public function render()
    {
        return view('livewire.cargar-contadores', [
            'delimitadores' => ImportadorCsv::DELIMITADORES,
            'codificaciones' => ImportadorCsv::CODIFICACIONES,
            'historial' => Importacion::with('usuario')->latest()->limit(20)->get(),
        ]);
    }
}
