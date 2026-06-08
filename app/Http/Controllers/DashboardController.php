<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\Estadistica;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\SesionPomodoro;
use App\Services\EstadisticaService;

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

        $user = Auth::user();

        if ($perfilActivoId) {
            // Obtenemos los datos del perfil activo
            $perfilActivo = Perfil::where('idPerfil', $perfilActivoId)
                                  ->where('idUsuario', $user->idUsuario)
                                  ->first();

            if ($perfilActivo) {
                // Obtenemos solo las tareas pendientes de ese perfil sumando ciclos Pomodoro
                $tareas = Tarea::where('idPerfil', $perfilActivo->idPerfil)
                               ->where('estadoTarea', 'Pendiente')
                               ->withSum(['sesionesPomodoro' => function($query) {
                                   $query->where('estadoSesion', 'Completada');
                               }], 'ciclosCompletados')
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

        // Datos para el Gráfico de Barras (Semana)
        $chartDataSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $minutos = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($user) {
                    $q->where('idUsuario', $user->idUsuario);
                })
                ->where('estadoSesion', 'Completada')
                ->whereDate('updated_at', $fecha->toDateString())
                ->sum('tiempoTrabajoTotalMinutos');
                
            $chartDataSemana[] = [
                'fecha' => $fecha->format('d/m'),
                'horas' => round($minutos / 60, 2)
            ];
        }

        // Datos para el Gráfico de Dona (Perfil)
        $chartDataPerfil = [];
        $coloresDisponibles = ['#612c2d', '#c4a5a5', '#e5d5d5', '#8b4c4c', '#531d55'];
        foreach ($perfiles as $index => $p) {
            $minutos = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($user) {
                    $q->where('idUsuario', $user->idUsuario);
                })
                ->whereHas('tarea', function($q) use ($p) {
                    $q->where('idPerfil', $p->idPerfil);
                })
                ->where('estadoSesion', 'Completada')
                ->sum('tiempoTrabajoTotalMinutos');
                
            if ($minutos > 0) {
                $chartDataPerfil[] = [
                    'perfil' => $p->tituloPerfil,
                    'color' => $p->colorPerfil ?? $coloresDisponibles[$index % count($coloresDisponibles)],
                    'horas' => round($minutos / 60, 2)
                ];
            }
        }

        // Enviamos los datos a Dashboard.vue
        return Inertia::render('Dashboard', [
            'perfiles' => $perfiles,
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas,
            'estadisticas' => $estadisticas,
            'chartDataSemana' => $chartDataSemana,
            'chartDataPerfil' => $chartDataPerfil
        ]);
    }
}