<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            $table->string('ubicacion')->nullable()->after('id');   // Lobby, DAB 7o., ...
            $table->string('serie', 30)->nullable()->after('modelo_id');
        });

        // Ubicaciones de las impresoras existentes.
        foreach ([
            '192.100.10.50' => 'Lobby',
            '192.100.12.6' => 'URC 7o.',
            '192.100.10.110' => 'DAB 7o.',
            '192.100.10.212' => 'DCR 8o.',
            '192.100.10.215' => 'DAF 8o.',
            '192.100.10.144' => 'DAJ 9o.',
            '192.100.10.176' => 'SG 9o.',
        ] as $ip => $ubicacion) {
            DB::table('dispositivos')->where('direccion_ip', $ip)->update(['ubicacion' => $ubicacion]);
        }
    }

    public function down(): void
    {
        Schema::table('dispositivos', function (Blueprint $table) {
            $table->dropColumn(['ubicacion', 'serie']);
        });
    }
};
