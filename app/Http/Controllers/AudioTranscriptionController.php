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
    private function verificarAccesoPerfil(string $permiso = 'modificar'): Perfil
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            abort(403, 'Selecciona un perfil primero');
        }

        $perfil = Perfil::findOrFail($perfilActivoId);
        $this->authorize($permiso, $perfil);

        return $perfil;
    }

    /**
     * Procesa la transcripción de un archivo de audio grabado/subido en un apunte.
     */
    public function transcribe(Request $request, $idApunte, $idAudio): JsonResponse
    {
        $perfil = $this->verificarAccesoPerfil('modificar');

        $apunte = Apunte::where('idApunte', $idApunte)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

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
}
