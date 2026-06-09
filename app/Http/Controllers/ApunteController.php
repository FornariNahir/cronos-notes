<?php

namespace App\Http\Controllers;

use App\Models\Apunte;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ApunteController extends Controller
{
    public function index()
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $apuntes = Apunte::where('idPerfil', $perfilActivoId)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        $perfilActivo = Perfil::find($perfilActivoId);

        return Inertia::render('Apuntes/Index', [
            'apuntes' => $apuntes,
            'perfilActivo' => $perfilActivo
        ]);
    }

    public function create()
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $perfilActivo = Perfil::find($perfilActivoId);

        return Inertia::render('Apuntes/Editor', [
            'perfilActivo' => $perfilActivo,
            'apunte' => null
        ]);
    }

    public function store(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $request->validate([
            'tituloApunte' => 'required|string|max:100',
            'contenidoApunte' => 'nullable|string'
        ]);

        Apunte::create([
            'idPerfil' => $perfilActivoId,
            'tituloApunte' => $request->tituloApunte,
            'contenidoApunte' => $request->contenidoApunte,
            'fechaCreacion' => now()
        ]);

        return redirect()->route('apuntes.index')->with('success', 'Apunte creado correctamente');
    }

    public function edit($id)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfilActivoId)
            ->firstOrFail();

        $perfilActivo = Perfil::find($perfilActivoId);

        return Inertia::render('Apuntes/Editor', [
            'perfilActivo' => $perfilActivo,
            'apunte' => $apunte
        ]);
    }

    public function update(Request $request, $id)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfilActivoId)
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
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $apunte = Apunte::where('idApunte', $id)
            ->where('idPerfil', $perfilActivoId)
            ->firstOrFail();

        $apunte->delete();

        return redirect()->route('apuntes.index')->with('success', 'Apunte eliminado correctamente');
    }
}
