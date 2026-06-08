<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\IntegracionExterna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir al usuario a la página de autenticación de Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Manejar la respuesta de autenticación de Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Hubo un problema al autenticar con Google. Por favor, intenta de nuevo.',
            ]);
        }

        // Buscar si existe un usuario con este email
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // Si el usuario no existe, lo registramos.
            // Extraer nombre y apellido de los datos crudos de Google o procesar el nombre completo.
            $rawUser = $googleUser->getRaw();
            $nombre = $rawUser['given_name'] ?? '';
            $apellido = $rawUser['family_name'] ?? '';

            if (empty($nombre)) {
                $parts = explode(' ', $googleUser->getName(), 2);
                $nombre = $parts[0] ?? 'Google';
                $apellido = $parts[1] ?? 'User';
            }

            $user = User::create([
                'nombre' => Str::limit($nombre, 50, ''),
                'apellido' => Str::limit($apellido, 50, ''),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(32)),
                'usuarioConectado' => true,
            ]);
        }

        // Crear o actualizar la integración de GoogleAuth en IntegracionExterna
        IntegracionExterna::updateOrCreate(
            [
                'idUsuario' => $user->idUsuario,
                'plataforma' => 'GoogleAuth',
            ],
            [
                'identificadorExterno' => $googleUser->getId(),
                'tokenAcceso' => $googleUser->token,
                'tokenNuevo' => $googleUser->refreshToken,
            ]
        );

        // Iniciar sesión en Laravel
        Auth::login($user);

        // Iniciar sesión personalizada y generar cookies necesarias
        $sesion = $user->iniciarSesionPersonalizada();
        $request->session()->regenerate();

        $cookie = cookie('cronos_session_token', $sesion->tokenSesionUsuario, 1440, null, null, false, true);

        return redirect()->intended(route('dashboard', absolute: false))->withCookie($cookie);
    }
}
