<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['password']);

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return redirect()->back();
    }

    /**
     * Request an email change: validates new email, verifies password, sends signed confirmation URL.
     */
    public function requestEmailChange(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico es inválido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = $request->user();
        $newEmail = strtolower($request->email);

        // Validate password
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual ingresada es incorrecta.'
            ], 422);
        }

        // Validate new email is different from current
        if ($newEmail === strtolower($user->email)) {
            return response()->json([
                'message' => 'Ingresá una dirección de correo diferente a la actual.'
            ], 422);
        }

        // Validate uniqueness in database
        $exists = \App\Models\User::where('email', $newEmail)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Este correo ya está registrado en la base de datos.'
            ], 422);
        }

        // Generate temporary signed URL
        $confirmUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'profile.confirm-email-change',
            now()->addHours(2),
            ['user' => $user->idUsuario, 'new_email' => $newEmail]
        );

        // Send email notification to the new email address
        \Illuminate\Support\Facades\Notification::route('mail', $newEmail)
            ->notify(new \App\Notifications\ConfirmarCambioCorreoNotification($confirmUrl));

        return response()->json([
            'message' => 'Solicitud procesada con éxito. Se ha enviado un enlace de confirmación a: ' . $newEmail
        ]);
    }

    /**
     * Confirm email change from signed URL.
     */
    public function confirmEmailChange(Request $request): RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'El enlace de confirmación es inválido o ha expirado.');
        }

        $userId = $request->query('user');
        $newEmail = strtolower($request->query('new_email'));

        $user = \App\Models\User::findOrFail($userId);

        // Check if the email is already in use by someone else (just in case)
        $exists = \App\Models\User::where('email', $newEmail)
            ->where('idUsuario', '!=', $user->idUsuario)
            ->exists();

        if ($exists) {
            return redirect()->route('login')->withErrors([
                'email' => 'El correo electrónico ya fue registrado por otro usuario mientras se confirmaba.'
            ]);
        }

        // Update email
        $user->email = $newEmail;
        $user->email_verified_at = now();
        $user->save();

        // If user is currently authenticated, update session
        if (Auth::check() && Auth::user()->idUsuario === $user->idUsuario) {
            // Re-authenticate user session to be safe
            Auth::login($user);
        }

        if (!Auth::check()) {
            return redirect()->route('login')->with('success', '¡Tu correo electrónico ha sido actualizado correctamente! Iniciá sesión con tus nuevos datos.');
        }

        return redirect()->route('perfil-usuario')->with('success', '¡Tu correo electrónico ha sido actualizado correctamente!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
