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
        Schema::create('tareas', function (Blueprint $table) {
            $table->id('idTarea');
        $table->unsignedBigInteger('idPerfil');
        $table->string('tituloTarea', 45);
        $table->string('descripcionTarea', 200)->nullable();
        $table->date('fechaInicioTarea');
        $table->date('fechaFinTarea')->nullable();
        $table->date('fechaLimite');
        $table->enum('estadoTarea', ['Pendiente', 'En Progreso', 'Completado'])->default('Pendiente');
        $table->enum('prioridadTarea', ['Baja', 'Media', 'Alta'])->nullable();
        $table->timestamps();

        // Relación con la tabla perfiles
        $table->foreign('idPerfil')
              ->references('idPerfil')
              ->on('perfiles')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
