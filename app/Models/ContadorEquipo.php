<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContadorEquipo extends Model
{
    protected $table = 'contadores_equipo';

    public $timestamps = false;

    protected $fillable = ['dispositivo_id', 'total', 'impresiones', 'copias', 'escaneos', 'leido_en', 'detalle'];

    protected $casts = [
        'leido_en' => 'datetime',
        'detalle' => 'array',
    ];

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class);
    }
}
