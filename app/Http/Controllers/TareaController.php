<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\Perfil;
use App\Models\Apunte;
use App\Models\PerfilCompartido;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\EstadisticaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TareaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Verifica que el usuario actual tenga acceso al perfil (propietario o compartido).
     */
    private function verificarAccesoPerfil($idPerfil, string $permiso = 'ver'): Perfil
    {
        $perfil = Perfil::findOrFail($idPerfil);
        $this->authorize($permiso, $perfil);
        return $perfil;
    }

    /**
     * Buscador contextual en tiempo real para el Navbar:
     * - Si contexto === 'perfiles': busca únicamente en Perfiles del usuario.
     * - Si contexto === 'tareas': busca únicamente en Tareas del perfil activo actual.
     */
    public function busquedaGlobal(Request $request)
    {
        $userId = Auth::id();
        $q = trim((string) $request->query('q', ''));
        $contexto = trim((string) $request->query('contexto', ''));

        if (!$userId || mb_strlen($q) < 1) {
            return response()->json(['resultados' => []]);
        }

        $like = '%' . $q . '%';

        // Caso 1: Búsqueda en la sección "Mis perfiles" -> solo perfiles
        if ($contexto === 'perfiles') {
            $perfiles = Perfil::where(function ($query) use ($userId) {
                    $query->where('idUsuario', $userId)
                        ->orWhereHas('usuariosCompartidos', function ($sub) use ($userId) {
                            $sub->where('PerfilCompartido.idUsuario', $userId);
                        });
                })
                ->where(function ($query) use ($like) {
                    $query->where('tituloPerfil', 'like', $like)
                        ->orWhere('descripcionPerfil', 'like', $like);
                })
                ->limit(8)
                ->get()
                ->map(function ($p) {
                    return [
                        'uid' => 'perfil_' . $p->idPerfil,
                        'tipo' => 'perfil',
                        'id' => $p->idPerfil,
                        'idPerfil' => $p->idPerfil,
                        'titulo' => $p->tituloPerfil,
                        'subtitulo' => $p->descripcionPerfil ?: 'Perfil de trabajo',
                        'badge' => 'Perfil',
                    ];
                })
                ->values();

            return response()->json([
                'resultados' => $perfiles,
            ]);
        }

        // Caso 2: Búsqueda en la sección "Mis tareas" -> solo tareas del perfil activo
        if ($contexto === 'tareas') {
            $perfilActivoId = $request->query('idPerfil') ?: session('perfilActivo');
            if (!$perfilActivoId) {
                return response()->json(['resultados' => []]);
            }

            $tareas = Tarea::with('perfil')
                ->where('idPerfil', $perfilActivoId)
                ->whereHas('perfil', function ($query) use ($userId) {
                    $query->where('idUsuario', $userId)
                        ->orWhereHas('usuariosCompartidos', function ($sub) use ($userId) {
                            $sub->where('PerfilCompartido.idUsuario', $userId);
                        });
                })
                ->where(function ($query) use ($like) {
                    $query->where('tituloTarea', 'like', $like)
                        ->orWhere('descripcionTarea', 'like', $like);
                })
                ->orderBy('fechaLimite', 'asc')
                ->limit(10)
                ->get()
                ->map(function ($t) {
                    return [
                        'uid' => 'tarea_' . $t->idTarea,
                        'tipo' => 'tarea',
                        'id' => $t->idTarea,
                        'idPerfil' => $t->idPerfil,
                        'titulo' => $t->tituloTarea,
                        'subtitulo' => ($t->estadoTarea ?? 'Pendiente') . ' · Prioridad ' . ($t->prioridadTarea ?? 'Media'),
                        'badge' => 'Tarea',
                    ];
                })
                ->values();

            return response()->json([
                'resultados' => $tareas,
            ]);
        }

        return response()->json([
            'resultados' => [],
        ]);
    }

    /**
     * Devuelve en formato JSON las tareas pendientes próximas a vencer (en las siguientes 24h)
     * del usuario autenticado para alimentar la campana de notificaciones del Navbar.
     */
    public function proximasAVencer(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['notificaciones' => [], 'total' => 0]);
        }

        $hoy = Carbon::now()->toDateString();
        $manana = Carbon::now()->addDay()->toDateString();

        $tareas = Tarea::with('perfil')
            ->where('estadoTarea', '!=', 'Completado')
            ->whereNotNull('fechaLimite')
            ->whereBetween('fechaLimite', [$hoy, $manana])
            ->whereHas('perfil', function ($query) use ($userId) {
                $query->where('idUsuario', $userId)
                    ->orWhereHas('usuariosCompartidos', function ($q) use ($userId) {
                        $q->where('PerfilCompartido.idUsuario', $userId);
                    });
            })
            ->orderBy('fechaLimite', 'asc')
            ->get()
            ->map(function ($tarea) use ($hoy) {
                $fechaStr = $tarea->fechaLimite instanceof Carbon
                    ? $tarea->fechaLimite->format('Y-m-d')
                    : Carbon::parse($tarea->fechaLimite)->format('Y-m-d');

                $vencimiento = Carbon::parse($fechaStr)->endOfDay();
                $minutosRestantes = Carbon::now()->diffInMinutes($vencimiento, false);
                $horasRestantes = max(1, min(24, (int) ceil($minutosRestantes / 60)));

                return [
                    'idTarea' => $tarea->idTarea,
                    'idPerfil' => $tarea->idPerfil,
                    'tituloTarea' => $tarea->tituloTarea,
                    'perfil' => $tarea->perfil?->tituloPerfil ?? 'General',
                    'prioridadTarea' => $tarea->prioridadTarea ?? 'Media',
                    'estadoTarea' => $tarea->estadoTarea,
                    'fechaLimite' => $fechaStr,
                    'venceHoy' => $fechaStr === $hoy,
                    'horas_restantes' => $horasRestantes,
                ];
            })
            ->values();

        return response()->json([
            'notificaciones' => $tareas,
            'total' => $tareas->count(),
        ]);
    }

    public function index(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('gestion-perfil')
                ->with('error', 'Selecciona un perfil primero');
        }

        // Verificar que el usuario tenga acceso al perfil activo
        $this->verificarAccesoPerfil($perfilActivoId, 'ver');

        // Retornamos todas las tareas del perfil para que la vista las filtre dinámicamente
        $tareas = Tarea::where('idPerfil', $perfilActivoId)
            ->orderBy('fechaLimite', 'asc')
            ->get();

        $perfilActivo = Perfil::find($perfilActivoId);

        $userId = Auth::id();
        if ($perfilActivo->idUsuario === $userId) {
            $perfilActivo->esCompartido = false;
            $perfilActivo->permisoCompartido = 'Administrador';
        } else {
            $perfilActivo->esCompartido = true;
            $perfilActivo->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfilActivoId)
                ->value('permiso');
        }

        return Inertia::render('GestionTareas', [
            'tareas' => $tareas,
            'perfilActivo' => $perfilActivo
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'idPerfil' => 'nullable|exists:Perfil,idPerfil',
            'tituloTarea' => 'required|string|max:45',
            'descripcionTarea' => 'nullable|string|max:200',
            'fechaLimite' => 'required|date|after_or_equal:today',
            'prioridadTarea' => 'required|in:Baja,Media,Alta',
            'estimacionEsfuerzo' => 'nullable|integer|min:1'
        ]);

        $perfilActivoId = $request->idPerfil ?? session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->back()->with('error', 'No hay perfil activo');
        }

        // Verificar permiso de creación
        $this->verificarAccesoPerfil($perfilActivoId, 'crear');

        Tarea::create([
            'idPerfil' => $perfilActivoId,
            'tituloTarea' => $request->tituloTarea,
            'descripcionTarea' => $request->descripcionTarea,
            'fechaInicioTarea' => now()->format('Y-m-d'),
            'fechaLimite' => $request->fechaLimite,
            'prioridadTarea' => $request->prioridadTarea,
            'estadoTarea' => 'Pendiente',
            'estimacionEsfuerzo' => $request->estimacionEsfuerzo
        ]);

        return redirect()->back()->with('success', 'Tarea creada correctamente');
    }

    public function show($id)
    {
        $tarea = Tarea::findOrFail($id);

        // Verificar acceso de lectura al perfil de la tarea
        $this->verificarAccesoPerfil($tarea->idPerfil, 'ver');

        return response()->json($tarea);
    }

    public function update(Request $request, $id)
    {
        $tarea = Tarea::findOrFail($id);

        // Verificar permiso de modificación sobre el perfil
        $this->verificarAccesoPerfil($tarea->idPerfil, 'modificar');

        $request->validate([
            'tituloTarea' => 'required|string|max:45',
            'descripcionTarea' => 'nullable|string|max:200',
            'fechaLimite' => 'required|date|after_or_equal:' . $tarea->fechaInicioTarea->format('Y-m-d'),
            'prioridadTarea' => 'required|in:Baja,Media,Alta',
            'estadoTarea' => 'required|in:Pendiente,En Progreso,Completado',
            'estimacionEsfuerzo' => 'nullable|integer|min:1'
        ]);

        if ($tarea->estadoTarea === 'Completado' && $request->estadoTarea !== 'Completado') {
            return redirect()->back()->with('error', 'No se puede cambiar el estado de una tarea ya completada');
        }

        $data = [
            'tituloTarea' => $request->tituloTarea,
            'descripcionTarea' => $request->descripcionTarea,
            'fechaLimite' => $request->fechaLimite,
            'prioridadTarea' => $request->prioridadTarea,
            'estadoTarea' => $request->estadoTarea,
            'estimacionEsfuerzo' => $request->estimacionEsfuerzo
        ];

        if ($request->estadoTarea === 'Completado') {
            $data['fechaFinTarea'] = now()->format('Y-m-d');
            
            $estadisticaService = new EstadisticaService();
            $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);
        }

        $tarea->update($data);

        return redirect()->back()->with('success', 'Tarea modificada correctamente');
    }

    public function completar($id)
    {
        $tarea = Tarea::findOrFail($id);

        // Verificar permiso de modificación
        $this->verificarAccesoPerfil($tarea->idPerfil, 'modificar');

        $tarea->update([
            'estadoTarea' => 'Completado',
            'fechaFinTarea' => now()->format('Y-m-d')
        ]);

        $estadisticaService = new EstadisticaService();
        $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);

        return redirect()->back()->with('success', '¡Tarea completada!');
    }

    public function destroy($id)
    {
        $tarea = Tarea::findOrFail($id);

        // Verificar permiso de borrado (solo Administrador o propietario)
        $this->verificarAccesoPerfil($tarea->idPerfil, 'borrar');

        $tarea->delete();

        return redirect()->back()->with('success', 'Tarea eliminada correctamente');
    }

    public function priorizarConIA(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return response()->json(['error' => 'Selecciona un perfil primero'], 400);
        }

        // Verificar acceso de lectura al perfil
        $this->verificarAccesoPerfil($perfilActivoId, 'ver');

        // Obtener las tareas pendientes de este perfil
        $tareas = Tarea::where('idPerfil', $perfilActivoId)
            ->where('estadoTarea', '!=', 'Completado')
            ->get();

        if ($tareas->isEmpty()) {
            return response()->json([
                'tareas' => [],
                'explicacionGeneral' => 'No tenés tareas pendientes para priorizar.'
            ]);
        }

        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'La API Key de Gemini no está configurada en el servidor.'], 500);
        }

        // Preparar las tareas para el payload (solo enviamos campos necesarios)
        $tareasPayload = $tareas->map(function ($tarea) {
            return [
                'idTarea' => $tarea->idTarea,
                'tituloTarea' => $tarea->tituloTarea,
                'descripcionTarea' => $tarea->descripcionTarea,
                'fechaLimite' => $tarea->fechaLimite ? $tarea->fechaLimite->format('Y-m-d') : 'Sin fecha límite',
                'prioridadTarea' => $tarea->prioridadTarea,
                'estimacionEsfuerzo' => $tarea->estimacionEsfuerzo ?? 0,
            ];
        })->toArray();

        $modelos = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-2.0-flash-lite'];
        $response = null;
        $ultimoError = '';

        foreach ($modelos as $modelo) {
            try {
                $prompt = "Organizá las siguientes tareas de forma óptima para maximizar la productividad y evitar el agotamiento (burnout). "
                    . "Tomá en cuenta los siguientes factores para cada tarea: \n"
                    . "1. Fecha límite (fechaLimite): Más urgente primero. \n"
                    . "2. Prioridad inicial (prioridadTarea): Alta, Media, Baja. \n"
                    . "3. Estimación de esfuerzo (estimacionEsfuerzo): Representa el esfuerzo estimado en cantidad de sesiones Pomodoro. Priorizá tareas más cortas para lograr victorias rápidas (quick wins) cuando las fechas y prioridades sean similares, o distribuí las tareas de mayor esfuerzo para evitar fatiga. \n\n"
                    . "Fecha de hoy: " . now()->format('Y-m-d') . ". \n\n"
                    . "Devolveme una lista con el orden óptimo de ejecución. Cada elemento de la lista debe contener el idTarea y una explicación breve y motivadora en español rioplatense de por qué está en ese lugar del orden.";

                // Reintentamos hasta 2 veces con 500ms de espera ante errores transitorios (ej. 503)
                $response = Http::retry(2, 500)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$apiKey}", [
                    'contents' => [
                        'parts' => [
                            ['text' => $prompt . "\nTareas a organizar:\n" . json_encode($tareasPayload, JSON_UNESCAPED_UNICODE)]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => "Sos un asistente experto en productividad y gestión del tiempo usando la técnica Pomodoro. Tu único objetivo es ordenar una lista de tareas de forma óptima. Debes responder estrictamente en formato JSON utilizando el esquema proporcionado. No agregues texto por fuera del JSON de respuesta."]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'ordenOptimo' => [
                                    'type' => 'ARRAY',
                                    'items' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'idTarea' => [
                                                'type' => 'INTEGER',
                                                'description' => 'El id de la tarea original.'
                                            ],
                                            'justificacion' => [
                                                'type' => 'STRING',
                                                'description' => 'Breve razón motivadora en español rioplatense (voseo) de por qué tiene esta posición.'
                                            ]
                                        ],
                                        'required' => ['idTarea', 'justificacion']
                                    ]
                                ]
                            ],
                            'required' => ['ordenOptimo']
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    break; // Salimos del bucle si fue exitoso
                }

                $ultimoError = "Modelo {$modelo} falló con código " . $response->status() . ": " . $response->body();
            } catch (\Exception $e) {
                $ultimoError = "Excepción en modelo {$modelo}: " . $e->getMessage();
            }
        }

        if (!$response || !$response->successful()) {
            \Illuminate\Support\Facades\Log::error('Fallo en priorizarConIA: ' . $ultimoError);
            return response()->json(['error' => 'No pudimos priorizar tus tareas en este momento. Los servicios de inteligencia artificial no están disponibles. Por favor, intentá de nuevo en unos minutos.'], 502);
        }

        try {
            $resultadoJson = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $resultado = json_decode($resultadoJson, true);

            if (!isset($resultado['ordenOptimo'])) {
                return response()->json(['error' => 'La IA retornó una respuesta con formato inválido.'], 502);
            }

            $ordenIds = collect($resultado['ordenOptimo'])->pluck('idTarea')->toArray();
            $justificaciones = collect($resultado['ordenOptimo'])->keyBy('idTarea')->map(fn($item) => $item['justificacion']);

            // Ordenar las tareas originales según el orden recomendado por la IA
            $tareasOrdenadas = $tareas->sortBy(function ($tarea) use ($ordenIds) {
                $posicion = array_search($tarea->idTarea, $ordenIds);
                return $posicion === false ? 999999 : $posicion;
            })->values()->map(function ($tarea) use ($justificaciones) {
                $tarea->sugerenciaIA = $justificaciones->get($tarea->idTarea) ?? 'Priorizada en lote';
                return $tarea;
            });

            return response()->json([
                'tareas' => $tareasOrdenadas,
                'explicacionGeneral' => 'Tareas organizadas óptimamente con Inteligencia Artificial considerando fechas límite, prioridades y la estimación de esfuerzo en pomodoros.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Excepción durante el procesamiento de la respuesta de la IA: ' . $e->getMessage()], 500);
        }
    }
}
