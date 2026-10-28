<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumoMensual extends Model
{
    protected $table = 'consumo_mensual';

    protected $fillable = ['periodo', 'dispositivo_id', 'cuenta_id', 'impresiones', 'copias', 'escaneos'];

    protected $casts = ['periodo' => 'date'];

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }
}
