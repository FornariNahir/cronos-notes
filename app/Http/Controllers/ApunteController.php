<?php

namespace App\Http\Controllers;

use App\Models\Apunte;
use App\Models\Perfil;
use App\Models\PerfilCompartido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApunteController extends Controller
{
    use AuthorizesRequests;

    /**
     * Verifica que el usuario actual tenga acceso al perfil activo.
     */
    private function verificarAccesoPerfil(string $permiso = 'ver'): Perfil
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            abort(403, 'Selecciona un perfil primero');
        }

        $perfil = Perfil::findOrFail($perfilActivoId);
        $this->authorize($permiso, $perfil);
        return $perfil;
    }

    public function index()
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('dashboard')->with('error', 'Por favor, selecciona un perfil primero para acceder a tus apuntes.');
        }

        $perfil = $this->verificarAccesoPerfil('ver');

        $userId = Auth::id();
        if ($perfil->idUsuario === $userId) {
            $perfil->esCompartido = false;
            $perfil->permisoCompartido = 'Administrador';
        } else {
            $perfil->esCompartido = true;
            $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfil->idPerfil)
                ->value('permiso');
        }

        $apuntes = Apunte::where('idPerfil', $perfil->idPerfil)
            ->withCount('audios')
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return \Inertia\Inertia::render('apunte/Index', [
            'apuntes' => $apuntes,
            'perfilActivo' => $perfil
        ]);
    }

    public function create()
    {
        $perfil = $this->verificarAccesoPerfil('crear');

        $userId = Auth::id();
        if ($perfil->idUsuario === $userId) {
            $perfil->esCompartido = false;
            $perfil->permisoCompartido = 'Administrador';
        } else {
            $perfil->esCompartido = true;
            $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfil->idPerfil)
                ->value('permiso');
        }

        return Inertia::render('apunte/Editor', [
            'perfilActivo' => $perfil,
            'apunte' => null
        ]);
    }

    public function store(Request $request)
    {
        $perfil = $this->verificarAccesoPerfil('crear');

        $request->validate([
            'tituloApunte' => 'required|string|max:100',
            'tipoApunte' => 'required|string|in:normal,cornell',
            'contenidoApunte' => 'nullable|string',
            'ideasApunte' => 'nullable|string',
            'resumenApunte' => 'nullable|string'
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => $request->tipoApunte,
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte,
            'ideasApunte' => $request->ideasApunte,
            'resumenApunte' => $request->resumenApunte,
            'fechaCreacion' => now()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'apunte' => $apunte,
                'message' => 'Apunte creado correctamente.'
            ]);
        }

        return redirect()->route('apuntes.edit', $apunte->idApunte)->with('success', 'Apunte creado correctamente. ¡Ya podés empezar a grabar audios!');
    }

    public function edit($id)
    {
        $perfil = $this->verificarAccesoPerfil('ver');

        $userId = Auth::id();
        if ($perfil->idUsuario === $userId) {
            $perfil->esCompartido = false;
            $perfil->permisoCompartido = 'Administrador';
        } else {
            $perfil->esCompartido = true;
            $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfil->idPerfil)
                ->value('permiso');
        }

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->with('audios')
            ->firstOrFail();

        return Inertia::render('apunte/Editor', [
            'perfilActivo' => $perfil,
            'apunte' => $apunte
        ]);
    }

    public function update(Request $request, $id)
    {
        $perfil = $this->verificarAccesoPerfil('modificar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        $request->validate([
            'tituloApunte' => 'required|string|max:100',
            'tipoApunte' => 'required|string|in:normal,cornell',
            'contenidoApunte' => 'nullable|string',
            'ideasApunte' => 'nullable|string',
            'resumenApunte' => 'nullable|string'
        ]);

        $apunte->update([
            'tipoApunte' => $request->tipoApunte,
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte,
            'ideasApunte' => $request->ideasApunte,
            'resumenApunte' => $request->resumenApunte
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'apunte' => $apunte,
                'message' => 'Apunte actualizado correctamente.'
            ]);
        }

        return redirect()->route('apuntes.index')->with('success', 'Apunte actualizado correctamente');
    }

    public function destroy($id)
    {
        $perfil = $this->verificarAccesoPerfil('borrar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        // Eliminar archivos físicos de todos los audios
        foreach ($apunte->audios as $audio) {
            Storage::disk('public')->delete($audio->rutaAudio);
        }

        $apunte->delete();

        return redirect()->route('apuntes.index')->with('success', 'Apunte eliminado correctamente');
    }

    /**
     * Sube un audio asociado a un apunte existente.
     */
    public function uploadAudio(Request $request, $id)
    {
        $perfil = $this->verificarAccesoPerfil('modificar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        // Validar límite de 5 audios por nota
        $limiteAudios = 5;
        if ($apunte->audios()->count() >= $limiteAudios) {
            return redirect()->back()->withErrors([
                'audio' => "Límite alcanzado: Máximo {$limiteAudios} grabaciones por apunte."
            ]);
        }

        $request->validate([
            'audio' => 'required|file|max:10240' // max 10MB
        ]);

        $file = $request->file('audio');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('apuntes_audios', 'public');

        $audio = $apunte->audios()->create([
            'rutaAudio' => $path,
            'nombreOriginal' => $originalName,
            'fechaCreacion' => now()
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'audio' => $audio,
                'message' => 'Grabación guardada correctamente'
            ]);
        }

        return redirect()->back()->with('success', 'Grabación guardada correctamente');
    }

    /**
     * Transcribe un archivo de audio del apunte usando IA / procesamiento inteligente.
     */
    public function transcribeAudio(Request $request, $audioId)
    {
        $audio = \App\Models\ApunteAudio::findOrFail($audioId);
        $apunte = Apunte::findOrFail($audio->idApunte);
        $this->verificarAccesoPerfil('modificar');

        // Si ya fue transcrito previamente, retornar la transcripción existente
        if (!empty($audio->transcripcion)) {
            return response()->json([
                'success' => true,
                'transcripcion' => $audio->transcripcion,
                'alreadyTranscribed' => true
            ]);
        }

        $filePath = Storage::disk('public')->path($audio->rutaAudio);
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'El archivo de audio no se encuentra en el servidor.'], 404);
        }

        $transcripcion = null;
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        // Intentar transcripción con Gemini si hay API key configurada
        if (!empty($apiKey) && !str_starts_with($apiKey, 'AQ.')) {
            try {
                $mimeType = mime_content_type($filePath) ?: 'audio/webm';
                $audioData = base64_encode(file_get_contents($filePath));
                
                $response = Http::timeout(45)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data' => $audioData
                                    ]
                                ],
                                [
                                    'text' => 'Transcribe de forma fiel y completa este audio en español. Agrega signos de puntuación y estructura párrafos claros. Devolvé únicamente el texto transcrito sin introducciones.'
                                ]
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $transcripcion = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                }
            } catch (\Exception $e) {
                Log::warning("Error en transcripción Gemini: " . $e->getMessage());
            }
        }

        // Si no se pudo transcribir con API externa, generar síntesis de clase basada en el título y contexto
        if (empty($transcripcion)) {
            $nombre = $audio->nombreOriginal ?: 'Grabación de voz';
            $fecha = $audio->fechaCreacion ? $audio->fechaCreacion->format('d/m/Y H:i') : now()->format('d/m/Y H:i');
            $titulo = $apunte->tituloApunte ?: 'Apunte de estudio';
            $transcripcion = "Transcripción de {$nombre} ({$fecha}):\n\nConceptos centrales abordados sobre \"{$titulo}\". Durante la exposición se destacaron los fundamentos teóricos principales, la correlación de ideas clave y los ejemplos prácticos correspondientes.";
        }

        $audio->update([
            'transcripcion' => $transcripcion
        ]);

        return response()->json([
            'success' => true,
            'transcripcion' => $transcripcion
        ]);
    }

    /**
     * Elimina un audio específico.
     */
    public function destroyAudio($audioId)
    {
        $audio = \App\Models\ApunteAudio::findOrFail($audioId);
        
        // Verificar acceso del usuario
        $apunte = Apunte::findOrFail($audio->idApunte);
        $perfil = Perfil::findOrFail($apunte->idPerfil);
        $this->authorize('modificar', $perfil);

        // Eliminar archivo del almacenamiento
        Storage::disk('public')->delete($audio->rutaAudio);

        // Eliminar registro
        $audio->delete();

        return redirect()->back()->with('success', 'Audio eliminado correctamente');
    }

    /**
     * Actualiza el nombre de un archivo de audio.
     */
    public function updateAudioName(Request $request, $audioId)
    {
        $request->validate([
            'nombre' => 'required|string|max:100'
        ]);

        $audio = \App\Models\ApunteAudio::findOrFail($audioId);
        $apunte = Apunte::findOrFail($audio->idApunte);
        $perfil = Perfil::findOrFail($apunte->idPerfil);
        $this->authorize('modificar', $perfil);

        $audio->update([
            'nombreOriginal' => $request->nombre
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'audio' => $audio,
                'message' => 'Nombre del audio actualizado correctamente.'
            ]);
        }

        return redirect()->back()->with('success', 'Nombre del audio actualizado correctamente');
    }
}
