<?php

namespace App\Policies;

use App\Models\Perfil;
use App\Models\User;

class PerfilPolicy
{
    /**
     * Jerarquía de roles:
     *   Lector        → solo ver contenido
     *   Editor        → ver + crear + modificar
     *   Administrador → ver + crear + modificar + borrar + gestionar permisos
     */
    private const JERARQUIA = [
        'Lector' => 1,
        'Editor' => 2,
        'Administrador' => 3,
    ];

    /**
     * El propietario siempre puede hacer todo.
     */
    public function before(User $user, string $ability, $perfil = null): ?bool
    {
        if ($perfil instanceof Perfil && $perfil->idUsuario === $user->idUsuario) {
            return true;
        }

        return null; // Continúa con la verificación del método específico
    }

    /**
     * ¿Puede ver el perfil y su contenido (tareas, apuntes)?
     * Requiere: Lector o superior
     */
    public function ver(User $user, Perfil $perfil): bool
    {
        return $this->tienePermisoMinimo($user, $perfil, 'Lector');
    }

    /**
     * ¿Puede crear tareas/apuntes dentro del perfil?
     * Requiere: Editor o superior
     */
    public function crear(User $user, Perfil $perfil): bool
    {
        return $this->tienePermisoMinimo($user, $perfil, 'Editor');
    }

    /**
     * ¿Puede modificar tareas/apuntes existentes dentro del perfil?
     * Requiere: Editor o superior
     */
    public function modificar(User $user, Perfil $perfil): bool
    {
        return $this->tienePermisoMinimo($user, $perfil, 'Editor');
    }

    /**
     * ¿Puede eliminar tareas/apuntes del perfil?
     * Requiere: Administrador
     */
    public function borrar(User $user, Perfil $perfil): bool
    {
        return $this->tienePermisoMinimo($user, $perfil, 'Administrador');
    }

    /**
     * ¿Puede gestionar permisos de otros usuarios sobre este perfil?
     * Requiere: Administrador (o ser propietario, que se resuelve en before)
     */
    public function gestionarPermisos(User $user, Perfil $perfil): bool
    {
        return $this->tienePermisoMinimo($user, $perfil, 'Administrador');
    }

    /**
     * Verifica si el usuario tiene un permiso igual o superior al requerido.
     */
    private function tienePermisoMinimo(User $user, Perfil $perfil, string $permisoRequerido): bool
    {
        $compartido = $perfil->usuariosCompartidos()
            ->where('PerfilCompartido.idUsuario', $user->idUsuario)
            ->first();

        if (!$compartido) {
            return false;
        }

        $permisoUsuario = $compartido->pivot->permiso;
        $nivelUsuario = self::JERARQUIA[$permisoUsuario] ?? 0;
        $nivelRequerido = self::JERARQUIA[$permisoRequerido] ?? 0;

        return $nivelUsuario >= $nivelRequerido;
    }
}
