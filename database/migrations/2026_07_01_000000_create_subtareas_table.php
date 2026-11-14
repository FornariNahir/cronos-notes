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
        Schema::create('subtareas', function (Blueprint $table) {
            $table->id('idSubtarea');
            $table->unsignedBigInteger('idTarea');
            $table->string('titulo', 150);
            $table->boolean('completada')->default(false);
            $table->unsignedTinyInteger('estimacionEsfuerzo')->default(1);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('idTarea')
                ->references('idTarea')
                ->on('Tarea')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subtareas');
    }
};
