
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
        Schema::create('PerfilCompartido', function (Blueprint $table) {
            $table->primary(['idUsuario', 'idPerfil']);
            $table->unsignedBigInteger('idUsuario');
            $table->unsignedBigInteger('idPerfil');
            $table->enum('permiso', ['Crear', 'Modificar', 'Leer', 'Borrar'])->default('Leer');

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
        Schema::dropIfExists('PerfilCompartido');
    }
};