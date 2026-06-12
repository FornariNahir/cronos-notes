<?php

namespace App\Http\Controllers;

use App\Models\Apunte;
use App\Models\Perfil;
use App\Models\PerfilCompartido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApunteController extends Controller
{
    use AuthorizesRequests;

    /**
     * Verifica que el usuario actual tenga acceso al perfil activo.
     */
    private function verificarAccesoPerfil(string $permiso = 'ver'): Perfil
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            abort(403, 'Selecciona un perfil primero');
        }

        $perfil = Perfil::findOrFail($perfilActivoId);
        $this->authorize($permiso, $perfil);
        return $perfil;
    }

    public function index()
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('dashboard')->with('error', 'Por favor, selecciona un perfil primero para acceder a tus apuntes.');
        }

        $perfil = $this->verificarAccesoPerfil('ver');

        $userId = Auth::id();
        if ($perfil->idUsuario === $userId) {
            $perfil->esCompartido = false;
            $perfil->permisoCompartido = 'Administrador';
        } else {
            $perfil->esCompartido = true;
            $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfil->idPerfil)
                ->value('permiso');
        }

        $apuntes = Apunte::where('idPerfil', $perfil->idPerfil)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return \Inertia\Inertia::render('Apuntes/Index', [
            'apuntes' => $apuntes,
            'perfilActivo' => $perfil
        ]);
    }

    public function create()
    {
        $perfil = $this->verificarAccesoPerfil('crear');

        $userId = Auth::id();
        if ($perfil->idUsuario === $userId) {
            $perfil->esCompartido = false;
            $perfil->permisoCompartido = 'Administrador';
        } else {
            $perfil->esCompartido = true;
            $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfil->idPerfil)
                ->value('permiso');
        }

        return Inertia::render('Apuntes/Editor', [
            'perfilActivo' => $perfil,
            'apunte' => null
        ]);
    }

    public function store(Request $request)
    {
        $perfil = $this->verificarAccesoPerfil('crear');

        $request->validate([
            'tituloApunte' => 'required|string|max:100',
            'contenidoApunte' => 'nullable|string'
        ]);

        Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte,
            'fechaCreacion' => now()
        ]);

        return redirect()->route('apuntes.index')->with('success', 'Apunte creado correctamente');
    }

    public function edit($id)
    {
        $perfil = $this->verificarAccesoPerfil('ver');

        $userId = Auth::id();
        if ($perfil->idUsuario === $userId) {
            $perfil->esCompartido = false;
            $perfil->permisoCompartido = 'Administrador';
        } else {
            $perfil->esCompartido = true;
            $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $userId)
                ->where('idPerfil', $perfil->idPerfil)
                ->value('permiso');
        }

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        return Inertia::render('Apuntes/Editor', [
            'perfilActivo' => $perfil,
            'apunte' => $apunte
        ]);
    }

    public function update(Request $request, $id)
    {
        $perfil = $this->verificarAccesoPerfil('modificar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        $request->validate([
            'tituloApunte' => 'required|string|max:100',
            'contenidoApunte' => 'nullable|string'
        ]);

        $apunte->update([
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte
        ]);

        return redirect()->route('apuntes.index')->with('success', 'Apunte actualizado correctamente');
    }

    public function destroy($id)
    {
        $perfil = $this->verificarAccesoPerfil('borrar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        $apunte->delete();

        return redirect()->route('apuntes.index')->with('success', 'Apunte eliminado correctamente');
    }
}
