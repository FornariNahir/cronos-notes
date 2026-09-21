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
        Schema::table('ApunteAudio', function (Blueprint $table) {
            $table->longText('transcripcion')->nullable()->after('rutaAudio');
            $table->json('resumen_ia')->nullable()->after('transcripcion');
            $table->string('estado', 30)->default('pendiente')->after('resumen_ia');
            $table->text('error_mensaje')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ApunteAudio', function (Blueprint $table) {
            $table->dropColumn([
                'transcripcion',
                'resumen_ia',
                'estado',
                'error_mensaje'
            ]);
        });
    }
};
