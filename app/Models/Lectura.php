<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lectura extends Model
{
    protected $table = 'lecturas';

    protected $fillable = [
        'cuenta_id', 'dispositivo_id', 'impresiones', 'copias',
        'escaneados', 'fecha_corte', 'importacion_id',
    ];

    protected $casts = [
        'fecha_corte' => 'datetime',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class);
    }

    /**
     * Filtros compartidos por los dashboards y las exportaciones.
     * $f = ['fecha_inicio','fecha_fin','unidad','dispositivo','modelo','nombre_cuenta']
     */
    public function scopeFiltrar(Builder $q, array $f): Builder
    {
        if (! empty($f['fecha_inicio'])) {
            $q->whereDate('fecha_corte', '>=', $f['fecha_inicio']);
        }
        if (! empty($f['fecha_fin'])) {
            $q->whereDate('fecha_corte', '<=', $f['fecha_fin']);
        }
        if (! empty($f['unidad'])) {
            $q->whereHas('cuenta.unidad', fn ($u) => $u->where('codigo', $f['unidad']));
        }
        if (! empty($f['dispositivo'])) {
            $q->where('dispositivo_id', $f['dispositivo']);
        }
        if (! empty($f['modelo'])) {
            $q->whereHas('dispositivo', fn ($d) => $d->where('modelo_id', $f['modelo']));
        }
        if (! empty($f['nombre_cuenta'])) {
            $q->whereHas('cuenta', fn ($c) => $c->where('nombre_cuenta', 'like', '%'.$f['nombre_cuenta'].'%'));
        }

        return $q;
    }
}
