<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\PerfilCompartido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class PerfilController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $perfiles = Perfil::where('idUsuario', Auth::user()->idUsuario)->get();

        // Perfiles compartidos con este usuario
        $perfilesCompartidos = Auth::user()->perfilesCompartidos()
            ->with('usuario:idUsuario,nombre,apellido')
            ->get()
            ->map(function ($perfil) {
                $perfil->esCompartido = true;
                $perfil->permisoCompartido = $perfil->pivot->permiso;
                $perfil->propietario = $perfil->usuario->nombre . ' ' . $perfil->usuario->apellido;
                return $perfil;
            });

        return Inertia::render('GestionPerfil', [
            'perfiles' => $perfiles,
            'perfilesCompartidos' => $perfilesCompartidos
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tituloPerfil' => 'required|string|max:30',
            'descripcionPerfil' => 'nullable|string|max:100'
        ]);

        // Validar que el usuario no tenga otro perfil con el mismo nombre
        $exists = Perfil::where('idUsuario', Auth::user()->idUsuario)
                        ->where('tituloPerfil', $request->tituloPerfil)
                        ->exists();
        if ($exists) {
            return redirect()->back()->withErrors([
                'tituloPerfil' => 'Ya tienes un perfil con este nombre.'
            ]);
        }

        $count = Perfil::where('idUsuario', Auth::user()->idUsuario)->count();
        if ($count >= 5) {
            return redirect()->back()->withErrors([
                'tituloPerfil' => 'No puedes tener más de 5 perfiles.'
            ]);
        }

        Perfil::create([
            'tituloPerfil' => $request->tituloPerfil,
            'descripcionPerfil' => $request->descripcionPerfil,
            'idUsuario' => Auth::user()->idUsuario
        ]);

        return redirect()->route('gestion-perfil')->with('success', 'Perfil agregado exitosamente');
    }

    public function show($id)
    {
        $perfil = Perfil::findOrFail($id);
        $this->authorize('ver', $perfil);

        return Inertia::render('Profiles/Show', [
            'perfil' => $perfil
        ]);
    }

    public function update(Request $request, $id)
    {
        $perfil = Perfil::findOrFail($id);
        $this->authorize('modificar', $perfil);

        $request->validate([
            'tituloPerfil' => 'required|string|max:30',
            'descripcionPerfil' => 'nullable|string|max:100'
        ]);

        // Validar que el usuario no tenga otro perfil con el mismo nombre
        $exists = Perfil::where('idUsuario', $perfil->idUsuario)
                        ->where('tituloPerfil', $request->tituloPerfil)
                        ->where('idPerfil', '!=', $id)
                        ->exists();
        if ($exists) {
            return redirect()->back()->withErrors([
                'tituloPerfil' => 'Ya tienes otro perfil con este nombre.'
            ]);
        }

        $perfil->update([
            'tituloPerfil' => $request->tituloPerfil,
            'descripcionPerfil' => $request->descripcionPerfil
        ]);

        return redirect()->route('gestion-perfil')->with('success', 'Perfil actualizado');
    }

    public function destroy($id)
    {
        $perfil = Perfil::findOrFail($id);
        $this->authorize('borrar', $perfil);

        $perfil->delete();

        return redirect()->route('gestion-perfil')->with('success', 'Perfil eliminado');
    }

    public function setActivo(Request $request)
    {
        $request->validate([
            'idPerfil' => 'required|exists:Perfil,idPerfil',
            'redirect' => 'nullable|string'
        ]);

        // Verificar que el usuario tenga acceso al perfil (propietario o compartido)
        $perfil = Perfil::findOrFail($request->idPerfil);
        $this->authorize('ver', $perfil);

        session(['perfilActivo' => $request->idPerfil]);
        if ($request->filled('redirect')) {
            return redirect($request->redirect);
        }
        return redirect()->route('dashboard');
    }
}
