<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\Importacion;
use App\Models\Lectura;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Verificación de Importación')]
class Verificacion extends Component
{
    public ?int $importacionId = null;

    public function seleccionar(int $id): void
    {
        $this->importacionId = $this->importacionId === $id ? null : $id;
    }

    public function render()
    {
        $precio = Configuracion::precioUnitario();
        $costoSql = "SUM((lecturas.impresiones + lecturas.copias) * {$precio})";

        // Costo por importación (lote).
        $importaciones = Importacion::query()
            ->leftJoin('lecturas', 'lecturas.importacion_id', '=', 'importaciones.id')
            ->groupBy('importaciones.id', 'importaciones.archivo', 'importaciones.registros', 'importaciones.estado', 'importaciones.created_at')
            ->select('importaciones.id', 'importaciones.archivo', 'importaciones.registros', 'importaciones.estado', 'importaciones.created_at')
            ->selectRaw("COALESCE({$costoSql}, 0) AS costo_total")
            ->orderByDesc('importaciones.created_at')
            ->limit(50)
            ->get();

        // Desglose por depto del lote seleccionado.
        $desglose = collect();
        if ($this->importacionId) {
            $desglose = Lectura::query()
                ->where('lecturas.importacion_id', $this->importacionId)
                ->join('cuentas', 'cuentas.id', '=', 'lecturas.cuenta_id')
                ->leftJoin('unidades', 'unidades.id', '=', 'cuentas.unidad_id')
                ->groupBy('unidades.codigo', 'unidades.nombre')
                ->select('unidades.codigo AS depto', 'unidades.nombre AS depto_nombre')
                ->selectRaw('COUNT(*) AS registros')
                ->selectRaw("{$costoSql} AS costo_total")
                ->orderByDesc('costo_total')
                ->get();
        }

        return view('livewire.verificacion', [
            'importaciones' => $importaciones,
            'desglose' => $desglose,
        ]);
    }
}
