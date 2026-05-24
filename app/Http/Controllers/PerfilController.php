<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PerfilController extends Controller
{
    public function index()
    {
        $perfiles = Perfil::where('idUsuario', Auth::user()->idUsuario)->get();

        return Inertia::render('Profiles/Index', [
            'perfiles' => $perfiles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tituloPerfil' => 'required|string|max:30',
            'descripcionPerfil' => 'required|string|max:100'
        ]);

        Perfil::create([
            'tituloPerfil' => $request->tituloPerfil,
            'descripcionPerfil' => $request->descripcionPerfil,
            'idUsuario' => Auth::user()->idUsuario
        ]);

        return redirect()->route('perfiles.index')->with('success', 'Perfil agregado exitosamente');
    }

    public function show($id)
    {
        $perfil = Perfil::where('idPerfil', $id)
                        ->where('idUsuario', Auth::user()->idUsuario)
                        ->firstOrFail();

        return Inertia::render('Profiles/Show', [
            'perfil' => $perfil
        ]);
    }

    public function update(Request $request, $id)
    {
        $perfil = Perfil::where('idPerfil', $id)
                        ->where('idUsuario', Auth::user()->idUsuario)
                        ->firstOrFail();

        $request->validate([
            'tituloPerfil' => 'required|string|max:30',
            'descripcionPerfil' => 'required|string|max:100'
        ]);

        $perfil->update([
            'tituloPerfil' => $request->tituloPerfil,
            'descripcionPerfil' => $request->descripcionPerfil
        ]);

        return redirect()->route('perfiles.index')->with('success', 'Perfil actualizado');
    }

    public function destroy($id)
    {
        $perfil = Perfil::where('idPerfil', $id)
                        ->where('idUsuario', Auth::user()->idUsuario)
                        ->firstOrFail();

        $perfil->delete();

        return redirect()->route('perfiles.index')->with('success', 'Perfil eliminado');
    }

    public function setActivo(Request $request)
    {
        $request->validate(['idPerfil' => 'required|exists:Perfil,idPerfil']);
        session(['perfilActivo' => $request->idPerfil]);
        return redirect()->route('dashboard');
    }
}
