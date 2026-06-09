<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\SesionPomodoro;
use App\Models\Tarea;
use App\Models\Perfil;
use App\Models\Estadistica;

class EstadisticaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. Estadísticas Rápidas (Old Cronos)
        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $user->idUsuario],
            [
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'rachaMasLarga' => 0,
                'rachaActual' => 0,
                'sesionesCanceladas' => 0,
                'horasConcentracionDiaria' => 0
            ]
        );

        $tareasCreadas = Tarea::whereHas('perfil', function($q) use ($user) {
            $q->where('idUsuario', $user->idUsuario);
        })->count();

        $tareasCompletadas = Tarea::whereHas('perfil', function($q) use ($user) {
            $q->where('idUsuario', $user->idUsuario);
        })->where('estadoTarea', 'Completado')->count();

        $tareasRetrasadas = Tarea::whereHas('perfil', function($q) use ($user) {
            $q->where('idUsuario', $user->idUsuario);
        })->where('estadoTarea', 'Completado')
          ->whereColumn('updated_at', '>', 'fechaLimite')
          ->count();

        $eficiencia = $tareasCreadas > 0 ? round(($tareasCompletadas / $tareasCreadas) * 100) : 0;

        // 2. Gráfico de Barras (Semana)
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

        // 3. Gráfico de Dona (Perfil)
        $perfiles = Perfil::where('idUsuario', $user->idUsuario)->get();
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

        return Inertia::render('Estadisticas', [
            'rachaMasLarga' => $estadistica->rachaMasLarga,
            'rachaActual' => $estadistica->rachaActual,
            'tiempoTotalPomodoro' => $estadistica->tiempoTotalPomodoro,
            'tareasCreadas' => $tareasCreadas,
            'tareasCompletadas' => $tareasCompletadas,
            'tareasRetrasadas' => $tareasRetrasadas,
            'eficiencia' => $eficiencia,
            'chartDataSemana' => $chartDataSemana,
            'chartDataPerfil' => $chartDataPerfil
        ]);
    }
}
