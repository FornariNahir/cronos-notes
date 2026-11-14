<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\PerfilCompartido;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskBreakdownServiceTest extends TestCase
{
    use RefreshDatabase;

    private function crearEscenario(string $rolCompartido = null, ?string $descripcion = 'Descripción detallada para la IA'): array
    {
        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil Pruebas IA',
        ]);

        $tarea = Tarea::create([
            'idPerfil' => $perfil->idPerfil,
            'tituloTarea' => 'Preparar informe final de pasantías',
            'descripcionTarea' => $descripcion,
            'fechaInicioTarea' => now()->toDateString(),
            'fechaLimite' => now()->addDays(5)->toDateString(),
            'estadoTarea' => 'Pendiente',
            'prioridadTarea' => 'Alta',
            'estimacionEsfuerzo' => 0,
        ]);

        $collaborator = null;
        if ($rolCompartido) {
            $collaborator = User::factory()->create();
            PerfilCompartido::create([
                'idUsuario' => $collaborator->idUsuario,
                'idPerfil' => $perfil->idPerfil,
                'permiso' => $rolCompartido,
            ]);
        }

        return [$owner, $perfil, $tarea, $collaborator];
    }

    public function test_desglosar_con_ia_exitoso_retorna_subtareas_estructuradas(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'subtareas' => [
                                            ['titulo' => 'Recopilar métricas de desempeño', 'estimacionPomodoros' => 2],
                                            ['titulo' => 'Redactar introducción y objetivos', 'estimacionPomodoros' => 3],
                                            ['titulo' => 'Estructurar conclusiones y anexos', 'estimacionPomodoros' => 2],
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('tareas.desglosar-ia', $tarea->idTarea));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(3, 'subtareas');
        $response->assertJsonPath('subtareas.0.titulo', 'Recopilar métricas de desempeño');
        $response->assertJsonPath('subtareas.0.estimacionPomodoros', 2);
    }

    public function test_desglosar_con_ia_rechaza_tarea_sin_descripcion(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario(null, '');

        $response = $this
            ->actingAs($owner)
            ->postJson(route('tareas.desglosar-ia', $tarea->idTarea));

        $response->assertStatus(422);
        $response->assertJsonStructure(['error']);
        $this->assertStringContainsString('descripción', $response->json('error'));
    }

    public function test_desglosar_con_ia_rechaza_usuario_con_rol_lector(): void
    {
        [$owner, $perfil, $tarea, $lector] = $this->crearEscenario('Lector');

        $response = $this
            ->actingAs($lector)
            ->postJson(route('tareas.desglosar-ia', $tarea->idTarea));

        $response->assertStatus(403);
    }

    public function test_fallback_de_modelos_cuando_el_primer_modelo_falla(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        Http::fake([
            // Primer modelo falla con 503
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'error' => ['message' => 'Service Unavailable']
            ], 503),
            // Segundo modelo responde con éxito
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'subtareas' => [
                                            ['titulo' => 'Planificar estructura del documento', 'estimacionPomodoros' => 1],
                                            ['titulo' => 'Escribir borrador preliminar', 'estimacionPomodoros' => 4],
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('tareas.desglosar-ia', $tarea->idTarea));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'subtareas');
        $response->assertJsonPath('subtareas.0.titulo', 'Planificar estructura del documento');
    }

    public function test_desglosar_con_ia_retorna_502_cuando_todos_los_modelos_fallan(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/*' => Http::response([
                'error' => ['message' => 'Internal server error']
            ], 500),
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('tareas.desglosar-ia', $tarea->idTarea));

        $response->assertStatus(502);
        $response->assertJsonStructure(['error']);
    }

    public function test_sanitiza_estimaciones_fuera_de_rango(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'subtareas' => [
                                            ['titulo' => 'Paso con esfuerzo exagerado', 'estimacionPomodoros' => 99],
                                            ['titulo' => 'Paso con esfuerzo cero', 'estimacionPomodoros' => 0],
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('tareas.desglosar-ia', $tarea->idTarea));

        $response->assertOk();
        // 99 se acota a 4
        $response->assertJsonPath('subtareas.0.estimacionPomodoros', 4);
        // 0 se acota a 1
        $response->assertJsonPath('subtareas.1.estimacionPomodoros', 1);
    }
}
