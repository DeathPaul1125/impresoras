<?php

namespace App\Livewire;

use App\Support\Contrato;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Dashboard del contrato: consumo acumulado contra el bolsón B/N, por corte y por impresora.
 * Interactivo: clic en una impresora o en un mes filtra/resalta el resto (estado en la URL).
 */
#[Layout('layouts.app')]
#[Title('Dashboard')]
class DashboardGeneral extends Component
{
    public const COLORES = ['#1d4ed8', '#f97316', '#15803d', '#7e22ce', '#f59e0b', '#0891b2', '#1e3a8a', '#db2777', '#65a30d'];

    /** Impresora seleccionada (id) o null. */
    #[Url(as: 'impresora')]
    public ?int $equipo = null;

    /** Corte seleccionado (Y-m-d) o null = último. */
    #[Url(as: 'corte')]
    public ?string $periodo = null;

    public function seleccionarEquipo(?int $id = null): void
    {
        $this->equipo = $this->equipo === $id ? null : $id;
    }

    public function seleccionarPeriodo(?string $fecha = null): void
    {
        $this->periodo = $this->periodo === $fecha ? null : $fecha;
    }

    public function limpiar(): void
    {
        $this->reset('equipo', 'periodo');
    }

    public function render()
    {
        $contrato = new Contrato;
        $r = $contrato->resumen();
        $periodos = $r['periodos'];

        // Color por posición en el ranking de consumo (como el dashboard de referencia: 1.º azul, 2.º naranja, 3.º verde...).
        $colorDe = $r['por_equipo']->sortByDesc('acumulado')->values()->mapWithKeys(fn ($e, $i) => [$e->id => self::COLORES[$i % count(self::COLORES)]])->all();

        $equipo = $this->equipo ? $r['por_equipo']->firstWhere('id', $this->equipo) : null;
        if ($this->equipo && ! $equipo) {
            $this->equipo = null;
        }
        $sel = ($this->periodo ? $periodos->first(fn ($p) => $p->fecha->toDateString() === $this->periodo) : null) ?? $periodos->last();
        $esUltimo = $sel === $periodos->last();

        return view('livewire.dashboard-general', [
            'r' => $r,
            'hallazgos' => $contrato->hallazgos($r),
            'colorDe' => $colorDe,
            'sel' => $sel,
            'esUltimo' => $esUltimo,
            'ficha' => $equipo ? $this->ficha($equipo, $periodos) : null,
        ]);
    }

    /** Datos de la ficha de una impresora: historial por corte y estadísticas. */
    private function ficha(object $e, $periodos): array
    {
        $serie = $periodos->map(fn ($p) => ['fecha' => $p->fecha, 'etiqueta' => $p->etiqueta, 'valor' => $p->producido[$e->id] ?? null]);
        $conDato = $serie->whereNotNull('valor');

        return [
            'e' => $e,
            'serie' => $serie,
            'promedio' => $conDato->count() ? (int) round($conDato->avg('valor')) : null,
            'mejor' => $conDato->sortByDesc('valor')->first(),
            'menor' => $conDato->sortBy('valor')->first(),
            'max' => max(1, (int) $conDato->max('valor')),
            'dispositivo' => \App\Models\Dispositivo::find($e->id),
        ];
    }
}
