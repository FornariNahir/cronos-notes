<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Apunte', function (Blueprint $table) {
            $table->string('tipoApunte', 20)->default('normal')->after('idPerfil');
            $table->longText('ideasApunte')->nullable()->after('contenidoApunte');
            $table->longText('resumenApunte')->nullable()->after('ideasApunte');
            $table->string('rutaAudio', 255)->nullable()->after('resumenApunte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Apunte', function (Blueprint $table) {
            $table->dropColumn(['tipoApunte', 'ideasApunte', 'resumenApunte', 'rutaAudio']);
        });
    }
};
