<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\Estadistica;
use App\Models\Racha;
use App\Models\SesionPomodoro;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\EstadisticaService;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
        return Inertia::render('Dashboard', [
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas,
            'estadistica' => $estadisticaView,
            'chartDataSemana' => $chartDataSemana,
            'chartDataMes' => $chartDataMes,
            'chartDataPerfil' => $chartDataPerfil
        ]);
    }
}