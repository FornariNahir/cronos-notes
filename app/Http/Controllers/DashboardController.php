<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\Estadistica;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Obtenemos todos los perfiles del usuario con el conteo de sus tareas para calcular el progreso
        $perfiles = Perfil::where('idUsuario', $user->idUsuario)
            ->withCount([
                'tareas',
                'tareas as tareas_completadas_count' => function ($query) {
                    $query->where('estadoTarea', 'Completado');
                }
            ])
            ->get();

        $perfilActivoId = session('perfilActivo');
        $perfilActivo = null;
        $tareas = [];

        if ($perfilActivoId) {
            // Obtenemos los datos del perfil activo
            $perfilActivo = Perfil::where('idPerfil', $perfilActivoId)
                                  ->where('idUsuario', $user->idUsuario)
                                  ->first();

            if ($perfilActivo) {
                // Obtenemos solo las tareas pendientes de ese perfil
                $tareas = Tarea::where('idPerfil', $perfilActivo->idPerfil)
                               ->where('estadoTarea', 'Pendiente')
                               ->get();
            }
        }

        // Obtenemos las estadísticas del usuario o creamos valores por defecto
        $estadisticas = Estadistica::where('idUsuario', $user->idUsuario)->first();
        if (!$estadisticas) {
            $estadisticas = (object) [
                'rachaActual' => 0,
                'rachaMasLarga' => 0,
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'horasConcentracionDiaria' => 0
            ];
        }

        // Enviamos los datos a Dashboard.vue
        return Inertia::render('Dashboard', [
            'perfiles' => $perfiles,
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas,
            'estadisticas' => $estadisticas
        ]);
    }
}