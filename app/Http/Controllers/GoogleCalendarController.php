<?php

namespace App\Http\Controllers;

use App\Models\IntegracionExterna;
use App\Services\ExternalTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleCalendarController extends Controller
{
    protected ExternalTokenService $tokenService;

    public function __construct(ExternalTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Obtener el proveedor de Socialite configurado específicamente con las credenciales de Google Calendar.
     */
    protected function getGoogleCalendarProvider()
    {
        $config = config('services.google_calendar');
        return Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            $config
        );
    }

    /**
     * Redirigir al usuario al flujo de autorización de Google Calendar con permisos incrementales.
     */
    public function redirect(): RedirectResponse
    {
        return $this->getGoogleCalendarProvider()
            ->scopes([
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/calendar.events.readonly',
            ])
            ->with([
                'access_type' => 'offline', // Crucial para obtener el Refresh Token duradero
                'prompt' => 'consent',       // Garantiza que Google devuelva siempre el tokenNuevo
            ])
            ->redirect();
    }

    /**
     * Procesar el callback de retorno desde Google OAuth.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = $this->getGoogleCalendarProvider()->user();

            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login')->withErrors([
                    'error' => 'Debes iniciar sesión para vincular tu calendario de Google.',
                ]);
            }

            // Almacenar o actualizar las credenciales en IntegracionExterna
            IntegracionExterna::updateOrCreate(
                [
                    'idUsuario' => $user->idUsuario,
                    'plataforma' => 'GoogleCalendar',
                ],
                [
                    'identificadorExterno' => $googleUser->getId(),
                    'tokenAcceso' => $googleUser->token,
                    // Si Google no envió refreshToken en esta reautorización, conservamos el previo
                    'tokenNuevo' => $googleUser->refreshToken ?? IntegracionExterna::where('idUsuario', $user->idUsuario)
                        ->where('plataforma', 'GoogleCalendar')
                        ->value('tokenNuevo'),
                ]
            );

            return redirect()->route('perfil-usuario')->with('success', '¡Google Calendar vinculado exitosamente!');
        } catch (\Exception $e) {
            Log::error("[GoogleCalendarController] Error en callback: " . $e->getMessage());
            return redirect()->route('perfil-usuario')->withErrors([
                'error' => 'Hubo un inconveniente al vincular Google Calendar: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Desvincular Google Calendar.
     */
    public function disconnect(): RedirectResponse
    {
        $this->tokenService->disconnect(Auth::id(), 'GoogleCalendar');
        return redirect()->route('perfil-usuario')->with('success', 'Google Calendar desvinculado correctamente.');
    }

    /**
     * Endpoint de diagnóstico (Smoke Test): comprueba la conexión leyendo los próximos eventos.
     */
    public function smokeTest(): JsonResponse
    {
        $userId = Auth::id();
        $token = $this->tokenService->getValidToken($userId, 'GoogleCalendar');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Google Calendar no está conectado.',
            ], 404);
        }

        // Llamada a la API de Google Calendar
        $response = Http::withToken($token)
            ->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'maxResults' => 5,
                'timeMin' => now()->toRfc3339String(),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
            ]);

        // Si expiró (401), intentamos refrescar una vez
        if ($response->status() === 401) {
            $nuevoToken = $this->tokenService->refreshToken($userId, 'GoogleCalendar');
            if ($nuevoToken) {
                $response = Http::withToken($nuevoToken)
                    ->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                        'maxResults' => 5,
                        'timeMin' => now()->toRfc3339String(),
                        'singleEvents' => 'true',
                        'orderBy' => 'startTime',
                    ]);
            }
        }

        if ($response->successful()) {
            $eventos = $response->json()['items'] ?? [];
            return response()->json([
                'status' => 'success',
                'message' => 'Conexión con Google Calendar API exitosa.',
                'total_eventos_obtenidos' => count($eventos),
                'eventos_muestra' => array_map(function ($ev) {
                    return [
                        'id' => $ev['id'] ?? null,
                        'resumen' => $ev['summary'] ?? '(Sin título)',
                        'inicio' => $ev['start']['dateTime'] ?? $ev['start']['date'] ?? null,
                        'fin' => $ev['end']['dateTime'] ?? $ev['end']['date'] ?? null,
                    ];
                }, $eventos),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Error al consultar Google Calendar API.',
            'error_details' => $response->json(),
        ], $response->status());
    }
}
