<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla para gestionar el flujo de invitación al compartir perfiles.
     * El dueño de un perfil invita a otro usuario por email, el invitado
     * recibe un link con token único para aceptar o rechazar.
     */
    public function up(): void
    {
        Schema::create('InvitacionPerfil', function (Blueprint $table) {
            $table->id('idInvitacion');
            $table->unsignedBigInteger('idPerfil');
            $table->unsignedBigInteger('idUsuarioInvita');
            $table->string('emailInvitado', 100);
            $table->unsignedBigInteger('idUsuarioInvitado')->nullable();
            $table->enum('permisoOfrecido', ['Lector', 'Editor', 'Administrador'])->default('Lector');
            $table->enum('estado', ['Pendiente', 'Aceptada', 'Rechazada', 'Expirada'])->default('Pendiente');
            $table->timestamp('fechaEnvio')->useCurrent();
            $table->timestamp('fechaExpiracion')->nullable();
            $table->string('token', 255)->unique();
            $table->boolean('tokenUtilizado')->default(false);

            $table->foreign('idPerfil')
                ->references('idPerfil')->on('Perfil')
                ->onDelete('cascade');

            $table->foreign('idUsuarioInvita')
                ->references('idUsuario')->on('Usuario')
                ->onDelete('cascade');

            $table->foreign('idUsuarioInvitado')
                ->references('idUsuario')->on('Usuario')
                ->onDelete('set null');

            // Índice para buscar invitaciones pendientes por email
            $table->index(['emailInvitado', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('InvitacionPerfil');
    }
};
