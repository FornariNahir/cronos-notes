<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            // Si el usuario ya está autenticado en la sesión local con este mismo token validado recientemente, evitamos consultar la BD remota en cada clic
            if (
                Auth::check() &&
                $request->session()->get('cronos_validated_token') === $token &&
                $request->session()->get('cronos_validated_until', 0) > time()
            ) {
                return $next($request);
            }

            $sesion = Cache::remember("sesion_token_{$token}", 120, function () use ($token) {
                return SesionUsuario::where('tokenSesionUsuario', $token)->first();
            });

            if ($sesion && $sesion->isValid()) {
                if (!Auth::check() || Auth::id() !== $sesion->idUsuario) {
                    Auth::loginUsingId($sesion->idUsuario);
                }

                $request->session()->put('cronos_validated_token', $token);
                $request->session()->put('cronos_validated_until', time() + 180);

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
