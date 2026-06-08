<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\Estadistica;
<<<<<<< HEAD
=======
use App\Models\Racha;
use App\Models\SesionPomodoro;
>>>>>>> 04201550dfaf17f333f7d08f8a8a1072bd561dda
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
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
                // Obtenemos solo las tareas pendientes de ese perfil
                $tareas = Tarea::where('idPerfil', $perfilActivo->idPerfil)
                               ->where('estadoTarea', 'Pendiente')
                               ->get();
            }
        }

<<<<<<< HEAD
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
=======
        // Verificamos si la racha se perdió
        $estadisticaService = new EstadisticaService();
        $estadisticaService->verificarRachaPerdida($user->idUsuario);

        // 1. Estadísticas Generales del Usuario
        $estadistica = Estadistica::where('idUsuario', $user->idUsuario)->first() ?: (object)[
            'tareasTotales' => 0, 'tiempoTotalPomodoro' => 0, 'rachaMasLarga' => 0,
            'rachaActual' => 0, 'sesionesCanceladas' => 0, 'horasConcentracionDiaria' => 0
        ];

        // Lógica antigua: tareas completadas, retrasadas, eficiencia
        $todasLasTareas = Tarea::whereHas('perfil', function($q) use ($user) {
            $q->where('idUsuario', $user->idUsuario);
        })->get();

        $totalTareas = $todasLasTareas->count();
        $tareasCompletadas = $todasLasTareas->where('estadoTarea', 'Completado')->count();
        
        // Tareas completadas con retraso (fechaFinTarea > fechaLimite)
        $tareasRetrasadas = $todasLasTareas->filter(function($t) {
            return $t->estadoTarea === 'Completado' && $t->fechaFinTarea && $t->fechaFinTarea > $t->fechaLimite;
        })->count();

        $eficiencia = 0;
        if ($totalTareas > 0) {
            $eficiencia = round(($tareasCompletadas / $totalTareas) * 100);
        }

        $estadisticaView = [
            'tareasTotales' => $totalTareas,
            'tareasCompletadas' => $tareasCompletadas,
            'tareasRetrasadas' => $tareasRetrasadas,
            'eficiencia' => $eficiencia,
            'rachaMasLarga' => $estadistica->rachaMasLarga,
            'rachaActual' => $estadistica->rachaActual,
            'tiempoTotalPomodoro' => $estadistica->tiempoTotalPomodoro
        ];

        // 2. Datos para el Gráfico de Barras (Horas por día - Semana)
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

        // 3. Datos para el Gráfico de Barras (Horas por día - Mes)
        $chartDataMes = [];
        for ($i = 29; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $minutos = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($user) {
                    $q->where('idUsuario', $user->idUsuario);
                })
                ->where('estadoSesion', 'Completada')
                ->whereDate('updated_at', $fecha->toDateString())
                ->sum('tiempoTrabajoTotalMinutos');
                
            $chartDataMes[] = [
                'fecha' => $fecha->format('d/m'),
                'horas' => round($minutos / 60, 2)
            ];
        }

        // 4. Datos para el Gráfico de Dona (Horas por Perfil)
        $perfiles = Perfil::where('idUsuario', $user->idUsuario)->get();
        $chartDataPerfil = [];
        foreach ($perfiles as $p) {
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
                    'perfil' => $p->nombrePerfil,
                    'color' => $p->colorPerfil ?? '#9c27b0', // Fallback color
                    'horas' => round($minutos / 60, 2)
                ];
            }
        }

        // Enviamos los datos a principalGestion.html (ahora Dashboard.vue)
>>>>>>> 04201550dfaf17f333f7d08f8a8a1072bd561dda
        return Inertia::render('Dashboard', [
            'perfiles' => $perfiles,
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas,
<<<<<<< HEAD
            'estadisticas' => $estadisticas
=======
            'estadistica' => $estadisticaView,
            'chartDataSemana' => $chartDataSemana,
            'chartDataMes' => $chartDataMes,
            'chartDataPerfil' => $chartDataPerfil
>>>>>>> 04201550dfaf17f333f7d08f8a8a1072bd561dda
        ]);
    }
}