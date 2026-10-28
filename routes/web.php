<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Actions\Logout;
use App\Livewire\ConfiguracionPrecio;
use App\Livewire\Cortes;
use App\Livewire\DashboardGeneral;
use App\Livewire\Impresoras;
use App\Livewire\Tablero;
use Illuminate\Support\Facades\Route;

// Sin portada: la raíz lleva al dashboard (y el middleware auth manda al login a quien no haya entrado).
Route::redirect('/', '/dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardGeneral::class)->name('dashboard');
    Route::get('tablero', Tablero::class)->name('tablero');

    // Exportaciones (respetan los filtros vigentes vía query string)
    Route::get('export/{tipo}', [ExportController::class, 'excel'])->name('export.excel');
    Route::get('export/pdf/{tipo}', [ExportController::class, 'pdf'])->name('export.pdf');

    // Solo administradores
    Route::middleware('can:admin')->group(function () {
        Route::get('impresoras', Impresoras::class)->name('impresoras');
        Route::get('cortes', Cortes::class)->name('cortes');
        Route::get('configuracion', ConfiguracionPrecio::class)->name('configuracion');
    });

    Route::view('profile', 'profile')->name('profile');

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/auth.php';
