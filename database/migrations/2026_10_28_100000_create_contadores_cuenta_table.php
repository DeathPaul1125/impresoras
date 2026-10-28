<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contadores acumulados por cuenta (clave) y equipo, leídos del Command Center (contabilidad de trabajos).
        Schema::create('contadores_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->cascadeOnDelete();
            $table->unsignedInteger('impresiones')->default(0);
            $table->unsignedInteger('copias')->default(0);
            $table->unsignedInteger('escaneos')->default(0);
            $table->dateTime('leido_en');

            $table->index(['cuenta_id', 'dispositivo_id', 'leido_en']);
            $table->index('leido_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores_cuenta');
    }
};
