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
        $provider = Socialite::buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
            $config
        );

        // Desactivar verificación estricta de SSL en cURL para entornos locales en Windows
        $verifySsl = config('services.google_calendar.guzzle.verify', env('CURL_SSL_VERIFY', !app()->isProduction() ? false : true));
        $provider->setHttpClient(new \GuzzleHttp\Client([
            'verify' => $verifySsl,
        ]));

        return $provider;
    }

    /**
     * Redirigir al usuario al flujo de autorización de Google Calendar con permisos incrementales.
     */
    public function redirect(): RedirectResponse
    {
        $user = Auth::user();
        $params = [
            'access_type' => 'offline', // Crucial para obtener el Refresh Token duradero
            'prompt' => 'consent',       // Garantiza que Google devuelva siempre el tokenNuevo
        ];

        // Si el usuario ya está autenticado, enviamos login_hint para omitir la selección de cuenta
        if ($user && !empty($user->email)) {
            $params['login_hint'] = $user->email;
        }

        return $this->getGoogleCalendarProvider()
            ->scopes([
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/calendar.readonly',
            ])
            ->with($params)
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

            return redirect()->route('calendario')->with('success', '¡Google Calendar vinculado exitosamente!');
        } catch (\Exception $e) {
            Log::error("[GoogleCalendarController] Error en callback: " . $e->getMessage());
            return redirect()->route('calendario')->withErrors([
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
     * Endpoint de diagnóstico (Smoke Test): comprueba la conexión leyendo los calendarios y eventos del usuario (RF-M15).
     */
    public function smokeTest(\App\Services\GoogleCalendarService $calendarService): JsonResponse
    {
        $userId = Auth::id();
        $token = $this->tokenService->getValidToken($userId, 'GoogleCalendar');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Google Calendar no está conectado.',
            ], 404);
        }

        try {
            $calendarios = $calendarService->getCalendariosDisponibles($userId);
            $eventos = $calendarService->getEventosRango($userId);

            $nombresCalendarios = array_map(function ($c) {
                return $c['summary'] ?? 'Calendario';
            }, $calendarios);

            return response()->json([
                'status' => 'success',
                'message' => 'Conexión con Google Calendar API exitosa.',
                'total_eventos_obtenidos' => count($eventos),
                'calendarios_consultados' => $nombresCalendarios,
                'eventos_muestra' => array_slice(array_map(function ($ev) {
                    return [
                        'id' => $ev['id'] ?? null,
                        'resumen' => $ev['titulo'] ?? '(Sin título)',
                        'calendario' => $ev['calendario_nombre'] ?? 'Principal',
                        'fecha' => $ev['fecha'] ?? null,
                        'hora' => $ev['hora_inicio'] ?? 'Todo el día',
                    ];
                }, $eventos), 0, 5),
            ]);
        } catch (\Exception $e) {
            Log::error("[GoogleCalendarController] Error en smokeTest: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar Google Calendar API: ' . $e->getMessage(),
            ], 500);
        }
    }
}
