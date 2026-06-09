<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvitacionPerfil extends Model
{
    protected $table = 'InvitacionPerfil';
    protected $primaryKey = 'idInvitacion';
    public $timestamps = false;

    protected $fillable = [
        'idPerfil',
        'idUsuarioInvita',
        'emailInvitado',
        'idUsuarioInvitado',
        'permisoOfrecido',
        'estado',
        'fechaEnvio',
        'fechaExpiracion',
        'token',
        'tokenUtilizado',
    ];

    protected $casts = [
        'fechaEnvio' => 'datetime',
        'fechaExpiracion' => 'datetime',
        'tokenUtilizado' => 'boolean',
    ];

    // ──────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'idPerfil', 'idPerfil');
    }

    public function usuarioQueInvita()
    {
        return $this->belongsTo(User::class, 'idUsuarioInvita', 'idUsuario');
    }

    public function usuarioInvitado()
    {
        return $this->belongsTo(User::class, 'idUsuarioInvitado', 'idUsuario');
    }

    // ──────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────

    /**
     * Invitaciones pendientes y válidas (no expiradas, token no utilizado).
     */
    public function scopeValidas($query)
    {
        return $query->where('estado', 'Pendiente')
                     ->where('tokenUtilizado', false)
                     ->where(function ($q) {
                         $q->whereNull('fechaExpiracion')
                           ->orWhere('fechaExpiracion', '>', now());
                     });
    }

    /**
     * Invitaciones pendientes para un email específico.
     */
    public function scopeParaEmail($query, string $email)
    {
        return $query->where('emailInvitado', $email);
    }

    // ──────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────

    /**
     * Verifica si la invitación aún puede ser utilizada.
     */
    public function estaDisponible(): bool
    {
        if ($this->tokenUtilizado) {
            return false;
        }

        if ($this->estado !== 'Pendiente') {
            return false;
        }

        if ($this->fechaExpiracion && $this->fechaExpiracion->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Marca la invitación como aceptada y quema el token.
     */
    public function aceptar(int $idUsuarioInvitado): void
    {
        $this->update([
            'estado' => 'Aceptada',
            'tokenUtilizado' => true,
            'idUsuarioInvitado' => $idUsuarioInvitado,
        ]);
    }

    /**
     * Marca la invitación como rechazada y quema el token.
     */
    public function rechazar(): void
    {
        $this->update([
            'estado' => 'Rechazada',
            'tokenUtilizado' => true,
        ]);
    }
}
