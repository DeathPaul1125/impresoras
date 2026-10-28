<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\VistaCorreoController;
use App\Livewire\Actions\Logout;
use App\Livewire\ConfiguracionPrecio;
use App\Livewire\Cortes;
use App\Livewire\DashboardGeneral;
use App\Livewire\Historico;
use App\Livewire\Impresoras;
use App\Livewire\Tablero;
use App\Livewire\Trabajos;
use Illuminate\Support\Facades\Route;

// Sin portada: la raíz lleva al dashboard (y el middleware auth manda al login a quien no haya entrado).
Route::redirect('/', '/dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardGeneral::class)->name('dashboard');
    Route::get('tablero', Tablero::class)->name('tablero');
    Route::get('historico', Historico::class)->name('historico');

    // Exportaciones (respetan los filtros vigentes vía query string)
    Route::get('export/{tipo}', [ExportController::class, 'excel'])->name('export.excel');
    Route::get('export/pdf/{tipo}', [ExportController::class, 'pdf'])->name('export.pdf');

    // Solo administradores
    Route::middleware('can:admin')->group(function () {
        Route::get('impresoras', Impresoras::class)->name('impresoras');
        Route::get('trabajos', Trabajos::class)->name('trabajos');
        Route::get('cortes', Cortes::class)->name('cortes');
        Route::get('configuracion', ConfiguracionPrecio::class)->name('configuracion');
        Route::get('correos/vista/{tipo}', [VistaCorreoController::class, 'ver'])->name('correos.vista');
        Route::get('correos/vista/{tipo}/{formato}', [VistaCorreoController::class, 'adjunto'])->name('correos.adjunto');
    });

    Route::view('profile', 'profile')->name('profile');

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/auth.php';
