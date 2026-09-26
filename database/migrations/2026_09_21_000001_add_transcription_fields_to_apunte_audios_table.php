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
            if (!Schema::hasColumn('ApunteAudio', 'transcripcion')) {
                $table->longText('transcripcion')->nullable()->after('rutaAudio');
            }
            if (!Schema::hasColumn('ApunteAudio', 'resumen_ia')) {
                $table->json('resumen_ia')->nullable();
            }
            if (!Schema::hasColumn('ApunteAudio', 'estado')) {
                $table->string('estado', 30)->default('pendiente');
            }
            if (!Schema::hasColumn('ApunteAudio', 'error_mensaje')) {
                $table->text('error_mensaje')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ApunteAudio', function (Blueprint $table) {
            $cols = array_filter(
                ['transcripcion', 'resumen_ia', 'estado', 'error_mensaje'],
                fn($c) => Schema::hasColumn('ApunteAudio', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
