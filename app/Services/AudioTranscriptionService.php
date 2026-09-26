<?php

namespace App\Services;

use App\Models\ApunteAudio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    public function transcribeWithWhisper(string $relativeAudioPath): string
    {
        if (!Storage::disk('public')->exists($relativeAudioPath)) {
            throw new RuntimeException("El archivo de audio no fue encontrado en el disco de almacenamiento: {$relativeAudioPath}");
        }

        $fileContents = Storage::disk('public')->get($relativeAudioPath);
        $whisperUrl = config('services.whisper.url', 'http://localhost:9000/asr');
        $separator = str_contains($whisperUrl, '?') ? '&' : '?';
        $endpoint = "{$whisperUrl}{$separator}task=transcribe&language=es&output=json";

        $response = Http::timeout(180)
            ->withOptions($this->getHttpOptions())
            ->attach('audio_file', $fileContents, basename($relativeAudioPath))
            ->post($endpoint);

        if (!$response->successful()) {
            throw new RuntimeException("Error en microservicio Whisper STT [Código {$response->status()}]: " . $response->body());
        }

        $text = $response->json('text');
        if (empty($text)) {
            $text = trim($response->body());
        }

        if (empty(trim($text))) {
            throw new RuntimeException("El servicio Whisper devolvió una transcripción vacía.");
        }

        return trim($text);
    }

    /**
     * Transcribe un archivo de audio utilizando Google Gemini Multimodal Audio (STT en la nube).
     *
     * @param string $relativeAudioPath
     * @return string
     * @throws RuntimeException
     */
    public function transcribeWithGemini(string $relativeAudioPath): string
    {
        if (!Storage::disk('public')->exists($relativeAudioPath)) {
            throw new RuntimeException("El archivo de audio no fue encontrado en el disco de almacenamiento: {$relativeAudioPath}");
        }

        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            throw new RuntimeException("La API Key de Google Gemini no está configurada para el servicio multimodal.");
        }

        $extension = strtolower(pathinfo($relativeAudioPath, PATHINFO_EXTENSION));
        $mimeTypeMap = [
            'mp3' => 'audio/mp3',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/m4a',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'webm' => 'audio/webm',
        ];
        $mimeType = $mimeTypeMap[$extension] ?? 'audio/mp3';
        $base64Audio = base64_encode(Storage::disk('public')->get($relativeAudioPath));

        $prompt = "Transcribí de forma completa, fidedigna y textual el contenido del siguiente audio en español. "
            . "Incluí puntuación adecuada y separación lógica de oraciones. "
            . "Devolvé únicamente el texto transcripto crudo, sin introducciones ni comentarios adicionales.";

        $modelos = ['gemini-2.5-flash', 'gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-flash-latest'];
        $response = null;
        $ultimoError = '';

        foreach ($modelos as $modelo) {
            try {
                $response = Http::timeout(60)
                    ->retry(2, 500)
                    ->withOptions($this->getHttpOptions())
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$apiKey}", [
                    'contents' => [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $base64Audio,
                                ]
                            ],
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    break;
                }

                $ultimoError = "Modelo {$modelo} falló [{$response->status()}]: " . $response->body();
            } catch (\Throwable $e) {
                $ultimoError = "Excepción en modelo {$modelo}: " . $e->getMessage();
            }
        }

        if (!$response || !$response->successful()) {
            throw new RuntimeException("Fallo al transcribir con Gemini Multimodal Audio: " . $ultimoError);
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty(trim($text))) {
            throw new RuntimeException("Gemini Multimodal devolvió una transcripción de audio vacía.");
        }

        return trim($text);
    }

    /**
     * Transcribe un archivo aplicando el patrón Driver y fallback resiliente según la configuración.
     *
     * @param string $relativeAudioPath
     * @return array{text: string, motor: string}
     */
    public function transcribe(string $relativeAudioPath): array
    {
        if (!Storage::disk('public')->exists($relativeAudioPath)) {
            throw new RuntimeException("El archivo de audio no fue encontrado en el disco de almacenamiento: {$relativeAudioPath}");
        }

        $driver = config('services.whisper.driver', 'whisper_local');
        $fallbackEnabled = (bool) config('services.whisper.fallback_to_gemini', true);

        if ($driver === 'gemini') {
            $text = $this->transcribeWithGemini($relativeAudioPath);
            return [
                'text' => $text,
                'motor' => 'gemini_multimodal',
            ];
        }

        // Intento primario con Whisper Local
        try {
            $text = $this->transcribeWithWhisper($relativeAudioPath);
            return [
                'text' => $text,
                'motor' => 'whisper_local',
            ];
        } catch (\Throwable $whisperException) {
            Log::warning("Whisper Local falló: " . $whisperException->getMessage());

            if (!$fallbackEnabled) {
                throw new RuntimeException(
                    "Fallo en transcripción con Whisper Local y el fallback a Gemini está deshabilitado: " . $whisperException->getMessage(),
                    0,
                    $whisperException
                );
            }

            try {
                $text = $this->transcribeWithGemini($relativeAudioPath);
                return [
                    'text' => $text,
                    'motor' => 'gemini_multimodal',
                ];
            } catch (\Throwable $geminiException) {
                throw new RuntimeException(
                    "Error crítico de transcripción: Whisper Local falló ({$whisperException->getMessage()}) y el fallback a Gemini Multimodal también falló ({$geminiException->getMessage()}).",
                    0,
                    $geminiException
                );
            }
        }
    }

    /**
     * Sintetiza y estructura el texto transcrito bajo el Método Cornell utilizando Google Gemini Flash.
     *
     * @param string $transcriptionText
     * @return array{titulo_sugerido: string, ideas_clave: array<string>, notas: string, resumen: string}
     * @throws RuntimeException
     */
    public function summarizeCornell(string $transcriptionText): array
    {
        $cleanText = trim($transcriptionText);
        if (empty($cleanText)) {
            throw new RuntimeException("El texto a sintetizar no puede estar vacío.");
        }

        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            throw new RuntimeException("La API Key de Google Gemini no está configurada en el servidor.");
        }

        $modelos = ['gemini-2.5-flash', 'gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-flash-latest'];
        $response = null;
        $ultimoError = '';

        $prompt = "A partir de la siguiente transcripción de una clase o grabación de audio, estructurá un apunte de estudio siguiendo con rigurosidad pedagógica el Método Cornell.\n\n"
            . "Estructura requerida:\n"
            . "1. titulo_sugerido: Un título académico, claro y representativo del contenido.\n"
            . "2. ideas_clave: Lista ordenada con las preguntas de repaso fundamentales, términos conceptuales y definiciones clave asociadas al tema.\n"
            . "3. notas: Desarrollo completo y ordenado de los temas explicados respetando este estándar de formato: NO uses almohadillas (#) para títulos. Todos los títulos de sección deben ir destacados en negrita (ejemplo: **Título de la Sección**). Los términos clave o destacados van en **Negrita**. Los ítems, ejemplos o puntos secundarios deben redactarse con viñetas '• ' y texto explicativo en cursiva (ejemplo: • *Explicación del punto en cursiva*). Mantén los párrafos y secciones separados por doble salto de línea.\n"
            . "4. resumen: Síntesis conceptual integradora de cierre redactada en un párrafo conciso de entre 3 a 5 oraciones.\n\n"
            . "Transcripción:\n" . $cleanText;

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'titulo_sugerido' => [
                    'type' => 'STRING',
                    'description' => 'Título sugerido para la nota de estudio.'
                ],
                'ideas_clave' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                    'description' => 'Preguntas de repaso, palabras clave y conceptos clave.'
                ],
                'notas' => [
                    'type' => 'STRING',
                    'description' => 'Desarrollo detallado con títulos en **Negrita**, términos en **Negrita** y viñetas con texto en *Cursiva*.'
                ],
                'resumen' => [
                    'type' => 'STRING',
                    'description' => 'Síntesis de cierre integradora en 3 a 5 oraciones.'
                ]
            ],
            'required' => ['titulo_sugerido', 'ideas_clave', 'notas', 'resumen']
        ];

        foreach ($modelos as $modelo) {
            try {
                $response = Http::timeout(60)
                    ->retry(2, 500)
                    ->withOptions($this->getHttpOptions())
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$apiKey}", [
                    'contents' => [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => "Sos un asistente pedagógico universitario de excelencia especializado en el Método Cornell de toma de apuntes. Debes procesar transcripciones orales y transformarlas en apuntes de alto valor académico. Debes responder estrictamente en formato JSON utilizando el esquema estricto proporcionado, sin texto adicional ni bloques fuera del JSON."]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                    ]
                ]);

                if ($response->successful()) {
                    break;
                }

                $ultimoError = "Modelo {$modelo} falló [{$response->status()}]: " . $response->body();
            } catch (\Throwable $e) {
                $ultimoError = "Excepción en modelo {$modelo}: " . $e->getMessage();
            }
        }

        if (!$response || !$response->successful()) {
            throw new RuntimeException("Error al generar resumen Cornell con Gemini: " . $ultimoError);
        }

        $body = $response->json();
        $rawJson = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $data = json_decode($rawJson, true);

        if (!is_array($data) || !isset($data['titulo_sugerido'], $data['ideas_clave'], $data['notas'], $data['resumen'])) {
            throw new RuntimeException("La respuesta de Gemini no contiene el esquema Cornell esperado: " . $rawJson);
        }

        return [
            'titulo_sugerido' => (string) $data['titulo_sugerido'],
            'ideas_clave' => (array) $data['ideas_clave'],
            'notas' => (string) $data['notas'],
            'resumen' => (string) $data['resumen'],
        ];
    }

    /**
     * Orquesta el procesamiento de transcripción y generación de resumen Cornell de un ApunteAudio.
     *
     * @param ApunteAudio $audio
     * @return array
     */
    public function processAudio(ApunteAudio $audio): array
    {
        @set_time_limit(300);

        $audio->update([
            'estado' => 'procesando',
            'error_mensaje' => null,
        ]);

        try {
            $sttResult = $this->transcribe($audio->rutaAudio);
            $transcription = $sttResult['text'];
            $usedMotor = $sttResult['motor'];

            $cornellSummary = $this->summarizeCornell($transcription);

            $audio->update([
                'transcripcion' => $transcription,
                'resumen_ia' => $cornellSummary,
                'estado' => 'completado',
                'error_mensaje' => null,
            ]);

            return [
                'idApunteAudio' => $audio->idApunteAudio,
                'idApunte' => $audio->idApunte,
                'estado' => 'completado',
                'transcripcion' => $transcription,
                'resumen_cornell' => $cornellSummary,
                'motor_stt' => $usedMotor,
            ];
        } catch (\Throwable $e) {
            $audio->update([
                'estado' => 'fallido',
                'error_mensaje' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtiene opciones seguras de HTTP para llamadas salientes en Windows/Local.
     */
    private function getHttpOptions(): array
    {
        $options = [];
        $certPaths = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
            'C:\Users\della\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\cacert.pem',
            'C:\Program Files\Git\mingw64\etc\ssl\certs\ca-bundle.crt',
        ];

        foreach ($certPaths as $path) {
            if (!empty($path) && file_exists($path)) {
                $options['verify'] = $path;
                return $options;
            }
        }

        if (app()->isLocal()) {
            $options['verify'] = false;
        }

        return $options;
    }
}
