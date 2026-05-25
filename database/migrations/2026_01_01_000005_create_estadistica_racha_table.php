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
        Schema::create('Estadistica', function (Blueprint $table) {
            $table->id('idEstadistica');
            $table->unsignedBigInteger('idUsuario');
            $table->integer('tareasTotales')->default(0);
            $table->integer('tiempoTotalPomodoro')->default(0);
            $table->integer('rachaMasLarga')->default(0);
            $table->integer('rachaActual')->default(0);
            $table->integer('sesionesCanceladas')->nullable();
            $table->decimal('horasConcentracionDiaria', 5, 2)->nullable();

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        Schema::create('Racha', function (Blueprint $table) {
            $table->id('idRacha');
            $table->unsignedBigInteger('idUsuario');
            $table->date('fechaInicioRacha')->nullable();
            $table->date('fechaFinRacha')->nullable();
            $table->integer('rachaActual')->default(0);
            $table->boolean('rachaActiva')->default(1);

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Racha');
        Schema::dropIfExists('Estadistica');
    }
};