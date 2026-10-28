<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';

    protected $fillable = ['ubicacion', 'nivel', 'nombre_host', 'direccion_ip', 'modelo_id', 'serie'];

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class);
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class);
    }

    public function cortes(): HasMany
    {
        return $this->hasMany(Corte::class);
    }

    /** Histórico de lecturas SNMP diarias. */
    public function contadores(): HasMany
    {
        return $this->hasMany(ContadorEquipo::class);
    }
}
