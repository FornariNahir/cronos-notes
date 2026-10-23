<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\IntegracionExterna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegracionExternaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_rutas_de_integracion(): void
    {
        $this->get(route('auth.google-calendar.redirect'))->assertRedirect(route('login'));
        $this->get(route('auth.spotify.redirect'))->assertRedirect(route('login'));
        $this->get(route('api.test.google-calendar'))->assertRedirect(route('login'));
        $this->get(route('api.test.spotify'))->assertRedirect(route('login'));
    }

    public function test_perfil_usuario_muestra_estado_de_integraciones(): void
    {
        $response = $this->actingAs($this->user)->get(route('perfil-usuario'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('PerfilUsuario')
            ->has('integraciones')
            ->where('integraciones.google_calendar', false)
            ->where('integraciones.spotify', false)
        );
    }

    public function test_smoke_test_devuelve_404_cuando_no_hay_cuenta_vinculada(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('api.test.google-calendar'))
            ->assertStatus(404)
            ->assertJson(['status' => 'error']);

        $this->actingAs($this->user)
            ->getJson(route('api.test.spotify'))
            ->assertStatus(404)
            ->assertJson(['status' => 'error']);
    }

    public function test_obtener_playlists_curadas_de_spotify(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('api.spotify.playlists-curadas'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'playlists' => [
                    '*' => ['id', 'titulo', 'descripcion', 'uri', 'url_web', 'icono', 'color_tag']
                ]
            ]);

        $this->assertCount(3, $response->json('playlists'));
    }

    public function test_desvincular_integracion_elimina_registro(): void
    {
        IntegracionExterna::create([
            'idUsuario' => $this->user->idUsuario,
            'plataforma' => 'GoogleCalendar',
            'identificadorExterno' => 'google_test_123',
            'tokenAcceso' => 'fake_token',
        ]);

        $this->assertDatabaseHas('IntegracionExterna', [
            'idUsuario' => $this->user->idUsuario,
            'plataforma' => 'GoogleCalendar',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('auth.google-calendar.disconnect'));

        $response->assertRedirect(route('perfil-usuario'));

        $this->assertDatabaseMissing('IntegracionExterna', [
            'idUsuario' => $this->user->idUsuario,
            'plataforma' => 'GoogleCalendar',
        ]);
    }

    public function test_calendario_carga_prop_eventos_google(): void
    {
        $response = $this->actingAs($this->user)->get(route('calendario'));

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => 
            $page->component('Calendario')
                ->has('tareasCargadas')
                ->has('perfilesDisponibles')
                ->has('eventosGoogle')
        );
    }

    public function test_smoke_test_google_calendar_multicalendario_exitoso(): void
    {
        IntegracionExterna::create([
            'idUsuario' => $this->user->idUsuario,
            'plataforma' => 'GoogleCalendar',
            'tokenAcceso' => 'fake-token',
        ]);

        $mockService = $this->mock(\App\Services\GoogleCalendarService::class);
        $mockService->shouldReceive('getCalendariosDisponibles')
            ->once()
            ->andReturn([
                ['id' => 'primary', 'summary' => 'Principal'],
                ['id' => 'sec@group.calendar.google.com', 'summary' => 'Lic. en Sistemas de Información']
            ]);

        $mockService->shouldReceive('getEventosRango')
            ->once()
            ->andReturn([
                [
                    'id' => 'ev1',
                    'titulo' => 'AE3 - PPS',
                    'calendario_nombre' => 'Lic. en Sistemas de Información',
                    'fecha' => '2026-10-21',
                    'hora_inicio' => '15:30',
                ]
            ]);

        $response = $this->actingAs($this->user)->get(route('api.test.google-calendar'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'total_eventos_obtenidos' => 1,
            'calendarios_consultados' => ['Principal', 'Lic. en Sistemas de Información'],
        ]);
    }
}
