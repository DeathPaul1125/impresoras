<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Corte extends Model
{
    protected $table = 'cortes';

    protected $fillable = ['fecha', 'dispositivo_id', 'serie', 'contador', 'origen'];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class);
    }
}
