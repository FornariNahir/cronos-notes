<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ConfiguracionPomodoro', function (Blueprint $table) {
            $table->id('idConfiguracionPomodoro');
            $table->unsignedBigInteger('idUsuario');
            $table->integer('duracionSesion')->default(25);
            $table->integer('duracionDescansoCorto')->default(5);
            $table->integer('duracionDescansoLargo')->default(15);
            $table->integer('sesionesPrevioDescansoLargo')->default(4);
            $table->timestamp('fechaCreacionConfiguracion')->useCurrent();
            $table->timestamps();

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ConfiguracionPomodoro');
    }
};
