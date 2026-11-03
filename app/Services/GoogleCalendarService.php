<?php

namespace App\Services;

use App\Services\ExternalTokenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected ExternalTokenService $tokenService;

    public function __construct(ExternalTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Obtener los eventos de todos los calendarios seleccionados del usuario para un rango de fechas.
     */
    public function getEventosRango(int $userId, ?string $inicioIso = null, ?string $finIso = null): array
    {
        $token = $this->tokenService->getValidToken($userId, 'GoogleCalendar');

        if (!$token) {
            return [];
        }

        // Rango por defecto: mes actual con un margen de 7 días antes y después para llenar la cuadrícula
        $timeMin = $inicioIso ?: now()->startOfMonth()->subDays(7)->startOfDay()->toRfc3339String();
        $timeMax = $finIso ?: now()->endOfMonth()->addDays(7)->endOfDay()->toRfc3339String();

        // 1. Obtener lista de calendarios del usuario
        $calendarios = $this->obtenerListaCalendarios($userId, $token);

        $todosLosEventos = [];

        // 2. Consultar eventos de cada calendario
        foreach ($calendarios as $cal) {
            $calId = $cal['id'];
            $calNombre = $cal['summary'] ?? 'Calendario';
            $calColor = $cal['backgroundColor'] ?? '#4285F4';

            $eventos = $this->obtenerEventosDeCalendario($userId, $token, $calId, $timeMin, $timeMax);

            foreach ($eventos as $ev) {
                $inicioRaw = $ev['start']['dateTime'] ?? $ev['start']['date'] ?? null;
                $finRaw = $ev['end']['dateTime'] ?? $ev['end']['date'] ?? null;

                if (!$inicioRaw) {
                    continue;
                }

                $esTodoElDia = !isset($ev['start']['dateTime']);
                $fechaFormato = substr($inicioRaw, 0, 10); // YYYY-MM-DD

                $horaInicio = null;
                $horaFin = null;

                if (!$esTodoElDia) {
                    try {
                        $horaInicio = Carbon::parse($inicioRaw)->format('H:i');
                        $horaFin = $finRaw ? Carbon::parse($finRaw)->format('H:i') : null;
                    } catch (\Exception $e) {
                        // ignore
                    }
                }

                $todosLosEventos[] = [
                    'id' => $ev['id'] ?? uniqid(),
                    'titulo' => $ev['summary'] ?? '(Sin título)',
                    'descripcion' => $ev['description'] ?? '',
                    'fecha' => $fechaFormato,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'es_todo_el_dia' => $esTodoElDia,
                    'origen' => 'GoogleCalendar',
                    'calendario_nombre' => $calNombre,
                    'color' => $calColor,
                    'html_link' => $ev['htmlLink'] ?? null,
                ];
            }
        }

        return $todosLosEventos;
    }

    /**
     * Obtener los calendarios disponibles y activos del usuario.
     */
    public function getCalendariosDisponibles(int $userId): array
    {
        $token = $this->tokenService->getValidToken($userId, 'GoogleCalendar');
        if (!$token) {
            return [];
        }
        return $this->obtenerListaCalendarios($userId, $token);
    }

    /**
     * Obtener lista de calendarios del usuario (personal y compartidos/subscriptos).
     */
    public function obtenerListaCalendarios(int $userId, string &$token): array
    {
        $response = Http::withToken($token)->get('https://www.googleapis.com/calendar/v3/users/me/calendarList');

        // Manejar expiración transparente
        if ($response->status() === 401) {
            $nuevoToken = $this->tokenService->refreshToken($userId, 'GoogleCalendar');
            if ($nuevoToken) {
                $token = $nuevoToken;
                $response = Http::withToken($token)->get('https://www.googleapis.com/calendar/v3/users/me/calendarList');
            }
        }

        if ($response->successful()) {
            $items = $response->json()['items'] ?? [];
            
            // Filtrar únicamente los calendarios activos y visibles
            $filtrados = array_filter($items, function ($c) {
                // Si está explícitamente oculto, omitir
                if ($c['hidden'] ?? false) {
                    return false;
                }
                // Si el usuario desmarcó el calendario explícitamente en la interfaz de Google Calendar
                if (isset($c['selected']) && !$c['selected']) {
                    return false;
                }
                return true;
            });

            return !empty($filtrados) ? array_values($filtrados) : [['id' => 'primary', 'summary' => 'Principal', 'backgroundColor' => '#4285F4']];
        }

        // Si no tiene permisos de calendarList aún (ej: scope previo), fallback a primary
        return [
            ['id' => 'primary', 'summary' => 'Principal', 'backgroundColor' => '#4285F4']
        ];
    }

    /**
     * Consultar eventos de un calendario específico.
     */
    protected function obtenerEventosDeCalendario(int $userId, string &$token, string $calendarId, string $timeMin, string $timeMax): array
    {
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($calendarId) . '/events';

        try {
            $response = Http::withToken($token)->get($url, [
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 100,
            ]);

            if ($response->status() === 401) {
                $nuevoToken = $this->tokenService->refreshToken($userId, 'GoogleCalendar');
                if ($nuevoToken) {
                    $token = $nuevoToken;
                    $response = Http::withToken($token)->get($url, [
                        'timeMin' => $timeMin,
                        'timeMax' => $timeMax,
                        'singleEvents' => 'true',
                        'orderBy' => 'startTime',
                        'maxResults' => 100,
                    ]);
                }
            }

            if ($response->successful()) {
                return $response->json()['items'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("[GoogleCalendarService] Error leyendo calendario {$calendarId}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Crear un evento en el calendario principal de Google Calendar.
     */
    public function crearEvento(int $userId, array $datos): ?string
    {
        $token = $this->tokenService->getValidToken($userId, 'GoogleCalendar');

        if (!$token) {
            return null;
        }

        $payload = [
            'summary' => $datos['titulo'],
            'description' => $datos['descripcion'] ?? '',
            'start' => isset($datos['fecha_hora_inicio']) 
                ? ['dateTime' => Carbon::parse($datos['fecha_hora_inicio'])->toRfc3339String()]
                : ['date' => $datos['fecha']],
            'end' => isset($datos['fecha_hora_fin'])
                ? ['dateTime' => Carbon::parse($datos['fecha_hora_fin'])->toRfc3339String()]
                : ['date' => $datos['fecha']],
        ];

        try {
            $response = Http::withToken($token)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $payload);

            if ($response->status() === 401) {
                $token = $this->tokenService->refreshToken($userId, 'GoogleCalendar');
                if ($token) {
                    $response = Http::withToken($token)
                        ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $payload);
                }
            }

            if ($response->successful()) {
                return $response->json()['id'] ?? null;
            }
        } catch (\Exception $e) {
            Log::error("[GoogleCalendarService] Error creando evento: " . $e->getMessage());
        }

        return null;
    }
}
