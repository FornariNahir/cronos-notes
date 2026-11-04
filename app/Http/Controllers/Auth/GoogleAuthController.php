<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\IntegracionExterna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

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
            // 1. Obtener usuario de Google (con tolerancia a desincronización de estado/sesión en entornos locales)
            try {
                $googleUser = Socialite::driver('google')->user();
            } catch (InvalidStateException $e) {
                Log::warning('[GoogleAuthController] InvalidStateException en callback de Google. Reintentando con stateless() para resolver posible inconsistencia de sesión/puerto.');
                $googleUser = Socialite::driver('google')->stateless()->user();
            }

            if (!$googleUser || !$googleUser->getEmail()) {
                throw new \Exception('No se pudo recuperar la información del correo desde Google.');
            }

            // 2. Buscar si existe un usuario con este email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Si el usuario no existe, lo registramos.
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

            // 3. Crear o actualizar la integración de GoogleAuth en IntegracionExterna
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

            // 4. Iniciar sesión en Laravel
            Auth::login($user);

            // 5. Iniciar sesión personalizada y generar cookies necesarias
            $sesion = $user->iniciarSesionPersonalizada();
            $request->session()->regenerate();

            $cookie = cookie('cronos_session_token', $sesion->tokenSesionUsuario, 1440, null, null, false, true);

            // Evitar que redirect intended devuelva a login si esa era la URL previa
            $intended = session()->pull('url.intended', route('dashboard', absolute: false));
            if (str_contains($intended, '/login') || str_contains($intended, '/register')) {
                $intended = route('dashboard', absolute: false);
            }

            return redirect()->to($intended)->withCookie($cookie);

        } catch (\Exception $e) {
            Log::error('[GoogleAuthController] Error en callback de autenticación: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Hubo un problema al autenticar con Google: ' . $e->getMessage(),
            ]);
        }
    }
}
