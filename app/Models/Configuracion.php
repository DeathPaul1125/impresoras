<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['clave', 'valor'];

    public static function precioUnitario(): float
    {
        return (float) (static::find('precio_unitario')->valor ?? 0.17);
    }

    public static function setPrecioUnitario(float $valor): void
    {
        static::updateOrCreate(['clave' => 'precio_unitario'], ['valor' => $valor]);
    }

    /** @return string[] destinatarios de los reportes programados. */
    public static function correosReportes(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', (string) static::find('correos_reportes')?->valor))));
    }

    public static function setCorreosReportes(array $correos): void
    {
        static::updateOrCreate(['clave' => 'correos_reportes'], ['valor' => implode(', ', $correos)]);
    }
}
