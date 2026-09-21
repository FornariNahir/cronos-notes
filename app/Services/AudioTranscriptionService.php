<?php

namespace App\Services;

use App\Models\ApunteAudio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AudioTranscriptionService
{
    /**
     * Transcribe un archivo de audio utilizando el microservicio de Whisper Local.
     *
     * @param string $relativeAudioPath Ruta relativa al disco 'public' (ej: 'apuntes_audios/archivo.mp3')
     * @return string Texto completo desgrabado
     * @throws RuntimeException Si el archivo no existe o el microservicio falla
     */
    public function transcribe(string $relativeAudioPath): string
    {
        if (!Storage::disk('public')->exists($relativeAudioPath)) {
            throw new RuntimeException("El archivo de audio no fue encontrado en el disco de almacenamiento: {$relativeAudioPath}");
        }

        $fileContents = Storage::disk('public')->get($relativeAudioPath);
        $whisperUrl = config('services.whisper.url', 'http://localhost:9000/asr');
        $separator = str_contains($whisperUrl, '?') ? '&' : '?';
        $endpoint = "{$whisperUrl}{$separator}task=transcribe&language=es&output=json";

        $response = Http::timeout(180)
            ->attach('audio_file', $fileContents, basename($relativeAudioPath))
            ->post($endpoint);

        if (!$response->successful()) {
            throw new RuntimeException("Error en microservicio Whisper STT [Código {$response->status()}]: " . $response->body());
        }

        $text = $response->json('text');
        if (empty($text)) {
            $text = trim($response->body());
        }

        return trim($text);
    }

    /**
     * Orquesta el procesamiento de transcripción de un ApunteAudio.
     *
     * @param ApunteAudio $audio
     * @return array
     */
    public function processAudio(ApunteAudio $audio): array
    {
        $audio->update([
            'estado' => 'procesando',
            'error_mensaje' => null,
        ]);

        try {
            $transcription = $this->transcribe($audio->rutaAudio);

            $audio->update([
                'transcripcion' => $transcription,
                'estado' => 'completado',
                'error_mensaje' => null,
            ]);

            return [
                'idApunteAudio' => $audio->idApunteAudio,
                'idApunte' => $audio->idApunte,
                'estado' => 'completado',
                'transcripcion' => $transcription,
                'motor_stt' => 'whisper_local',
            ];
        } catch (\Throwable $e) {
            $audio->update([
                'estado' => 'fallido',
                'error_mensaje' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
