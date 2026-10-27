<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('dispositivo_id')->nullable()->constrained('dispositivos')->nullOnDelete();
            $table->unsignedInteger('impresiones')->default(0);
            $table->unsignedInteger('copias')->default(0);
            $table->unsignedInteger('escaneados')->default(0);
            $table->dateTime('fecha_corte')->index();   // ultimo corte
            $table->foreignId('importacion_id')->nullable()->constrained('importaciones')->nullOnDelete();
            $table->timestamps();

            $table->index(['cuenta_id', 'fecha_corte']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
