<?php

namespace App\Services;

use App\Models\IntegracionExterna;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalTokenService
{
    /**
     * Obtener un token de acceso válido para el usuario y la plataforma.
     * Si el token no está disponible o la cuenta no está conectada, retorna null.
     */
    public function getValidToken(int $userId, string $plataforma): ?string
    {
        $integracion = IntegracionExterna::where('idUsuario', $userId)
            ->where('plataforma', $plataforma)
            ->first();

        if (!$integracion || empty($integracion->tokenAcceso)) {
            return null;
        }

        return $integracion->tokenAcceso;
    }

    /**
     * Comprueba si el usuario tiene una integración activa para la plataforma.
     */
    public function isConnected(int $userId, string $plataforma): bool
    {
        return IntegracionExterna::where('idUsuario', $userId)
            ->where('plataforma', $plataforma)
            ->whereNotNull('tokenAcceso')
            ->exists();
    }

    /**
     * Elimina la integración externa (Desconectar cuenta).
     */
    public function disconnect(int $userId, string $plataforma): bool
    {
        $deleted = IntegracionExterna::where('idUsuario', $userId)
            ->where('plataforma', $plataforma)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Renovar el token de acceso utilizando el Refresh Token ('tokenNuevo').
     */
    public function refreshToken(int $userId, string $plataforma): ?string
    {
        $integracion = IntegracionExterna::where('idUsuario', $userId)
            ->where('plataforma', $plataforma)
            ->first();

        if (!$integracion || empty($integracion->tokenNuevo)) {
            Log::warning("[ExternalTokenService] No hay refresh token disponible para usuario {$userId} en {$plataforma}");
            return null;
        }

        if ($plataforma === 'GoogleCalendar') {
            return $this->refreshGoogleToken($integracion);
        }

        if ($plataforma === 'Spotify') {
            return $this->refreshSpotifyToken($integracion);
        }

        return null;
    }

    /**
     * Refrescar token de Google Calendar.
     */
    protected function refreshGoogleToken(IntegracionExterna $integracion): ?string
    {
        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $integracion->tokenNuevo,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $nuevoToken = $data['access_token'];

                $integracion->tokenAcceso = $nuevoToken;
                $integracion->save();

                Log::info("[ExternalTokenService] Token de Google Calendar refrescado exitosamente para usuario {$integracion->idUsuario}");
                return $nuevoToken;
            }

            Log::error("[ExternalTokenService] Error refrescando token de Google: " . $response->body());
        } catch (\Exception $e) {
            Log::error("[ExternalTokenService] Excepción refrescando token de Google: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Refrescar token de Spotify.
     */
    protected function refreshSpotifyToken(IntegracionExterna $integracion): ?string
    {
        $clientId = config('services.spotify.client_id');
        $clientSecret = config('services.spotify.client_secret');

        try {
            $response = Http::asForm()->withBasicAuth($clientId, $clientSecret)
                ->post('https://accounts.spotify.com/api/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $integracion->tokenNuevo,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $nuevoToken = $data['access_token'];

                $integracion->tokenAcceso = $nuevoToken;
                if (!empty($data['refresh_token'])) {
                    $integracion->tokenNuevo = $data['refresh_token'];
                }
                $integracion->save();

                Log::info("[ExternalTokenService] Token de Spotify refrescado exitosamente para usuario {$integracion->idUsuario}");
                return $nuevoToken;
            }

            Log::error("[ExternalTokenService] Error refrescando token de Spotify: " . $response->body());
        } catch (\Exception $e) {
            Log::error("[ExternalTokenService] Excepción refrescando token de Spotify: " . $e->getMessage());
        }

        return null;
    }
}
