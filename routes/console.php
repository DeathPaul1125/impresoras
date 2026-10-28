<?php

use App\Models\Configuracion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Latido: deja la hora de la última corrida del programador (lo muestra impresoras:diagnostico).
Schedule::call(fn () => Cache::forever('programador:ultimo', now()->toDateTimeString()))->everyMinute()->name('latido');

// Registro de trabajos cada minuto (la impresora solo guarda los últimos 16) y estado/tóner cada 15 minutos.
Schedule::command('impresoras:leer-trabajos')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('impresoras:leer-estado')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();

// Horas de Guatemala (app.timezone). Lectura de contadores a las 6 pm (SNMP o panel web), cuentas a las 6:02 (tarda ~6 min); reportes a las 6:15.
Schedule::command('impresoras:leer-snmp')->dailyAt('18:00');
Schedule::command('impresoras:leer-cuentas')->dailyAt('18:02')->runInBackground();   // ~6 min: el panel de cada impresora es lento
Schedule::command('impresoras:enviar-reporte diario')->dailyAt('18:15');
Schedule::command('impresoras:enviar-reporte semanal')->fridays()->at('18:15');
// Reporte detallado del mes: solo el último día del mes, después de la lectura y de las cuentas.
Schedule::command('impresoras:enviar-reporte detallado')->lastDayOfMonth('18:30');

// Corte mensual del contrato el día configurado (25) a las 00:00, y su reporte 10 min después.
$esDiaDeCorte = fn () => today()->day === (int) Configuracion::contrato()['dia_corte'];
Schedule::command('impresoras:corte')->dailyAt('00:00')->when($esDiaDeCorte);
Schedule::command('impresoras:enviar-reporte mensual')->dailyAt('00:10')->when($esDiaDeCorte);
