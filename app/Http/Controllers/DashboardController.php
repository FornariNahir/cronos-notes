<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        $perfilActivo = null;
        $tareas = [];

        if ($perfilActivoId) {
            // Obtenemos los datos del perfil activo
            $perfilActivo = Perfil::where('idPerfil', $perfilActivoId)
                                  ->where('idUsuario', Auth::user()->idUsuario)
                                  ->first();

            if ($perfilActivo) {
                // Obtenemos solo las tareas pendientes de ese perfil
                $tareas = Tarea::where('idPerfil', $perfilActivo->idPerfil)
                               ->where('estadoTarea', 'Pendiente')
                               ->get();
            }
        }

        // Enviamos los datos a principalGestion.html (ahora Dashboard.vue)
        return Inertia::render('Dashboard', [
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas
        ]);
    }
}