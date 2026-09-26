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
            'audio' => 'required|file|mimes:mp3,wav,ogg,m4a,aac,webm,flac|max:25600' // max 25MB
        ], [
            'audio.required' => 'El archivo de audio es obligatorio.',
            'audio.file' => 'El archivo subido no es válido.',
            'audio.uploaded' => 'El archivo no se pudo subir. Puede que supere el tamaño máximo permitido por el servidor.',
            'audio.mimes' => 'El formato debe ser MP3, WAV, M4A, OGG, WEBM o FLAC.',
            'audio.max' => 'El archivo de audio no debe superar los 25 MB.'
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
     * Transcribe un archivo de audio del apunte usando IA / procesamiento inteligente y genera síntesis Cornell.
     */
    public function transcribeAudio(Request $request, $audioId)
    {
        $audio = \App\Models\ApunteAudio::findOrFail($audioId);
        $apunte = Apunte::findOrFail($audio->idApunte);
        $this->verificarAccesoPerfil('modificar');

        // Si ya fue transcrito previamente y tiene resumen Cornell, retornar la transcripción existente
        if (!empty($audio->transcripcion) && !empty($audio->resumen_ia) && !$request->boolean('force')) {
            return response()->json([
                'success' => true,
                'transcripcion' => $audio->transcripcion,
                'resumen_cornell' => $audio->resumen_ia,
                'alreadyTranscribed' => true
            ]);
        }

        $filePath = Storage::disk('public')->path($audio->rutaAudio);
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'El archivo de audio no se encuentra en el servidor.'], 404);
        }

        $transcriptionService = app(\App\Services\AudioTranscriptionService::class);

        try {
            $result = $transcriptionService->processAudio($audio);

            return response()->json([
                'success' => true,
                'transcripcion' => $result['transcripcion'],
                'resumen_cornell' => $result['resumen_cornell'],
                'motor_stt' => $result['motor_stt'] ?? 'gemini_multimodal',
            ]);
        } catch (\Throwable $e) {
            Log::error("Error al procesar transcripción de audio ID {$audioId}: " . $e->getMessage());

            return response()->json([
                'error' => 'No se pudo realizar la transcripción del audio.',
                'detalle' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Aplica el resumen Cornell generado a las columnas del apunte (reemplazar o anexar).
     */
    public function aplicarCornell(Request $request, $id, $audioId)
    {
        $request->validate([
            'modo' => 'required|in:reemplazar,anexar',
            'formato' => 'nullable|in:normal,cornell',
        ]);

        $apunte = Apunte::findOrFail($id);
        $this->verificarAccesoPerfil('modificar');

        $audio = \App\Models\ApunteAudio::where('idApunteAudio', $audioId)
            ->where('idApunte', $apunte->idApunte)
            ->firstOrFail();

        $resumen = $audio->resumen_ia;
        if (empty($resumen) || !is_array($resumen)) {
            return response()->json([
                'error' => 'El audio seleccionado aún no cuenta con un resumen Cornell procesado.',
            ], 422);
        }

        $modo = $request->input('modo', 'anexar');
        $formato = $request->input('formato', $apunte->tipoApunte ?? 'normal');

        // Formatear ideas_clave
        $nuevasIdeas = '';
        if (isset($resumen['ideas_clave'])) {
            if (is_array($resumen['ideas_clave'])) {
                $nuevasIdeas = implode("\n", array_map(fn($item) => "- " . ltrim($item, "- *"), $resumen['ideas_clave']));
            } else {
                $nuevasIdeas = (string) $resumen['ideas_clave'];
            }
        }

        $nuevasNotas = (string) ($resumen['notas'] ?? '');
        $nuevoResumen = (string) ($resumen['resumen'] ?? '');

        // Formato para Modo Normal
        $contenidoNormal = "### 💡 Preguntas Clave\n" . trim($nuevasIdeas) . "\n\n"
            . "---\n\n"
            . "### 📝 Notas\n" . trim($nuevasNotas) . "\n\n"
            . "---\n\n"
            . "### 📌 Resumen\n" . trim($nuevoResumen);

        if ($modo === 'reemplazar') {
            if ($formato === 'normal') {
                $apunte->contenidoApunte = $contenidoNormal;
                $apunte->ideasApunte = $nuevasIdeas;
                $apunte->resumenApunte = $nuevoResumen;
                $apunte->tipoApunte = 'normal';
            } else {
                $apunte->ideasApunte = $nuevasIdeas;
                $apunte->contenidoApunte = $nuevasNotas;
                $apunte->resumenApunte = $nuevoResumen;
                $apunte->tipoApunte = 'cornell';
            }

            if (!empty($resumen['titulo_sugerido']) && (empty($apunte->tituloApunte) || in_array($apunte->tituloApunte, ['Sin título', 'Nuevo Apunte']))) {
                $apunte->tituloApunte = mb_substr($resumen['titulo_sugerido'], 0, 100);
            }
        } else { // anexar
            if ($formato === 'normal') {
                $apunte->contenidoApunte = !empty(trim($apunte->contenidoApunte ?? ''))
                    ? trim($apunte->contenidoApunte) . "\n\n---\n\n" . $contenidoNormal
                    : $contenidoNormal;
                $apunte->ideasApunte = !empty(trim($apunte->ideasApunte ?? ''))
                    ? trim($apunte->ideasApunte) . "\n\n" . trim($nuevasIdeas)
                    : trim($nuevasIdeas);
                $apunte->resumenApunte = !empty(trim($apunte->resumenApunte ?? ''))
                    ? trim($apunte->resumenApunte) . "\n\n" . trim($nuevoResumen)
                    : trim($nuevoResumen);
                $apunte->tipoApunte = 'normal';
            } else {
                $apunte->ideasApunte = !empty(trim($apunte->ideasApunte ?? ''))
                    ? trim($apunte->ideasApunte) . "\n\n" . trim($nuevasIdeas)
                    : trim($nuevasIdeas);

                $apunte->contenidoApunte = !empty(trim($apunte->contenidoApunte ?? ''))
                    ? trim($apunte->contenidoApunte) . "\n\n---\n\n" . trim($nuevasNotas)
                    : trim($nuevasNotas);

                $apunte->resumenApunte = !empty(trim($apunte->resumenApunte ?? ''))
                    ? trim($apunte->resumenApunte) . "\n\n" . trim($nuevoResumen)
                    : trim($nuevoResumen);
                $apunte->tipoApunte = 'cornell';
            }
        }

        $apunte->save();

        return response()->json($apunte->fresh(), 200);
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
