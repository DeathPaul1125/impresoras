<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unidad extends Model
{
    protected $table = 'unidades';

    protected $fillable = ['codigo', 'nombre'];

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }
}
