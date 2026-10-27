<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Totales de impresoras por SNMP, todos los días a las 6 pm (hora de Guatemala; la app está en UTC).
Schedule::command('impresoras:leer-snmp')->dailyAt('18:00')->timezone('America/Guatemala');

// Reportes por correo, 5 min después de la lectura.
Schedule::command('impresoras:enviar-reporte diario')->dailyAt('18:05')->timezone('America/Guatemala');
Schedule::command('impresoras:enviar-reporte semanal')->fridays()->at('18:05')->timezone('America/Guatemala');
// No se usa lastDayOfMonth(): calcula el día con la hora UTC y a las 18:05 de Guatemala ya es el día siguiente en UTC.
Schedule::command('impresoras:enviar-reporte mensual')->dailyAt('18:05')->timezone('America/Guatemala')
    ->when(fn () => now('America/Guatemala')->isLastOfMonth());
