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
        Schema::create('SesionUsuario', function (Blueprint $table) {
            $table->id('idSesionUsuario');
            $table->unsignedBigInteger('idUsuario')->nullable();
            $table->string('tokenSesionUsuario', 255)->unique();
            $table->dateTime('fechaAlta');
            $table->dateTime('fechaCaducidad');
            $table->boolean('activa')->default(1);

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('no action')
                ->onUpdate('no action');
        });

        Schema::create('RecuperacionPassword', function (Blueprint $table) {
            $table->id('idRecuperacionPassword');
            $table->unsignedBigInteger('idUsuario');
            $table->string('tokenRecuperacion', 255);
            $table->timestamp('fechaGeneracion');
            $table->boolean('utilizado');

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('Usuario')
                ->onDelete('no action')
                ->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('RecuperacionPassword');
        Schema::dropIfExists('SesionUsuario');
    }
};
