<?php

use App\Models\Configuracion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Horas de Guatemala (app.timezone). Lectura SNMP de totales a las 6 pm; los reportes 5 min después.
Schedule::command('impresoras:leer-snmp')->dailyAt('18:00');
Schedule::command('impresoras:enviar-reporte diario')->dailyAt('18:05');
Schedule::command('impresoras:enviar-reporte semanal')->fridays()->at('18:05');

// Corte mensual del contrato el día configurado (25) a las 00:00, y su reporte 10 min después.
$esDiaDeCorte = fn () => today()->day === (int) Configuracion::contrato()['dia_corte'];
Schedule::command('impresoras:corte')->dailyAt('00:00')->when($esDiaDeCorte);
Schedule::command('impresoras:enviar-reporte mensual')->dailyAt('00:10')->when($esDiaDeCorte);
