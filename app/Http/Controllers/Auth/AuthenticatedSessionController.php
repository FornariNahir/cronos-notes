<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        
        // Iniciar la sesión personalizada en la base de datos
        $sesion = $user->iniciarSesionPersonalizada();

        $request->session()->regenerate();

        // Crear una cookie segura con el token de sesión (caduca en 24 horas / 1440 minutos)
        $cookie = cookie('cronos_session_token', $sesion->tokenSesionUsuario, 1440, null, null, false, true);

        return redirect()->intended(route('dashboard', absolute: false))->withCookie($cookie);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $token = $request->cookie('cronos_session_token');
        $user = Auth::user();

        if ($user && $token) {
            $user->cerrarSesionPersonalizada($token);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->withoutCookie('cronos_session_token');
    }
}
