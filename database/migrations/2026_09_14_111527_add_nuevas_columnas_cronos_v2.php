<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('Tarea', function (Blueprint $table) {
            $table->boolean('notificado_vencimiento')->default(false)->after('fechaLimite');
        });

        Schema::table('ConfiguracionPomodoro', function (Blueprint $table) {
            $table->boolean('sonido_activado')->default(true)->after('sesionesPrevioDescansoLargo');
            $table->integer('volumen_sonido')->default(80)->after('sonido_activado');
            $table->boolean('notificaciones_navegador')->default(true)->after('volumen_sonido');
        });

        Schema::table('Perfil', function (Blueprint $table) {
            $table->boolean('es_sala_estudio')->default(false)->after('descripcionPerfil');
        });
    }

    public function down()
    {
        Schema::table('Tarea', function (Blueprint $table) {
            $table->dropColumn('notificado_vencimiento');
        });
        Schema::table('ConfiguracionPomodoro', function (Blueprint $table) {
            $table->dropColumn(['sonido_activado', 'volumen_sonido', 'notificaciones_navegador']);
        });
        Schema::table('Perfil', function (Blueprint $table) {
            $table->dropColumn('es_sala_estudio');
        });
    }
};
