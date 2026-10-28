<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Historia mensual por impresora y usuario (clave), traída del sistema anterior (contabilidad de trabajos). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumo_mensual', function (Blueprint $table) {
            $table->id();
            $table->date('periodo');   // primer día del mes
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->cascadeOnDelete();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->unsignedInteger('impresiones')->default(0);
            $table->unsignedInteger('copias')->default(0);
            $table->unsignedInteger('escaneos')->default(0);
            $table->timestamps();
            $table->unique(['periodo', 'dispositivo_id', 'cuenta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumo_mensual');
    }
};
