<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Actions\Logout;
use App\Livewire\CargarContadores;
use App\Livewire\ConfiguracionPrecio;
use App\Livewire\DashboardGeneral;
use App\Livewire\Impresoras;
use App\Livewire\Tablero;
use App\Livewire\Verificacion;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardGeneral::class)->name('dashboard');
    Route::get('tablero', Tablero::class)->name('tablero');
    Route::get('verificacion', Verificacion::class)->name('verificacion');

    // Exportaciones (respetan los filtros vigentes vía query string)
    Route::get('export/{tipo}', [ExportController::class, 'excel'])->name('export.excel');
    Route::get('export/pdf/{tipo}', [ExportController::class, 'pdf'])->name('export.pdf');

    // Solo administradores
    Route::middleware('can:admin')->group(function () {
        Route::get('carga', CargarContadores::class)->name('carga');
        Route::get('impresoras', Impresoras::class)->name('impresoras');
        Route::get('configuracion', ConfiguracionPrecio::class)->name('configuracion');
    });

    Route::view('profile', 'profile')->name('profile');

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/auth.php';
