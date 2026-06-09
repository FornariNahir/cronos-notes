<?php

namespace App\Http\Controllers;

use App\Models\InvitacionPerfil;
use App\Models\Perfil;
use App\Models\PerfilCompartido;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PerfilCompartidoController extends Controller
{
    /**
     * Muestra los usuarios con acceso a un perfil y las invitaciones pendientes.
     * Solo el propietario o un Administrador pueden ver esta lista.
     */
    public function index($idPerfil)
    {
        $perfil = Perfil::findOrFail($idPerfil);
        $user = Auth::user();

        // Verificar que sea propietario o Administrador
        if ($perfil->idUsuario !== $user->idUsuario) {
            $compartido = PerfilCompartido::where('idUsuario', $user->idUsuario)
                ->where('idPerfil', $idPerfil)
                ->where('permiso', 'Administrador')
                ->first();

            if (!$compartido) {
                abort(403, 'No tenés permisos para gestionar este perfil compartido.');
            }
        }

        $usuariosCompartidos = $perfil->usuariosCompartidos()
            ->select('Usuario.idUsuario', 'nombre', 'apellido', 'email')
            ->get()
            ->map(function ($usuario) {
                return [
                    'idUsuario' => $usuario->idUsuario,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'email' => $usuario->email,
                    'permiso' => $usuario->pivot->permiso,
                    'fechaCompartido' => $usuario->pivot->fechaCompartido ?? null,
                ];
            });

        $invitacionesPendientes = InvitacionPerfil::where('idPerfil', $idPerfil)
            ->where('estado', 'Pendiente')
            ->where('tokenUtilizado', false)
            ->get(['idInvitacion', 'emailInvitado', 'permisoOfrecido', 'fechaEnvio']);

        return Inertia::render('PerfilCompartido/Index', [
            'perfil' => $perfil,
            'usuariosCompartidos' => $usuariosCompartidos,
            'invitacionesPendientes' => $invitacionesPendientes,
            'esPropietario' => $perfil->idUsuario === $user->idUsuario,
        ]);
    }

    /**
     * Envía una invitación para compartir un perfil con otro usuario.
     * Busca al usuario por email y crea la invitación con token.
     */
    public function compartir(Request $request, $idPerfil)
    {
        $request->validate([
            'email' => 'required|email|max:100',
            'permiso' => 'required|in:Lector,Editor,Administrador',
        ]);

        $perfil = Perfil::findOrFail($idPerfil);
        $user = Auth::user();

        // Solo el propietario o Administrador puede compartir
        if ($perfil->idUsuario !== $user->idUsuario) {
            $compartido = PerfilCompartido::where('idUsuario', $user->idUsuario)
                ->where('idPerfil', $idPerfil)
                ->where('permiso', 'Administrador')
                ->first();

            if (!$compartido) {
                abort(403, 'No tenés permisos para compartir este perfil.');
            }
        }

        // No se puede compartir con uno mismo
        if ($request->email === $user->email) {
            return redirect()->back()->withErrors([
                'email' => 'No podés compartir un perfil con vos mismo.',
            ]);
        }

        // Verificar si ya tiene acceso
        $usuarioDestino = User::where('email', $request->email)->first();
        if ($usuarioDestino) {
            $yaCompartido = PerfilCompartido::where('idUsuario', $usuarioDestino->idUsuario)
                ->where('idPerfil', $idPerfil)
                ->exists();

            if ($yaCompartido) {
                return redirect()->back()->withErrors([
                    'email' => 'Este usuario ya tiene acceso al perfil.',
                ]);
            }
        }

        // Verificar si ya hay una invitación pendiente para ese email
        $invitacionExistente = InvitacionPerfil::where('idPerfil', $idPerfil)
            ->where('emailInvitado', $request->email)
            ->where('estado', 'Pendiente')
            ->where('tokenUtilizado', false)
            ->exists();

        if ($invitacionExistente) {
            return redirect()->back()->withErrors([
                'email' => 'Ya hay una invitación pendiente para este email.',
            ]);
        }

        // Crear la invitación
        InvitacionPerfil::create([
            'idPerfil' => $idPerfil,
            'idUsuarioInvita' => $user->idUsuario,
            'emailInvitado' => $request->email,
            'idUsuarioInvitado' => $usuarioDestino?->idUsuario,
            'permisoOfrecido' => $request->permiso,
            'estado' => 'Pendiente',
            'fechaEnvio' => now(),
            'fechaExpiracion' => now()->addDays(7),
            'token' => Str::random(64),
            'tokenUtilizado' => false,
        ]);

        return redirect()->back()->with('success', 'Invitación enviada correctamente.');
    }

    /**
     * Actualiza el permiso de un usuario que ya tiene acceso al perfil.
     */
    public function actualizarPermiso(Request $request, $idPerfil, $idUsuario)
    {
        $request->validate([
            'permiso' => 'required|in:Lector,Editor,Administrador',
        ]);

        $perfil = Perfil::findOrFail($idPerfil);
        $user = Auth::user();

        // Solo el propietario puede cambiar permisos
        if ($perfil->idUsuario !== $user->idUsuario) {
            abort(403, 'Solo el propietario puede cambiar permisos.');
        }

        $compartido = PerfilCompartido::where('idUsuario', $idUsuario)
            ->where('idPerfil', $idPerfil)
            ->firstOrFail();

        $compartido->update(['permiso' => $request->permiso]);

        return redirect()->back()->with('success', 'Permiso actualizado correctamente.');
    }

    /**
     * Revoca el acceso de un usuario al perfil compartido.
     */
    public function revocar($idPerfil, $idUsuario)
    {
        $perfil = Perfil::findOrFail($idPerfil);
        $user = Auth::user();

        // Solo el propietario puede revocar acceso
        if ($perfil->idUsuario !== $user->idUsuario) {
            abort(403, 'Solo el propietario puede revocar acceso.');
        }

        PerfilCompartido::where('idUsuario', $idUsuario)
            ->where('idPerfil', $idPerfil)
            ->delete();

        return redirect()->back()->with('success', 'Acceso revocado correctamente.');
    }

    /**
     * Cancela una invitación pendiente.
     */
    public function cancelarInvitacion($idInvitacion)
    {
        $invitacion = InvitacionPerfil::findOrFail($idInvitacion);
        $perfil = Perfil::findOrFail($invitacion->idPerfil);
        $user = Auth::user();

        if ($perfil->idUsuario !== $user->idUsuario && $invitacion->idUsuarioInvita !== $user->idUsuario) {
            abort(403, 'No tenés permisos para cancelar esta invitación.');
        }

        $invitacion->update([
            'estado' => 'Expirada',
            'tokenUtilizado' => true,
        ]);

        return redirect()->back()->with('success', 'Invitación cancelada.');
    }

    // ──────────────────────────────────────────────────────────────
    // Flujo del INVITADO (acepta o rechaza)
    // ──────────────────────────────────────────────────────────────

    /**
     * Muestra la pantalla de invitación al acceder al link con token.
     * No requiere autenticación para ver, pero sí para aceptar.
     */
    public function verInvitacion($token)
    {
        $invitacion = InvitacionPerfil::where('token', $token)
            ->with(['perfil', 'usuarioQueInvita'])
            ->firstOrFail();

        if (!$invitacion->estaDisponible()) {
            return Inertia::render('PerfilCompartido/InvitacionExpirada', [
                'motivo' => $invitacion->tokenUtilizado ? 'utilizada' : 'expirada',
            ]);
        }

        return Inertia::render('PerfilCompartido/Invitacion', [
            'invitacion' => [
                'token' => $invitacion->token,
                'perfil' => $invitacion->perfil->tituloPerfil,
                'invitadoPor' => $invitacion->usuarioQueInvita->nombre . ' ' . $invitacion->usuarioQueInvita->apellido,
                'permiso' => $invitacion->permisoOfrecido,
                'fechaExpiracion' => $invitacion->fechaExpiracion,
            ],
        ]);
    }

    /**
     * Acepta una invitación. Requiere autenticación.
     * Crea el registro en PerfilCompartido y quema el token.
     */
    public function aceptarInvitacion($token)
    {
        $invitacion = InvitacionPerfil::where('token', $token)->firstOrFail();
        $user = Auth::user();

        if (!$invitacion->estaDisponible()) {
            return redirect()->route('dashboard')->withErrors([
                'invitacion' => 'Esta invitación ya no es válida.',
            ]);
        }

        // Verificar que el email coincida (o que sea el usuario invitado)
        if ($invitacion->emailInvitado !== $user->email && $invitacion->idUsuarioInvitado !== $user->idUsuario) {
            return redirect()->route('dashboard')->withErrors([
                'invitacion' => 'Esta invitación no es para tu cuenta.',
            ]);
        }

        // Crear el acceso compartido
        PerfilCompartido::updateOrCreate(
            [
                'idUsuario' => $user->idUsuario,
                'idPerfil' => $invitacion->idPerfil,
            ],
            [
                'permiso' => $invitacion->permisoOfrecido,
                'compartidoPor' => $invitacion->idUsuarioInvita,
                'fechaCompartido' => now(),
            ]
        );

        // Quemar el token
        $invitacion->aceptar($user->idUsuario);

        return redirect()->route('dashboard')->with('success', '¡Invitación aceptada! Ya tenés acceso al perfil.');
    }

    /**
     * Rechaza una invitación. Requiere autenticación.
     */
    public function rechazarInvitacion($token)
    {
        $invitacion = InvitacionPerfil::where('token', $token)->firstOrFail();

        if (!$invitacion->estaDisponible()) {
            return redirect()->route('dashboard')->withErrors([
                'invitacion' => 'Esta invitación ya no es válida.',
            ]);
        }

        $invitacion->rechazar();

        return redirect()->route('dashboard')->with('success', 'Invitación rechazada.');
    }
}
