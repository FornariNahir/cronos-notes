<?php

namespace App\Services;

use App\Models\Tarea;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskBreakdownService
{
    /**
     * Cadena de modelos ordenada por preferencia y resiliencia.
     */
    protected array $modelos = [
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-flash-latest'
    ];

    /**
     * Desglosa una tarea en subtareas accionables utilizando Google Gemini API.
     *
     * @throws \InvalidArgumentException si la tarea no tiene descripción
     * @throws \RuntimeException si la API falla o la clave no está configurada
     */
    public function desglosar(Tarea $tarea): array
    {
        $descripcion = trim($tarea->descripcionTarea ?? '');
        if (empty($descripcion)) {
            throw new \InvalidArgumentException('La tarea debe tener una descripción detallada para poder ser desglosada con Inteligencia Artificial.');
        }

        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            throw new \RuntimeException('La API Key de Gemini no está configurada en el servidor.');
        }

        $prompt = "Desglosá la siguiente tarea en una secuencia lógica de subtareas accionables:\n\n"
            . "Título de la tarea: \"{$tarea->tituloTarea}\"\n"
            . "Descripción: \"{$descripcion}\"\n"
            . ($tarea->fechaLimite ? "Fecha límite: " . $tarea->fechaLimite->format('Y-m-d') . "\n" : "")
            . ($tarea->prioridadTarea ? "Prioridad: " . $tarea->prioridadTarea . "\n" : "")
            . "\nInstrucciones:\n"
            . "1. Generá entre 3 y 7 subtareas secuenciales, claras y concretas para llevar a cabo la tarea.\n"
            . "2. Cada subtarea debe tener un título claro que comience con un verbo en infinitivo (ej: 'Diseñar...', 'Configurar...', 'Redactar...', 'Revisar...').\n"
            . "3. Asigná una estimación de esfuerzo realista en Pomodoros (bloques de 25 minutos) entre 1 y 4 pomodoros para cada subtarea.\n"
            . "4. Respondé estrictamente en formato JSON utilizando el esquema requerido sin añadir texto adicional.";

        $systemInstruction = "Sos un tutor experto en productividad y técnica Pomodoro. "
            . "Tu objetivo es descomponer tareas complejas en pasos pequeños, accionables y motivadores (quick wins) "
            . "para evitar la parálisis por sobrecarga y la procrastinación. Respondé estrictamente en formato JSON.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'subtareas' => [
                    'type' => 'ARRAY',
                    'description' => 'Lista de 3 a 7 subtareas ordenadas lógicamente.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'titulo' => [
                                'type' => 'STRING',
                                'description' => 'Título de la subtarea que inicia con verbo en infinitivo.'
                            ],
                            'estimacionPomodoros' => [
                                'type' => 'INTEGER',
                                'description' => 'Cantidad estimada de bloques pomodoro (entre 1 y 4).'
                            ]
                        ],
                        'required' => ['titulo', 'estimacionPomodoros']
                    ]
                ]
            ],
            'required' => ['subtareas']
        ];

        $ultimoError = '';

        foreach ($this->modelos as $modelo) {
            try {
                $response = Http::timeout(25)->retry(2, 500)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$apiKey}", [
                    'contents' => [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema
                    ]
                ]);

                if ($response->successful()) {
                    $jsonText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                    $resultado = json_decode($jsonText, true);

                    if (isset($resultado['subtareas']) && is_array($resultado['subtareas']) && count($resultado['subtareas']) > 0) {
                        $sanitizadas = [];
                        foreach ($resultado['subtareas'] as $sub) {
                            $titulo = trim($sub['titulo'] ?? '');
                            if (!empty($titulo)) {
                                $sanitizadas[] = [
                                    'titulo' => mb_substr($titulo, 0, 150),
                                    'estimacionPomodoros' => max(1, min(4, (int) ($sub['estimacionPomodoros'] ?? 1))),
                                ];
                            }
                        }

                        if (!empty($sanitizadas)) {
                            return $sanitizadas;
                        }
                    }
                }

                $ultimoError = "Modelo {$modelo} falló con código " . $response->status() . ": " . $response->body();
            } catch (\Exception $e) {
                $ultimoError = "Excepción en modelo {$modelo}: " . $e->getMessage();
            }
        }

        Log::error("Fallo general en TaskBreakdownService: " . $ultimoError);
        throw new \RuntimeException('No fue posible desglosar la tarea con Inteligencia Artificial. Los servicios no están disponibles en este momento.');
    }
}
