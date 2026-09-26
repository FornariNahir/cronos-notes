<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ApunteAudio', function (Blueprint $table) {
            if (!Schema::hasColumn('ApunteAudio', 'nombreOriginal')) {
                $table->string('nombreOriginal')->nullable()->after('rutaAudio');
            }
            if (!Schema::hasColumn('ApunteAudio', 'transcripcion')) {
                $table->longText('transcripcion')->nullable()->after('nombreOriginal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ApunteAudio', function (Blueprint $table) {
            $table->dropColumn(['nombreOriginal', 'transcripcion']);
        });
    }
};
