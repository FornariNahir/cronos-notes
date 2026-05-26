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
        Schema::create('IntegracionExterna', function (Blueprint $table) {
            $table->id('idIntegracionExterna');
            $table->unsignedBigInteger('idUsuario');
            $table->enum('plataforma', ['GoogleCalendar', 'Spotify', 'GoogleAuth']);
            $table->string('tokenAcceso', 255);
            $table->string('tokenNuevo', 255);
            $table->unique(['idUsuario', 'plataforma'], 'uq_usuario_plataforma');

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('cascade')
                ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('IntegracionExterna');
    }
};
