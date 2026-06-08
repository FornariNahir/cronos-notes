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
            $table->string('identificadorExterno')->nullable()->comment('ID único devuelto por el proveedor (ej. Google ID)');
            $table->text('tokenAcceso')->nullable();
            $table->text('tokenNuevo')->nullable()->comment('Refresh Token u otro token secundario');
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
