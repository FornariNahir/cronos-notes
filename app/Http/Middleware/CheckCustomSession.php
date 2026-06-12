<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SesionUsuario;
use Symfony\Component\HttpFoundation\Response;

class CheckCustomSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && Auth::check()) {
            return $next($request);
        }

        $token = $request->cookie('cronos_session_token');

        if ($token) {
            $sesion = SesionUsuario::where('tokenSesionUsuario', $token)->first();

            if ($sesion && $sesion->isValid()) {
                // Autenticar programáticamente al usuario solo si no está autenticado como tal
                if (!Auth::check() || Auth::id() !== $sesion->idUsuario) {
                    Auth::loginUsingId($sesion->idUsuario);
                }

                return $next($request);
            }
        }

        // Si no es válido, asegurar que esté deslogueado y redirigir
        if (Auth::check()) {
            Auth::logout();
        }

        return redirect()->guest(route('login'))->withoutCookie('cronos_session_token');
    }
}
