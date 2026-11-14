<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\PerfilCompartido;
use App\Models\Subtarea;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubtareaFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function crearEscenario(string $rolCompartido = null): array
    {
        $owner = User::factory()->create();
        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil Pruebas',
        ]);

        $tarea = Tarea::create([
            'idPerfil' => $perfil->idPerfil,
            'tituloTarea' => 'Tarea de prueba',
            'descripcionTarea' => 'Descripción detallada de la tarea',
            'fechaInicioTarea' => now()->toDateString(),
            'fechaLimite' => now()->addDays(3)->toDateString(),
            'estadoTarea' => 'Pendiente',
            'prioridadTarea' => 'Media',
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

    public function test_propietario_puede_crear_subtarea_y_actualiza_esfuerzo(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $response = $this
            ->actingAs($owner)
            ->postJson(route('subtareas.store', $tarea->idTarea), [
                'titulo' => 'Subtarea número 1',
                'estimacionEsfuerzo' => 2,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('subtarea.titulo', 'Subtarea número 1');
        $response->assertJsonPath('subtarea.estimacionEsfuerzo', 2);
        $response->assertJsonPath('subtarea.completada', false);

        $this->assertDatabaseHas('subtareas', [
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Subtarea número 1',
            'estimacionEsfuerzo' => 2,
        ]);

        $tarea->refresh();
        $this->assertEquals(2, $tarea->estimacionEsfuerzo);
        $this->assertEquals('Pendiente', $tarea->estadoTarea);
    }

    public function test_usuario_lector_no_puede_crear_subtarea(): void
    {
        [$owner, $perfil, $tarea, $lector] = $this->crearEscenario('Lector');

        $response = $this
            ->actingAs($lector)
            ->postJson(route('subtareas.store', $tarea->idTarea), [
                'titulo' => 'Subtarea no permitida',
                'estimacionEsfuerzo' => 1,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('subtareas', [
            'titulo' => 'Subtarea no permitida',
        ]);
    }

    public function test_toggle_subtarea_actualiza_estado_a_en_progreso_y_completado(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $sub1 = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso 1',
            'completada' => false,
            'estimacionEsfuerzo' => 1,
            'orden' => 1,
        ]);
        $sub2 = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso 2',
            'completada' => false,
            'estimacionEsfuerzo' => 2,
            'orden' => 2,
        ]);
        $tarea->recalcularProgreso();

        // 1. Marcar paso 1 completado -> Tarea pasa a 'En Progreso'
        $resp1 = $this->actingAs($owner)->patchJson(route('subtareas.toggle', $sub1->idSubtarea));
        $resp1->assertOk();
        $resp1->assertJsonPath('subtarea.completada', true);

        $tarea->refresh();
        $this->assertEquals('En Progreso', $tarea->estadoTarea);
        $this->assertNull($tarea->fechaFinTarea);

        // 2. Marcar paso 2 completado -> Tarea pasa a 'Completado' y asigna fechaFinTarea
        $resp2 = $this->actingAs($owner)->patchJson(route('subtareas.toggle', $sub2->idSubtarea));
        $resp2->assertOk();
        $resp2->assertJsonPath('subtarea.completada', true);

        $tarea->refresh();
        $this->assertEquals('Completado', $tarea->estadoTarea);
        $this->assertNotNull($tarea->fechaFinTarea);

        // 3. Desmarcar paso 1 -> Tarea vuelve a 'En Progreso'
        $resp3 = $this->actingAs($owner)->patchJson(route('subtareas.toggle', $sub1->idSubtarea));
        $resp3->assertOk();
        $resp3->assertJsonPath('subtarea.completada', false);

        $tarea->refresh();
        $this->assertEquals('En Progreso', $tarea->estadoTarea);
        $this->assertNull($tarea->fechaFinTarea);
    }

    public function test_actualizar_subtarea_modifica_titulo_y_recalcula_esfuerzo(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $sub = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso inicial',
            'completada' => false,
            'estimacionEsfuerzo' => 1,
            'orden' => 1,
        ]);
        $tarea->recalcularProgreso();

        $response = $this->actingAs($owner)->putJson(route('subtareas.update', $sub->idSubtarea), [
            'titulo' => 'Paso editado y corregido',
            'estimacionEsfuerzo' => 3,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('subtareas', [
            'idSubtarea' => $sub->idSubtarea,
            'titulo' => 'Paso editado y corregido',
            'estimacionEsfuerzo' => 3,
        ]);

        $tarea->refresh();
        $this->assertEquals(3, $tarea->estimacionEsfuerzo);
    }

    public function test_eliminar_subtarea_recalcula_esfuerzo_y_estado(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $sub1 = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso 1',
            'completada' => true,
            'estimacionEsfuerzo' => 2,
            'orden' => 1,
        ]);
        $sub2 = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso 2',
            'completada' => false,
            'estimacionEsfuerzo' => 1,
            'orden' => 2,
        ]);
        $tarea->recalcularProgreso();
        $this->assertEquals('En Progreso', $tarea->fresh()->estadoTarea);

        // Eliminamos la subtarea pendiente (sub2). Al quedar solo sub1 completada, la tarea pasa a 'Completado'
        $response = $this->actingAs($owner)->deleteJson(route('subtareas.destroy', $sub2->idSubtarea));
        $response->assertOk();

        $this->assertDatabaseMissing('subtareas', ['idSubtarea' => $sub2->idSubtarea]);

        $tarea->refresh();
        $this->assertEquals(2, $tarea->estimacionEsfuerzo);
        $this->assertEquals('Completado', $tarea->estadoTarea);
    }

    public function test_completar_tarea_directamente_marca_todas_las_subtareas_como_completadas(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $sub1 = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso A',
            'completada' => false,
            'estimacionEsfuerzo' => 1,
        ]);
        $sub2 = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso B',
            'completada' => false,
            'estimacionEsfuerzo' => 2,
        ]);

        $response = $this->actingAs($owner)->patch(route('tareas.completar', $tarea->idTarea));
        $response->assertRedirect();

        $this->assertTrue($sub1->fresh()->completada);
        $this->assertTrue($sub2->fresh()->completada);
    }

    public function test_eliminar_tarea_elimina_subtareas_en_cascada(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $sub = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Paso C',
            'completada' => false,
            'estimacionEsfuerzo' => 1,
        ]);

        $tarea->delete();

        $this->assertDatabaseMissing('subtareas', ['idSubtarea' => $sub->idSubtarea]);
    }

    public function test_guardar_lote_en_modo_reemplazar_elimina_subtareas_previas_y_actualiza_esfuerzo(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        // Subtarea previa que debe ser reemplazada
        $subPrevia = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Subtarea vieja',
            'estimacionEsfuerzo' => 1,
            'orden' => 1,
        ]);

        $response = $this->actingAs($owner)->postJson(route('subtareas.guardar-lote', $tarea->idTarea), [
            'modo' => 'reemplazar',
            'subtareas' => [
                ['titulo' => 'Paso nuevo 1', 'estimacionPomodoros' => 2],
                ['titulo' => 'Paso nuevo 2', 'estimacionPomodoros' => 3],
            ]
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'tarea.subtareas');

        $this->assertDatabaseMissing('subtareas', ['idSubtarea' => $subPrevia->idSubtarea]);
        $this->assertDatabaseHas('subtareas', ['idTarea' => $tarea->idTarea, 'titulo' => 'Paso nuevo 1', 'orden' => 1]);
        $this->assertDatabaseHas('subtareas', ['idTarea' => $tarea->idTarea, 'titulo' => 'Paso nuevo 2', 'orden' => 2]);

        $tarea->refresh();
        $this->assertEquals(5, $tarea->estimacionEsfuerzo); // 2 + 3
    }

    public function test_guardar_lote_en_modo_anexar_agrega_al_final_con_orden_consecutivo(): void
    {
        [$owner, $perfil, $tarea] = $this->crearEscenario();

        $subPrevia = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => 'Subtarea previa que se conserva',
            'estimacionEsfuerzo' => 2,
            'orden' => 1,
        ]);

        $response = $this->actingAs($owner)->postJson(route('subtareas.guardar-lote', $tarea->idTarea), [
            'modo' => 'anexar',
            'subtareas' => [
                ['titulo' => 'Paso anexado 1', 'estimacionPomodoros' => 1],
            ]
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'tarea.subtareas');

        $this->assertDatabaseHas('subtareas', ['idSubtarea' => $subPrevia->idSubtarea]);
        $this->assertDatabaseHas('subtareas', ['idTarea' => $tarea->idTarea, 'titulo' => 'Paso anexado 1', 'orden' => 2]);

        $tarea->refresh();
        $this->assertEquals(3, $tarea->estimacionEsfuerzo); // 2 + 1
    }

    public function test_guardar_lote_rechaza_usuario_lector(): void
    {
        [$owner, $perfil, $tarea, $lector] = $this->crearEscenario('Lector');

        $response = $this->actingAs($lector)->postJson(route('subtareas.guardar-lote', $tarea->idTarea), [
            'modo' => 'reemplazar',
            'subtareas' => [
                ['titulo' => 'Paso no autorizado', 'estimacionPomodoros' => 1],
            ]
        ]);

        $response->assertStatus(403);
    }
}
