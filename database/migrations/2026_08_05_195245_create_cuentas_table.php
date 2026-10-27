<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('id_cuenta')->unique();   // codigo externo, ej. 9001
            $table->string('nombre_cuenta');         // SG_Bililin Menendez
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
