<?php

namespace Tests\Feature;

use App\Models\Apunte;
use App\Models\ApunteAudio;
use App\Models\Perfil;
use App\Models\PerfilCompartido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AudioTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_no_autenticado_no_puede_transcribir(): void
    {
        $response = $this->postJson('/apuntes/1/audios/1/transcribir');
        $response->assertUnauthorized();
    }

    public function test_usuario_con_rol_lector_recibe_403_al_transcribir(): void
    {
        $owner = User::factory()->create();
        $reader = User::factory()->create();

        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil de Estudio',
        ]);

        PerfilCompartido::create([
            'idUsuario' => $reader->idUsuario,
            'idPerfil' => $perfil->idPerfil,
            'permiso' => 'Lector',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Apunte de Redes',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => 'apuntes_audios/clase.mp3',
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($reader)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertForbidden();
    }

    public function test_propietario_puede_transcribir_y_generar_resumen_cornell_exitosamente(): void
    {
        Storage::fake('public');
        $fakeAudioPath = 'apuntes_audios/clase_redes.mp3';
        Storage::disk('public')->put($fakeAudioPath, 'dummy-binary-audio-content');

        $geminiCornellPayload = [
            'titulo_sugerido' => 'Introducción a Redes Neuronales Artificiales',
            'ideas_clave' => [
                '¿Qué es un perceptrón?',
                'Función de activación Sigmoide vs ReLU',
            ],
            'notas' => "### Conceptos Principales\n- Un perceptrón modela una neurona biológica.\n- Las funciones de activación introducen no-linealidad.",
            'resumen' => 'La clase profundizó en los fundamentos de las redes neuronales artificiales, abarcando el perceptrón simple y las diferentes funciones de activación requeridas para el aprendizaje profundo.',
        ];

        Http::fake([
            '*/asr*' => Http::response([
                'text' => 'En la clase de hoy vamos a ver redes neuronales artificiales y funciones de activación.',
            ], 200),
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode($geminiCornellPayload),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Mi Perfil de Ingeniería',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Clase de IA',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => $fakeAudioPath,
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertOk();
        $response->assertJson([
            'idApunteAudio' => $audio->idApunteAudio,
            'idApunte' => $apunte->idApunte,
            'estado' => 'completado',
            'transcripcion' => 'En la clase de hoy vamos a ver redes neuronales artificiales y funciones de activación.',
            'resumen_cornell' => [
                'titulo_sugerido' => 'Introducción a Redes Neuronales Artificiales',
                'ideas_clave' => [
                    '¿Qué es un perceptrón?',
                    'Función de activación Sigmoide vs ReLU',
                ],
                'notas' => "### Conceptos Principales\n- Un perceptrón modela una neurona biológica.\n- Las funciones de activación introducen no-linealidad.",
                'resumen' => 'La clase profundizó en los fundamentos de las redes neuronales artificiales, abarcando el perceptrón simple y las diferentes funciones de activación requeridas para el aprendizaje profundo.',
            ],
            'motor_stt' => 'whisper_local',
        ]);

        $audio->refresh();
        $this->assertEquals('completado', $audio->estado);
        $this->assertEquals('En la clase de hoy vamos a ver redes neuronales artificiales y funciones de activación.', $audio->transcripcion);
        $this->assertIsArray($audio->resumen_ia);
        $this->assertEquals('Introducción a Redes Neuronales Artificiales', $audio->resumen_ia['titulo_sugerido']);
        $this->assertCount(2, $audio->resumen_ia['ideas_clave']);
    }

    public function test_fallback_automatico_a_gemini_multimodal_cuando_whisper_falla(): void
    {
        Storage::fake('public');
        $fakeAudioPath = 'apuntes_audios/clase_fallback.mp3';
        Storage::disk('public')->put($fakeAudioPath, 'dummy-audio-fallback');

        $geminiCornellPayload = [
            'titulo_sugerido' => 'Conceptos Resilientes',
            'ideas_clave' => ['Resiliencia', 'Alta Disponibilidad'],
            'notas' => '### Apuntes\n- El fallback funcionó exitosamente.',
            'resumen' => 'Resumen generado vía fallback a Gemini.',
        ];

        // Simulamos fallo en Whisper (500) y éxito en Gemini tanto para STT como para Cornell
        Http::fake([
            '*/asr*' => Http::response('Internal Server Error en Whisper', 500),
            '*generativelanguage.googleapis.com*' => Http::sequence()
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => 'Transcripción obtenida gracias a Gemini Multimodal Audio.'],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => json_encode($geminiCornellPayload)],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Mi Perfil',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Clase Fallback',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => $fakeAudioPath,
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertOk();
        $response->assertJson([
            'estado' => 'completado',
            'transcripcion' => 'Transcripción obtenida gracias a Gemini Multimodal Audio.',
            'motor_stt' => 'gemini_multimodal',
        ]);

        $audio->refresh();
        $this->assertEquals('completado', $audio->estado);
        $this->assertEquals('Transcripción obtenida gracias a Gemini Multimodal Audio.', $audio->transcripcion);
    }

    public function test_falla_con_503_si_whisper_falla_y_fallback_esta_deshabilitado(): void
    {
        Storage::fake('public');
        $fakeAudioPath = 'apuntes_audios/clase_no_fallback.mp3';
        Storage::disk('public')->put($fakeAudioPath, 'dummy-audio');

        config(['services.whisper.fallback_to_gemini' => false]);

        Http::fake([
            '*/asr*' => Http::response('Whisper Down', 500),
        ]);

        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Mi Perfil',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Clase No Fallback',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => $fakeAudioPath,
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertStatus(503);
        $response->assertJson([
            'estado' => 'fallido',
        ]);

        $audio->refresh();
        $this->assertEquals('fallido', $audio->estado);
        $this->assertStringContainsString('fallback deshabilitado', $audio->error_mensaje);
    }

    public function test_falla_con_503_si_whisper_y_gemini_ambos_fallan(): void
    {
        Storage::fake('public');
        $fakeAudioPath = 'apuntes_audios/clase_total_failure.mp3';
        Storage::disk('public')->put($fakeAudioPath, 'dummy-audio');

        Http::fake([
            '*/asr*' => Http::response('Whisper Down', 500),
            '*generativelanguage.googleapis.com*' => Http::response('Gemini Down', 500),
        ]);

        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Mi Perfil',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Clase Total Failure',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => $fakeAudioPath,
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertStatus(503);
        $response->assertJson([
            'estado' => 'fallido',
        ]);

        $audio->refresh();
        $this->assertEquals('fallido', $audio->estado);
    }

    public function test_transcripcion_maneja_error_si_archivo_no_existe(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Mi Perfil',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Clase Inexistente',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => 'apuntes_audios/no_existe.mp3',
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertStatus(503);
        $response->assertJson([
            'estado' => 'fallido',
        ]);

        $this->assertDatabaseHas('ApunteAudio', [
            'idApunteAudio' => $audio->idApunteAudio,
            'estado' => 'fallido',
        ]);
    }

    public function test_transcripcion_maneja_error_si_gemini_retorna_esquema_invalido(): void
    {
        Storage::fake('public');
        $fakeAudioPath = 'apuntes_audios/clase_invalida.mp3';
        Storage::disk('public')->put($fakeAudioPath, 'dummy-audio');

        Http::fake([
            '*/asr*' => Http::response(['text' => 'Texto válido pero IA responderá esquema inválido.'], 200),
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode(['formato_incorrecto' => true])],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Mi Perfil',
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => 'cornell',
            'tituloApunte' => 'Clase Prueba',
            'fechaCreacion' => now(),
        ]);

        $audio = ApunteAudio::create([
            'idApunte' => $apunte->idApunte,
            'rutaAudio' => $fakeAudioPath,
            'fechaCreacion' => now(),
        ]);

        $response = $this
            ->actingAs($owner)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->postJson("/apuntes/{$apunte->idApunte}/audios/{$audio->idApunteAudio}/transcribir");

        $response->assertStatus(503);
        $response->assertJson([
            'estado' => 'fallido',
        ]);

        $audio->refresh();
        $this->assertEquals('fallido', $audio->estado);
        $this->assertNotNull($audio->error_mensaje);
    }
}
