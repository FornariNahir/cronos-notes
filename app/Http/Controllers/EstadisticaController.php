<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        
        // Verificar y resetear racha si corresponde antes de cargar estadísticas
        $estadisticaService = new \App\Services\EstadisticaService();
        $estadisticaService->verificarRachaPerdida($user->idUsuario);

        // 1. Estadísticas Rápidas
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

        // 1 sola consulta agregada para conteos de tareas
        $resumenTareas = Tarea::join('Perfil', 'Tarea.idPerfil', '=', 'Perfil.idPerfil')
            ->where('Perfil.idUsuario', $user->idUsuario)
            ->selectRaw('
                COUNT(*) as creadas,
                SUM(CASE WHEN Tarea.estadoTarea = "Completado" THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN Tarea.estadoTarea = "Completado" AND Tarea.updated_at > Tarea.fechaLimite THEN 1 ELSE 0 END) as retrasadas
            ')
            ->first();

        $tareasCreadas = (int) ($resumenTareas->creadas ?? 0);
        $tareasCompletadas = (int) ($resumenTareas->completadas ?? 0);
        $tareasRetrasadas = (int) ($resumenTareas->retrasadas ?? 0);

        $eficiencia = $tareasCreadas > 0 ? round(($tareasCompletadas / $tareasCreadas) * 100) : 0;

        // 2. Gráfico de Barras (Semana) + Horas de hoy en 1 sola consulta
        $fechaInicioSemana = Carbon::today()->subDays(6)->startOfDay();
        $minutosPorDia = SesionPomodoro::join('ConfiguracionPomodoro', 'SesionPomodoro.idConfiguracionPomodoro', '=', 'ConfiguracionPomodoro.idConfiguracionPomodoro')
            ->where('ConfiguracionPomodoro.idUsuario', $user->idUsuario)
            ->where('SesionPomodoro.fechaCreacionSesion', '>=', $fechaInicioSemana)
            ->selectRaw('DATE(SesionPomodoro.fechaCreacionSesion) as fecha, SUM(SesionPomodoro.tiempoTrabajoTotalMinutos) as total_minutos')
            ->groupBy(DB::raw('DATE(SesionPomodoro.fechaCreacionSesion)'))
            ->pluck('total_minutos', 'fecha');

        $chartDataSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $minutos = (int) ($minutosPorDia[$fecha->toDateString()] ?? 0);
                
            $chartDataSemana[] = [
                'fecha' => $fecha->format('d/m'),
                'horas' => round($minutos / 60, 2)
            ];
        }

        // 3. Gráfico de Dona (Perfil) en 1 sola consulta agrupada
        $perfiles = Perfil::where('idUsuario', $user->idUsuario)->get();
        $datosPorPerfil = SesionPomodoro::join('ConfiguracionPomodoro', 'SesionPomodoro.idConfiguracionPomodoro', '=', 'ConfiguracionPomodoro.idConfiguracionPomodoro')
            ->join('Tarea', 'SesionPomodoro.idTarea', '=', 'Tarea.idTarea')
            ->where('ConfiguracionPomodoro.idUsuario', $user->idUsuario)
            ->selectRaw('Tarea.idPerfil as idPerfil, SUM(SesionPomodoro.tiempoTrabajoTotalMinutos) as total_minutos, COUNT(SesionPomodoro.idSesionPomodoro) as total_sesiones')
            ->groupBy('Tarea.idPerfil')
            ->get()
            ->keyBy('idPerfil');

        $chartDataPerfil = [];
        $coloresDisponibles = ['#612c2d', '#c4a5a5', '#e5d5d5', '#8b4c4c', '#531d55'];
        foreach ($perfiles as $index => $p) {
            $row = $datosPorPerfil->get($p->idPerfil);
            $minutos = (int) ($row->total_minutos ?? 0);
            $sesionesCount = (int) ($row->total_sesiones ?? 0);
                
            if ($minutos > 0 || $sesionesCount > 0) {
                $chartDataPerfil[] = [
                    'perfil' => $p->tituloPerfil,
                    'color' => $p->colorPerfil ?? $coloresDisponibles[$index % count($coloresDisponibles)],
                    'horas' => round($minutos / 60, 2),
                    'sesiones' => $sesionesCount
                ];
            }
        }

        // 4. Horas de concentración diaria (hoy)
        $hoyStr = Carbon::today()->toDateString();
        $minutosHoy = (int) ($minutosPorDia[$hoyStr] ?? 0);
        $horasConcentracionDiaria = round($minutosHoy / 60, 2);

        if (abs((float) $estadistica->horasConcentracionDiaria - $horasConcentracionDiaria) > 0.001) {
            $estadistica->update([
                'horasConcentracionDiaria' => $horasConcentracionDiaria
            ]);
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
            'chartDataPerfil' => $chartDataPerfil,
            'horasConcentracionDiaria' => $horasConcentracionDiaria
        ]);
    }
}
