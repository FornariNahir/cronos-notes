<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [trans(\Illuminate\Support\Facades\Password::INVALID_USER)],
            ]);
        }

        // Buscamos un token válido y no utilizado que no haya expirado (60 minutos)
        $recuperacion = $user->recuperacionesPassword()
            ->where('utilizado', false)
            ->where('fechaGeneracion', '>', now()->subMinutes(60))
            ->get()
            ->first(function ($rec) use ($request) {
                return Hash::check($request->token, $rec->tokenRecuperacion);
            });

        if (! $recuperacion) {
            throw ValidationException::withMessages([
                'email' => [trans(\Illuminate\Support\Facades\Password::INVALID_TOKEN)],
            ]);
        }

        // Actualizamos la contraseña y generamos nuevo remember_token
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        // Marcamos el token como utilizado
        $recuperacion->update(['utilizado' => true]);

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', trans(\Illuminate\Support\Facades\Password::PASSWORD_RESET));
    }
}
