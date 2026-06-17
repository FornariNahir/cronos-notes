<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Backend guard: verify that the email is not registered by another user
        if (\App\Models\User::where('email', $request->user()->email)
            ->where('idUsuario', '!=', $request->user()->idUsuario)
            ->exists()) {
            return back()->withErrors(['email' => 'El correo electrónico ya está registrado por otro usuario.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
