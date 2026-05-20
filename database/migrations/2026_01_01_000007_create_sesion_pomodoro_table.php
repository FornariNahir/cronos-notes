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
        Schema::create('SesionPomodoro', function (Blueprint $table) {
            $table->id('idSesionPomodoro');
            $table->unsignedBigInteger('idConfiguracionPomodoro');
            $table->unsignedBigInteger('idTarea')->nullable();
            $table->timestamp('fechaCreacionSesion')->useCurrent();
            $table->integer('tiempoTrabajoTotalMinutos')->nullable();
            $table->enum('estadoSesion', ['Completada', 'Cancelada', 'Pausada', 'En Progreso'])->nullable();
            $table->integer('ciclosObjetivo')->nullable();
            $table->integer('ciclosCompletados')->nullable();

            $table->foreign('idConfiguracionPomodoro')
                ->references('idConfiguracionPomodoro')
                ->on('ConfiguracionPomodoro')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('idTarea')
                ->references('idTarea')
                ->on('Tarea')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('SesionPomodoro');
    }
};
