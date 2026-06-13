<?php

namespace App\Http\Controllers;

use App\Models\Apunte;
use App\Models\Perfil;
use App\Models\PerfilCompartido;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            ->withCount('audios')
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
            'tipoApunte' => 'required|string|in:normal,cornell',
            'contenidoApunte' => 'nullable|string',
            'ideasApunte' => 'nullable|string',
            'resumenApunte' => 'nullable|string'
        ]);

        $apunte = Apunte::create([
            'idPerfil' => $perfil->idPerfil,
            'tipoApunte' => $request->tipoApunte,
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte,
            'ideasApunte' => $request->ideasApunte,
            'resumenApunte' => $request->resumenApunte,
            'fechaCreacion' => now()
        ]);

        return redirect()->route('apuntes.edit', $apunte->idApunte)->with('success', 'Apunte creado correctamente. ¡Ya podés empezar a grabar audios!');
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
            ->with('audios')
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
            'tipoApunte' => 'required|string|in:normal,cornell',
            'contenidoApunte' => 'nullable|string',
            'ideasApunte' => 'nullable|string',
            'resumenApunte' => 'nullable|string'
        ]);

        $apunte->update([
            'tipoApunte' => $request->tipoApunte,
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte,
            'ideasApunte' => $request->ideasApunte,
            'resumenApunte' => $request->resumenApunte
        ]);

        return redirect()->route('apuntes.index')->with('success', 'Apunte actualizado correctamente');
    }

    public function destroy($id)
    {
        $perfil = $this->verificarAccesoPerfil('borrar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        // Eliminar archivos físicos de todos los audios
        foreach ($apunte->audios as $audio) {
            Storage::disk('public')->delete($audio->rutaAudio);
        }

        $apunte->delete();

        return redirect()->route('apuntes.index')->with('success', 'Apunte eliminado correctamente');
    }

    /**
     * Sube un audio asociado a un apunte existente.
     */
    public function uploadAudio(Request $request, $id)
    {
        $perfil = $this->verificarAccesoPerfil('modificar');

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfil->idPerfil)
            ->firstOrFail();

        // Validar límite de 5 audios por nota
        $limiteAudios = 5;
        if ($apunte->audios()->count() >= $limiteAudios) {
            return redirect()->back()->withErrors([
                'audio' => "Límite alcanzado: Máximo {$limiteAudios} grabaciones por apunte."
            ]);
        }

        $request->validate([
            'audio' => 'required|file|max:10240' // max 10MB
        ]);

        $path = $request->file('audio')->store('apuntes_audios', 'public');

        $apunte->audios()->create([
            'rutaAudio' => $path,
            'fechaCreacion' => now()
        ]);

        return redirect()->back()->with('success', 'Grabación guardada correctamente');
    }

    /**
     * Elimina un audio específico.
     */
    public function destroyAudio($audioId)
    {
        $audio = \App\Models\ApunteAudio::findOrFail($audioId);
        
        // Verificar acceso del usuario
        $apunte = Apunte::findOrFail($audio->idApunte);
        $perfil = Perfil::findOrFail($apunte->idPerfil);
        $this->authorize('modificar', $perfil);

        // Eliminar archivo del almacenamiento
        Storage::disk('public')->delete($audio->rutaAudio);

        // Eliminar registro
        $audio->delete();

        return redirect()->back()->with('success', 'Audio eliminado correctamente');
    }
}
