<?php

namespace App\Services;

use App\Models\IntegracionExterna;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleMeetService
{
    /**
     * Normaliza un código o URL de Google Meet ingresado manualmente.
     */
    public function normalizarEnlaceMeet(?string $input): ?string
    {
        if (!$input) {
            return null;
        }

        $trimmed = trim($input);
        if ($trimmed === '') {
            return null;
        }

        // Si el usuario ingresó solo el código (ej: abc-defg-hij)
        if (preg_match('/^[a-z]{3}-[a-z]{4}-[a-z]{3}$/i', $trimmed)) {
            return 'https://meet.google.com/' . strtolower($trimmed);
        }

        // Si ingresó meet.google.com/... sin https://
        if (str_starts_with(strtolower($trimmed), 'meet.google.com/')) {
            return 'https://' . $trimmed;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            return $trimmed;
        }

        return null;
    }

    /**
     * Extrae el código de reunión (ej: abc-defg-hij) de una URL de Google Meet.
     */
    public function extraerMeetId(string $url): ?string
    {
        if (preg_match('/meet\.google\.com\/([a-z]{3}-[a-z]{4}-[a-z]{3})/i', $url, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }

    /**
     * Intenta generar una reunión real de Google Meet usando Google Calendar API v3 (conferenceData).
     * Si el usuario no tiene permisos de Calendar o falla, retorna el enlace provisto o https://meet.google.com/new.
     */
    public function crearReunionMeet(int $idUsuario, string $tituloEvento, ?string $enlaceManual = null): array
    {
        $enlaceNormalizado = $this->normalizarEnlaceMeet($enlaceManual);
        if ($enlaceNormalizado && $enlaceNormalizado !== 'https://meet.google.com/new') {
            return [
                'url' => $enlaceNormalizado,
                'meetId' => $this->extraerMeetId($enlaceNormalizado),
                'generadoPorApi' => false,
            ];
        }

        $integracion = IntegracionExterna::where('idUsuario', $idUsuario)
            ->where('plataforma', 'GoogleAuth')
            ->first();

        if ($integracion && !empty($integracion->tokenAcceso)) {
            $resultadoApi = $this->intentarCrearConCalendarApi($integracion, $tituloEvento);
            if ($resultadoApi) {
                return $resultadoApi;
            }
        }

        return [
            'url' => $enlaceNormalizado ?: 'https://meet.google.com/new',
            'meetId' => null,
            'generadoPorApi' => false,
        ];
    }

    protected function intentarCrearConCalendarApi(IntegracionExterna $integracion, string $tituloEvento): ?array
    {
        $token = $integracion->tokenAcceso;
        $inicio = Carbon::now();
        $fin = Carbon::now()->addHours(2);

        $payload = [
            'summary' => 'Cronos Notes: ' . $tituloEvento,
            'description' => 'Sala de Estudio Virtual sincronizada en Cronos Notes.',
            'start' => [
                'dateTime' => $inicio->toRfc3339String(),
            ],
            'end' => [
                'dateTime' => $fin->toRfc3339String(),
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'cronos-' . Str::uuid()->toString(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(8)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', $payload);

            // Si el token expiró (401) y tenemos refresh token, intentamos refrescarlo
            if ($response->status() === 401 && !empty($integracion->tokenNuevo)) {
                $nuevoToken = $this->refrescarTokenGoogle($integracion);
                if ($nuevoToken) {
                    $response = Http::withToken($nuevoToken)
                        ->timeout(8)
                        ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', $payload);
                }
            }

            if ($response->successful()) {
                $data = $response->json();
                $hangoutLink = $data['hangoutLink'] ?? null;
                $conferenceId = $data['conferenceData']['conferenceId'] ?? ($hangoutLink ? $this->extraerMeetId($hangoutLink) : null);

                if ($hangoutLink) {
                    return [
                        'url' => $hangoutLink,
                        'meetId' => $conferenceId,
                        'generadoPorApi' => true,
                    ];
                }
            } else {
                Log::info('GoogleMeetService: No se pudo crear conferencia automática vía Calendar API', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('GoogleMeetService: Excepción al conectar con Google Calendar API: ' . $e->getMessage());
        }

        return null;
    }

    protected function refrescarTokenGoogle(IntegracionExterna $integracion): ?string
    {
        try {
            $response = Http::asForm()->timeout(6)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id', env('GOOGLE_CLIENT_ID')),
                'client_secret' => config('services.google.client_secret', env('GOOGLE_CLIENT_SECRET')),
                'refresh_token' => $integracion->tokenNuevo,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $nuevoAccessToken = $response->json('access_token');
                if ($nuevoAccessToken) {
                    $integracion->update(['tokenAcceso' => $nuevoAccessToken]);
                    return $nuevoAccessToken;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('GoogleMeetService: Error al refrescar token OAuth: ' . $e->getMessage());
        }

        return null;
    }
}
