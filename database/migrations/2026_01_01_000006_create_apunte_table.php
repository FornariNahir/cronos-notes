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
        Schema::create('Apunte', function (Blueprint $table) {
            $table->id('idApunte');
            $table->unsignedBigInteger('idPerfil');
            $table->string('tituloApunte', 100);
            $table->longText('contenidoApunte')->nullable();
            $table->timestamp('fechaCreacion')->nullable();

            $table->foreign('idPerfil')
                ->references('idPerfil')
                ->on('Perfil')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Apunte');
    }
};
