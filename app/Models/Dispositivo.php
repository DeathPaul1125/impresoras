<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';

    protected $fillable = ['ubicacion', 'nivel', 'nombre_host', 'direccion_ip', 'modelo_id', 'serie', 'estado', 'estado_en'];

    protected $casts = ['estado' => 'array', 'estado_en' => 'datetime'];

    /** Nombres de los tamaños de papel que reporta la Kyocera. */
    public const PAPEL = [
        'Letter' => 'Carta', 'Legal' => 'Oficio (legal)', 'Folio' => 'Folio', 'Statement' => 'Media carta',
        'A4' => 'A4', 'A5' => 'A5', 'B5' => 'B5', 'Other1' => 'Otro 1', 'Other2' => 'Otro 2', 'Banner1' => 'Banner',
    ];

    public static function nombrePapel(string $tamano): string
    {
        return self::PAPEL[$tamano] ?? $tamano;
    }

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
