<?php

namespace App\Http\Controllers;

use App\Models\Apunte;
use App\Models\ApunteAudio;
use App\Models\Perfil;
use App\Services\AudioTranscriptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AudioTranscriptionController extends Controller
{
    use AuthorizesRequests;

    protected AudioTranscriptionService $transcriptionService;

    public function __construct(AudioTranscriptionService $transcriptionService)
    {
        $this->transcriptionService = $transcriptionService;
    }

    /**
     * Verifica que el usuario tenga acceso y los permisos requeridos sobre el perfil activo.
     */
    private function verificarAccesoPerfil(string $permiso = 'modificar', ?Apunte $apunte = null): Perfil
    {
        $perfilActivoId = session('perfilActivo') ?? $apunte?->idPerfil;
        if (!$perfilActivoId) {
            abort(403, 'Selecciona un perfil primero');
        }

        $perfil = Perfil::findOrFail($perfilActivoId);
        $this->authorize($permiso, $perfil);

        return $perfil;
    }

    /**
     * Procesa la transcripción y resumen de un archivo de audio grabado/subido en un apunte.
     */
    public function transcribe(Request $request, $idApunte, $idAudio): JsonResponse
    {
        @set_time_limit(300);

        $apunte = Apunte::findOrFail($idApunte);
        $perfil = $this->verificarAccesoPerfil('modificar', $apunte);

        $audio = ApunteAudio::where('idApunteAudio', $idAudio)
            ->where('idApunte', $apunte->idApunte)
            ->firstOrFail();

        try {
            $result = $this->transcriptionService->processAudio($audio);

            return response()->json($result, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al procesar la transcripción del audio.',
                'detalle' => $e->getMessage(),
                'estado' => 'fallido',
            ], 503);
        }
    }

    /**
     * Aplica el resumen Cornell generado a las columnas del apunte (reemplazar o anexar).
     */
    public function aplicarCornell(Request $request, $idApunte, $idAudio): JsonResponse
    {
        $request->validate([
            'modo' => 'required|in:reemplazar,anexar',
        ]);

        $apunte = Apunte::findOrFail($idApunte);
        $perfil = $this->verificarAccesoPerfil('modificar', $apunte);

        $audio = ApunteAudio::where('idApunteAudio', $idAudio)
            ->where('idApunte', $apunte->idApunte)
            ->firstOrFail();

        $resumen = $audio->resumen_ia;
        if (empty($resumen) || !is_array($resumen)) {
            return response()->json([
                'error' => 'El audio seleccionado aún no cuenta con un resumen Cornell procesado.',
            ], 422);
        }

        $modo = $request->input('modo');

        // Formatear ideas_clave si viene como array
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

        if ($modo === 'reemplazar') {
            $apunte->ideasApunte = $nuevasIdeas;
            $apunte->contenidoApunte = $nuevasNotas;
            $apunte->resumenApunte = $nuevoResumen;

            // Si el título es genérico o vacío, sugerir el título de la IA
            if (!empty($resumen['titulo_sugerido']) && (empty($apunte->tituloApunte) || $apunte->tituloApunte === 'Sin título')) {
                $apunte->tituloApunte = mb_substr($resumen['titulo_sugerido'], 0, 100);
            }
        } else { // anexar
            $apunte->ideasApunte = !empty(trim($apunte->ideasApunte ?? ''))
                ? trim($apunte->ideasApunte) . "\n\n" . trim($nuevasIdeas)
                : trim($nuevasIdeas);

            $apunte->contenidoApunte = !empty(trim($apunte->contenidoApunte ?? ''))
                ? trim($apunte->contenidoApunte) . "\n\n---\n\n" . trim($nuevasNotas)
                : trim($nuevasNotas);

            $apunte->resumenApunte = !empty(trim($apunte->resumenApunte ?? ''))
                ? trim($apunte->resumenApunte) . "\n\n" . trim($nuevoResumen)
                : trim($nuevoResumen);
        }

        $apunte->tipoApunte = 'cornell';
        $apunte->save();

        return response()->json($apunte->fresh(), 200);
    }
}
