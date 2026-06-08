<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user) {
            $token = \Illuminate\Support\Str::random(60);

            $user->recuperacionesPassword()->create([
                'tokenRecuperacion' => \Illuminate\Support\Facades\Hash::make($token),
                'fechaGeneracion' => now(),
                'utilizado' => false,
            ]);

            $user->notify(new \App\Notifications\CustomResetPassword($token));
        }

        // Devolvemos success siempre para evitar enumeración de correos
        return back()->with('status', trans(\Illuminate\Support\Facades\Password::RESET_LINK_SENT));
    }
}
