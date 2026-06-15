<?php

namespace Tests\Feature;

use App\Models\Perfil;
use App\Models\PerfilCompartido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilCompartidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_propietario_puede_revocar_acceso_de_usuario_compartido(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();

        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil del Owner',
        ]);

        PerfilCompartido::create([
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
            'permiso' => 'Lector',
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('perfil-compartido.revocar', [
                'idPerfil' => $perfil->idPerfil,
                'idUsuario' => $invited->idUsuario,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Acceso revocado correctamente.');

        $this->assertDatabaseMissing('PerfilCompartido', [
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
        ]);
    }

    public function test_usuario_compartido_puede_abandonar_perfil_compartido(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();

        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil del Owner',
        ]);

        PerfilCompartido::create([
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
            'permiso' => 'Lector',
        ]);

        $response = $this
            ->actingAs($invited)
            ->delete(route('perfil-compartido.revocar', [
                'idPerfil' => $perfil->idPerfil,
                'idUsuario' => $invited->idUsuario,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Saliste del perfil correctamente.');

        $this->assertDatabaseMissing('PerfilCompartido', [
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
        ]);
    }

    public function test_usuario_no_autorizado_no_puede_revocar_acceso(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();
        $unauthorized = User::factory()->create();

        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil del Owner',
        ]);

        PerfilCompartido::create([
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
            'permiso' => 'Lector',
        ]);

        $response = $this
            ->actingAs($unauthorized)
            ->delete(route('perfil-compartido.revocar', [
                'idPerfil' => $perfil->idPerfil,
                'idUsuario' => $invited->idUsuario,
            ]));

        $response->assertStatus(403);

        $this->assertDatabaseHas('PerfilCompartido', [
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
        ]);
    }

    public function test_al_abandonar_perfil_activo_se_limpia_la_sesion(): void
    {
        $owner = User::factory()->create();
        $invited = User::factory()->create();

        $perfil = Perfil::create([
            'idUsuario' => $owner->idUsuario,
            'tituloPerfil' => 'Perfil del Owner',
        ]);

        PerfilCompartido::create([
            'idUsuario' => $invited->idUsuario,
            'idPerfil' => $perfil->idPerfil,
            'permiso' => 'Lector',
        ]);

        $response = $this
            ->actingAs($invited)
            ->withSession(['perfilActivo' => $perfil->idPerfil])
            ->delete(route('perfil-compartido.revocar', [
                'idPerfil' => $perfil->idPerfil,
                'idUsuario' => $invited->idUsuario,
            ]));

        $response->assertRedirect();
        $response->assertSessionMissing('perfilActivo');
    }
}
