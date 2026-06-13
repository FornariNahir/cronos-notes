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
        // 1. Quitar la columna rutaAudio de la tabla Apunte
        Schema::table('Apunte', function (Blueprint $table) {
            if (Schema::hasColumn('Apunte', 'rutaAudio')) {
                $table->dropColumn('rutaAudio');
            }
        });

        // 2. Crear la nueva tabla ApunteAudio
        Schema::create('ApunteAudio', function (Blueprint $table) {
            $table->id('idApunteAudio');
            $table->unsignedBigInteger('idApunte');
            $table->string('rutaAudio', 255);
            $table->timestamp('fechaCreacion')->nullable();

            $table->foreign('idApunte')
                ->references('idApunte')
                ->on('Apunte')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ApunteAudio');

        Schema::table('Apunte', function (Blueprint $table) {
            $table->string('rutaAudio', 255)->nullable()->after('resumenApunte');
        });
    }
};
