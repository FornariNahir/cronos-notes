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

class SpotifyController extends Controller
{
    protected ExternalTokenService $tokenService;

    public function __construct(ExternalTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Redirigir al usuario al flujo de autorización OAuth 2.0 de Spotify.
     */
    public function redirect(): RedirectResponse
    {
        $clientId = config('services.spotify.client_id');
        $redirectUri = config('services.spotify.redirect', 'http://127.0.0.1:8000/auth/spotify/callback');

        $scopes = implode(' ', [
            'streaming',
            'user-read-playback-state',
            'user-modify-playback-state',
            'playlist-read-private',
            'user-read-email',
            'user-read-private',
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'show_dialog' => 'true', // Permite reautorizar o cambiar de cuenta
        ]);

        return redirect('https://accounts.spotify.com/authorize?' . $query);
    }

    /**
     * Procesar el callback de retorno desde Spotify.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            Log::warning("[SpotifyController] Autorización cancelada o con error: " . $request->query('error'));
            return redirect()->route('perfil-usuario')->withErrors([
                'error' => 'Autorización de Spotify cancelada o denegada.',
            ]);
        }

        $code = $request->query('code');
        $clientId = config('services.spotify.client_id');
        $clientSecret = config('services.spotify.client_secret');
        $redirectUri = config('services.spotify.redirect', 'http://127.0.0.1:8000/auth/spotify/callback');

        try {
            // Intercambiar código por tokens
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error("[SpotifyController] Error al obtener token: " . $response->body());
                return redirect()->route('perfil-usuario')->withErrors([
                    'error' => 'No se pudo intercambiar el token de Spotify.',
                ]);
            }

            $data = $response->json();
            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'] ?? null;

            // Consultar datos del perfil de Spotify para obtener el ID y el tipo de plan ('premium' o 'free')
            $meResponse = Http::withToken($accessToken)->get('https://api.spotify.com/v1/me');
            $spotifyProfile = $meResponse->successful() ? $meResponse->json() : [];
            $spotifyId = $spotifyProfile['id'] ?? null;
            $productPlan = strtolower($spotifyProfile['product'] ?? 'free'); // 'premium' o 'free'

            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login');
            }

            // Almacenar en IntegracionExterna
            IntegracionExterna::updateOrCreate(
                [
                    'idUsuario' => $user->idUsuario,
                    'plataforma' => 'Spotify',
                ],
                [
                    'identificadorExterno' => $spotifyId,
                    'tokenAcceso' => $accessToken,
                    'tokenNuevo' => $refreshToken ?? IntegracionExterna::where('idUsuario', $user->idUsuario)
                        ->where('plataforma', 'Spotify')
                        ->value('tokenNuevo'),
                ]
            );

            $mensajePlan = $productPlan === 'premium'
                ? '¡Spotify conectado exitosamente con cuenta Premium!'
                : '¡Spotify conectado exitosamente! (Cuenta gratuita: se habilitará acceso a playlists por Deeplink).';

            return redirect()->route('perfil-usuario')->with('success', $mensajePlan);
        } catch (\Exception $e) {
            Log::error("[SpotifyController] Excepción en callback: " . $e->getMessage());
            return redirect()->route('perfil-usuario')->withErrors([
                'error' => 'Error inesperado al conectar Spotify: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Desvincular Spotify.
     */
    public function disconnect(): RedirectResponse
    {
        $this->tokenService->disconnect(Auth::id(), 'Spotify');
        return redirect()->route('perfil-usuario')->with('success', 'Spotify desvinculado correctamente.');
    }

    /**
     * Obtener el catálogo curado de los 3 estilos musicales para concentración.
     */
    public function getCuratedPlaylists(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'playlists' => [
                [
                    'id' => 'lofi',
                    'titulo' => 'Lofi Girl - beats to relax/study',
                    'descripcion' => 'La emblemática selección instrumental de lofi hip hop y chillhop para estudiar con calma.',
                    'uri' => 'spotify:playlist:0vvXsWCC9xrXsKd4FyS8kM',
                    'url_web' => 'https://open.spotify.com/playlist/0vvXsWCC9xrXsKd4FyS8kM',
                    'icono' => 'bi-headphones',
                    'color_tag' => '#9c27b0',
                ],
                [
                    'id' => 'jazz',
                    'titulo' => 'Café & Jazz Instrumental',
                    'descripcion' => 'Jazz acústico suave de cafetería (piano, contrabajo y vientos), 100% libre de voces.',
                    'uri' => 'spotify:playlist:37i9dQZF1DX8Ig2zLEAhzO',
                    'url_web' => 'https://open.spotify.com/playlist/37i9dQZF1DX8Ig2zLEAhzO',
                    'icono' => 'bi-cup-hot',
                    'color_tag' => '#ff9800',
                ],
                [
                    'id' => 'piano',
                    'titulo' => 'Piano & Clásica Minimalista',
                    'descripcion' => 'Melodías de piano acústico instrumental contemporáneo para máxima concentración y código.',
                    'uri' => 'spotify:playlist:37i9dQZF1DX4sWSpwq3LiO',
                    'url_web' => 'https://open.spotify.com/playlist/37i9dQZF1DX4sWSpwq3LiO',
                    'icono' => 'bi-music-note-beamed',
                    'color_tag' => '#2196f3',
                ],
            ]
        ]);
    }

    /**
     * Endpoint de diagnóstico (Smoke Test): comprueba la conexión consultando el perfil y plan en Spotify.
     */
    public function smokeTest(): JsonResponse
    {
        $userId = Auth::id();
        $token = $this->tokenService->getValidToken($userId, 'Spotify');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Spotify no está conectado.',
            ], 404);
        }

        $response = Http::withToken($token)->get('https://api.spotify.com/v1/me');

        // Si expiró (401), intentar refrescar
        if ($response->status() === 401) {
            $nuevoToken = $this->tokenService->refreshToken($userId, 'Spotify');
            if ($nuevoToken) {
                $response = Http::withToken($nuevoToken)->get('https://api.spotify.com/v1/me');
            }
        }

        if ($response->successful()) {
            $me = $response->json();
            return response()->json([
                'status' => 'success',
                'message' => 'Conexión con Spotify Web API exitosa.',
                'usuario_spotify' => $me['display_name'] ?? '(Sin nombre público)',
                'email' => $me['email'] ?? null,
                'pais' => $me['country'] ?? null,
                'plan' => $me['product'] ?? 'desconocido',
                'es_premium' => ($me['product'] ?? '') === 'premium',
            ]);
        }

        $errorBody = $response->body();
        $message = 'Error al consultar Spotify Web API.';
        if (str_contains($errorBody, 'Active premium subscription required for the owner of the app')) {
            $message = 'La cuenta de Spotify que creó la aplicación en el Spotify Developer Dashboard no posee una suscripción activa a Spotify Premium. Spotify exige obligatoriamente que el propietario de la App en el Dashboard sea Premium para habilitar llamadas a su Web API.';
        }

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'error_details' => $response->json() ?? $errorBody,
        ], $response->status());
    }
}
