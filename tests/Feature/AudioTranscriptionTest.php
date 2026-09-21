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

    public function test_propietario_puede_transcribir_audio_con_whisper_local_exitosamente(): void
    {
        Storage::fake('public');
        $fakeAudioPath = 'apuntes_audios/test_audio.mp3';
        Storage::disk('public')->put($fakeAudioPath, 'dummy-audio-bytes');

        Http::fake([
            '*/asr*' => Http::response([
                'text' => 'Esta es una transcripción de prueba generada por Whisper.',
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
            'tituloApunte' => 'Clase de Sistemas',
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
            'transcripcion' => 'Esta es una transcripción de prueba generada por Whisper.',
            'motor_stt' => 'whisper_local',
        ]);

        $this->assertDatabaseHas('ApunteAudio', [
            'idApunteAudio' => $audio->idApunteAudio,
            'estado' => 'completado',
            'transcripcion' => 'Esta es una transcripción de prueba generada por Whisper.',
            'error_mensaje' => null,
        ]);
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

        $response->assertStatus(500);
        $response->assertJson([
            'estado' => 'fallido',
        ]);

        $this->assertDatabaseHas('ApunteAudio', [
            'idApunteAudio' => $audio->idApunteAudio,
            'estado' => 'fallido',
        ]);
    }
}
