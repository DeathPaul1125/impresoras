<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

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

    /** Contrato con el proveedor: número, bolsón B/N (páginas), vigencia y día del corte mensual. */
    public static function contrato(): array
    {
        return (json_decode((string) static::find('contrato')?->valor, true) ?: [])
            + ['numero' => '', 'bolson' => 0, 'inicio' => null, 'fin' => null, 'dia_corte' => 25];
    }

    public static function setContrato(array $contrato): void
    {
        static::updateOrCreate(['clave' => 'contrato'], ['valor' => json_encode($contrato)]);
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

    /** Cuenta que envía los correos (SMTP). La contraseña se guarda cifrada con APP_KEY. */
    public static function smtp(): array
    {
        $smtp = json_decode((string) static::find('smtp')?->valor, true) ?: [];
        $smtp['password'] = isset($smtp['password']) ? Crypt::decryptString($smtp['password']) : null;

        return $smtp + ['host' => null, 'port' => 587, 'ssl' => false, 'username' => null, 'from_address' => null, 'from_name' => 'Impresoras SENABED'];
    }

    public static function setSmtp(array $smtp): void
    {
        $smtp['password'] = filled($smtp['password'] ?? null) ? Crypt::encryptString($smtp['password']) : null;
        static::updateOrCreate(['clave' => 'smtp'], ['valor' => json_encode($smtp)]);
    }

    /**
     * Usa la cuenta SMTP guardada para los envíos de este proceso. Si no hay servidor configurado
     * se queda con lo del .env (MAIL_*). Devuelve true si aplicó la configuración guardada.
     */
    public static function aplicarSmtp(): bool
    {
        $s = static::smtp();
        if (! $s['host']) {
            return false;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $s['ssl'] ? 'smtps' : 'smtp',   // smtps = SSL (465); smtp usa STARTTLS si el servidor lo ofrece
            'mail.mailers.smtp.host' => $s['host'],
            'mail.mailers.smtp.port' => (int) $s['port'],
            'mail.mailers.smtp.username' => $s['username'],
            'mail.mailers.smtp.password' => $s['password'],
            'mail.mailers.smtp.url' => null,
            'mail.from.address' => $s['from_address'] ?: $s['username'],
            'mail.from.name' => $s['from_name'],
        ]);
        Mail::purge('smtp');   // descarta el mailer ya creado con la configuración anterior

        return true;
    }
}
