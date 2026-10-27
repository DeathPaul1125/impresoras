<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modelo extends Model
{
    protected $table = 'modelos';

    protected $fillable = ['nombre'];

    public function dispositivos(): HasMany
    {
        return $this->hasMany(Dispositivo::class);
    }
}
