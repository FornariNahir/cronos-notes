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
        Schema::create('Perfil', function (Blueprint $table) {
            $table->id('idPerfil');
            $table->unsignedBigInteger('idUsuario');
            $table->string('tituloPerfil', 30);
            $table->string('descripcionPerfil', 100)->nullable();
            $table->timestamps();

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Perfil');
    }
};
