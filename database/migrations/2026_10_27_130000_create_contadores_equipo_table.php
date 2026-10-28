<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Totales del equipo leídos por SNMP (impresoras:leer-snmp, diario 18:00).
        Schema::create('contadores_equipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->cascadeOnDelete();
            $table->unsignedInteger('total')->nullable();
            $table->unsignedInteger('impresiones')->nullable();
            $table->unsignedInteger('copias')->nullable();
            $table->unsignedInteger('escaneos')->nullable();
            $table->dateTime('leido_en');

            $table->index(['dispositivo_id', 'leido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contadores_equipo');
    }
};
