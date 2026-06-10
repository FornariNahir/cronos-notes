<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cambia el ENUM de permisos de acciones individuales (Crear/Modificar/Leer/Borrar)
     * a roles jerárquicos (Lector/Editor/Administrador), estilo Google Docs.
     *
     * Jerarquía:
     *   Lector        → solo lectura
     *   Editor        → lectura + crear + modificar
     *   Administrador → lectura + crear + modificar + borrar + gestionar permisos
     */
    public function up(): void
    {
        // Mapear valores existentes a los nuevos roles antes de cambiar el ENUM
        DB::table('PerfilCompartido')->where('permiso', 'Leer')->update(['permiso' => 'Lector']);
        DB::table('PerfilCompartido')->where('permiso', 'Crear')->update(['permiso' => 'Editor']);
        DB::table('PerfilCompartido')->where('permiso', 'Modificar')->update(['permiso' => 'Editor']);
        DB::table('PerfilCompartido')->where('permiso', 'Borrar')->update(['permiso' => 'Administrador']);

        // Cambiar el ENUM — SQLite no soporta ALTER COLUMN, así que reconstruimos
        // Para MySQL, usamos raw SQL
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recrear tabla (limitación del driver)
            Schema::table('PerfilCompartido', function (Blueprint $table) {
                $table->string('permiso_nuevo')->default('Lector')->after('idPerfil');
            });
            DB::table('PerfilCompartido')->update(['permiso_nuevo' => DB::raw('permiso')]);
            Schema::table('PerfilCompartido', function (Blueprint $table) {
                $table->dropColumn('permiso');
            });
            Schema::table('PerfilCompartido', function (Blueprint $table) {
                $table->renameColumn('permiso_nuevo', 'permiso');
            });
        } else {
            DB::statement("ALTER TABLE PerfilCompartido MODIFY COLUMN permiso ENUM('Lector', 'Editor', 'Administrador') NOT NULL DEFAULT 'Lector'");
        }

        // Agregar campos de auditoría
        Schema::table('PerfilCompartido', function (Blueprint $table) {
            $table->unsignedBigInteger('compartidoPor')->nullable()->after('permiso');
            $table->timestamp('fechaCompartido')->useCurrent()->after('compartidoPor');

            $table->foreign('compartidoPor')
                ->references('idUsuario')->on('Usuario')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('PerfilCompartido', function (Blueprint $table) {
            $table->dropForeign(['compartidoPor']);
            $table->dropColumn(['compartidoPor', 'fechaCompartido']);
        });

        // Revertir ENUM
        if (DB::getDriverName() !== 'sqlite') {
            // Mapear de vuelta
            DB::table('PerfilCompartido')->where('permiso', 'Lector')->update(['permiso' => 'Leer']);
            DB::table('PerfilCompartido')->where('permiso', 'Editor')->update(['permiso' => 'Modificar']);
            DB::table('PerfilCompartido')->where('permiso', 'Administrador')->update(['permiso' => 'Borrar']);

            DB::statement("ALTER TABLE PerfilCompartido MODIFY COLUMN permiso ENUM('Crear', 'Modificar', 'Leer', 'Borrar') NULL DEFAULT 'Leer'");
        }
    }
};
