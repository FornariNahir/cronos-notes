<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\EstadisticaService;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('gestion-perfil')
                ->with('error', 'Selecciona un perfil primero');
        }

        // Retornamos todas las tareas del perfil para que la vista las filtre dinámicamente
        $tareas = Tarea::where('idPerfil', $perfilActivoId)
            ->orderBy('fechaLimite', 'asc')
            ->get();

        $perfilActivo = Perfil::find($perfilActivoId);

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
        $perfilActivoId = session('perfilActivo');
        $tarea = Tarea::where('idTarea', $id)
            ->where('idPerfil', $perfilActivoId)
            ->firstOrFail();

        return response()->json($tarea);
    }

    public function update(Request $request, $id)
    {
        $tarea = Tarea::where('idTarea', $id)
            ->whereHas('perfil', function ($query) {
                $query->where('idUsuario', Auth::user()->idUsuario);
            })
            ->firstOrFail();

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
        $tarea = Tarea::where('idTarea', $id)
            ->whereHas('perfil', function ($query) {
                $query->where('idUsuario', Auth::user()->idUsuario);
            })
            ->firstOrFail();

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
        $tarea = Tarea::where('idTarea', $id)
            ->whereHas('perfil', function ($query) {
                $query->where('idUsuario', Auth::user()->idUsuario);
            })
            ->firstOrFail();

        $tarea->delete();

        return redirect()->back()->with('success', 'Tarea eliminada correctamente');
    }

    public function priorizarConIA(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return response()->json(['error' => 'Selecciona un perfil primero'], 400);
        }

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

        $modelos = ['gemini-2.5-flash', 'gemini-1.5-flash'];
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
            return response()->json(['error' => 'Error al comunicarse con la API de IA. Detalle: ' . $ultimoError], 502);
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

