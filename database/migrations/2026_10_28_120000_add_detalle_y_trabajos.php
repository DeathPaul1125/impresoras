<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Estado actual (cada 15 min): estado, pantalla, tóner, alertas y ficha técnica.
        Schema::table('dispositivos', function (Blueprint $table) {
            $table->json('estado')->nullable();
            $table->dateTime('estado_en')->nullable();
        });

        // Foto diaria junto a la lectura de las 6 pm: tóner, papel por tamaño, simplex/dúplex.
        Schema::table('contadores_equipo', function (Blueprint $table) {
            $table->json('detalle')->nullable();
        });

        // Dúplex por cuenta (contabilidad de trabajos).
        Schema::table('contadores_cuenta', function (Blueprint $table) {
            $table->unsignedInteger('simplex')->nullable();
            $table->unsignedInteger('duplex')->nullable();
        });

        // Registro de trabajos de las impresoras (cada trabajo, consultado cada minuto).
        Schema::create('trabajos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispositivo_id')->constrained('dispositivos')->cascadeOnDelete();
            $table->string('origen', 10);          // impresion (impresión/copia) | envio (escaneo/envío)
            $table->unsignedBigInteger('trabajo');  // n.º de trabajo en la impresora
            $table->string('tipo', 12);             // impresion | copia | escaneo | envio | otro
            $table->string('documento')->nullable();
            $table->string('cuenta')->nullable();   // nombre de la cuenta (copias y escaneos en el panel)
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->string('usuario')->nullable();  // usuario de Windows (impresiones)
            $table->unsignedInteger('paginas')->default(0);
            $table->unsignedInteger('originales')->default(0);
            $table->unsignedInteger('juegos')->default(1);
            $table->string('estado', 12)->default('completado');
            $table->dateTime('inicio')->nullable();
            $table->dateTime('fin')->nullable();
            $table->timestamps();

            $table->unique(['dispositivo_id', 'origen', 'trabajo', 'inicio']);
            $table->index('inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajos');
        Schema::table('contadores_cuenta', fn (Blueprint $t) => $t->dropColumn(['simplex', 'duplex']));
        Schema::table('contadores_equipo', fn (Blueprint $t) => $t->dropColumn('detalle'));
        Schema::table('dispositivos', fn (Blueprint $t) => $t->dropColumn(['estado', 'estado_en']));
    }
};
