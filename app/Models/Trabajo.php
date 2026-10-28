<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trabajo extends Model
{
    protected $table = 'trabajos';

    protected $fillable = ['dispositivo_id', 'origen', 'trabajo', 'tipo', 'documento', 'cuenta', 'cuenta_id', 'usuario',
        'paginas', 'originales', 'juegos', 'estado', 'inicio', 'fin'];

    protected $casts = ['inicio' => 'datetime', 'fin' => 'datetime'];

    public const TIPOS = ['impresion' => 'Impresión', 'copia' => 'Copia', 'escaneo' => 'Escaneo', 'envio' => 'Envío', 'otro' => 'Otro'];

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class);
    }

    public function cuentaRegistrada(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    /** Filtros de la página y del Excel: desde, hasta (Y-m-d), dispositivo, tipo, buscar (cuenta/usuario/documento). */
    public function scopeFiltrar(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->where('inicio', '>=', $v.' 00:00:00'))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->where('inicio', '<=', $v.' 23:59:59'))
            ->when($f['dispositivo'] ?? null, fn ($q, $v) => $q->where('dispositivo_id', $v))
            ->when($f['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($f['buscar'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('cuenta', 'like', "%{$v}%")->orWhere('usuario', 'like', "%{$v}%")->orWhere('documento', 'like', "%{$v}%")));
    }

    /** Quién lo hizo: la cuenta del panel o el usuario de Windows. */
    public function getQuienAttribute(): string
    {
        return $this->cuenta ?: ($this->usuario ?: '—');
    }
}
