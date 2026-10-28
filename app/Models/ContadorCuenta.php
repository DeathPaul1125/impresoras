<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContadorCuenta extends Model
{
    protected $table = 'contadores_cuenta';

    public $timestamps = false;

    protected $fillable = ['cuenta_id', 'dispositivo_id', 'impresiones', 'copias', 'escaneos', 'simplex', 'duplex', 'leido_en'];

    protected $casts = ['leido_en' => 'datetime'];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class);
    }
}
