<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerfilController extends Controller
{
    // Reemplaza a agregarPerfil()
    public function store(Request $request)
    {
        // Validar que los campos no estén vacíos
        $request->validate([
            'tituloPerfil' => 'required|string|max:255',
            'descripcionPerfil' => 'required|string'
        ]);

        // Crear el perfil.
        Perfil::create([
            'titulo' => $request->tituloPerfil,
            'descripcion' => $request->descripcionPerfil,
            'idUsuario' => Auth::user()->idUsuario
        ]);

        return redirect()->route('perfiles.index')->with('success', 'Perfil agregado exitosamente');
    }

    // Reemplaza a obtenerPerfil() y verificarPropietarioPerfil()
    public function show($id)
    {
        // Aseguramos que el perfil pertenezca al usuario autenticado
        $perfil = Perfil::where('idPerfil', $id)
                        ->where('idUsuario', Auth::user()->idUsuario)
                        ->firstOrFail();

        return view('perfiles.show', compact('perfil'));
    }

    // Reemplaza a setPerfilActivo()
    public function setActivo(Request $request)
    {
        $request->validate(['idPerfil' => 'required|exists:perfiles,idPerfil']);
        
        // Guardamos el perfil activo en la sesión de Laravel
        session(['perfil_activo' => $request->idPerfil]);

        return redirect()->back()->with('success', 'Perfil activo establecido');
    }
}