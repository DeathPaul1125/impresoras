<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cortes mensuales del contrato con el proveedor: contador B/N (impresiones + copias) de cada equipo.
        Schema::create('cortes', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha')->index();
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->cascadeOnDelete();
            $table->string('serie', 30)->nullable();      // serie del equipo al momento del corte
            $table->unsignedInteger('contador');
            $table->string('origen', 10)->default('snmp'); // snmp | manual | historico
            $table->timestamps();

            $table->unique(['fecha', 'dispositivo_id']);
        });

        Schema::table('dispositivos', function (Blueprint $table) {
            $table->string('nivel', 10)->nullable()->after('ubicacion');   // 1, 7.1, 7.2, ...
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortes');
        Schema::table('dispositivos', fn (Blueprint $table) => $table->dropColumn('nivel'));
    }
};
