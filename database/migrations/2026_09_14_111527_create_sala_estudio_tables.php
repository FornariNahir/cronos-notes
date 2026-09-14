<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('SalaEstudio', function (Blueprint $table) {
            $table->id('idSalaEstudio');
            $table->integer('idPerfil');
            $table->integer('idUsuarioCreador');
            $table->string('googleMeetUrl', 500);
            $table->string('googleMeetId', 100)->nullable();
            $table->string('tituloEvento', 150)->default('Sesión de Estudio Grupal');
            $table->enum('estado', ['Activa', 'Finalizada'])->default('Activa');
            $table->timestamp('fechaCreacion')->useCurrent();
            $table->timestamp('fechaFin')->nullable();

            $table->foreign('idPerfil')->references('idPerfil')->on('Perfil')->onDelete('cascade');
            $table->foreign('idUsuarioCreador')->references('idUsuario')->on('Usuario')->onDelete('cascade');
        });

        Schema::create('PresenciaSalaEstudio', function (Blueprint $table) {
            $table->integer('idPerfil');
            $table->integer('idUsuario');
            $table->enum('estadoUsuario', ['EnLinea', 'EnPomodoro', 'EnMeet', 'Desconectado'])->default('EnLinea');
            $table->timestamp('ultimoPing')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['idPerfil', 'idUsuario']);
            $table->foreign('idPerfil')->references('idPerfil')->on('Perfil')->onDelete('cascade');
            $table->foreign('idUsuario')->references('idUsuario')->on('Usuario')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('PresenciaSalaEstudio');
        Schema::dropIfExists('SalaEstudio');
    }
};
