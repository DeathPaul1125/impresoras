<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // La contraseña SMTP cifrada no cabe en 255 caracteres.
    public function up(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->text('valor')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones', function (Blueprint $table) {
            $table->string('valor')->nullable()->change();
        });
    }
};
