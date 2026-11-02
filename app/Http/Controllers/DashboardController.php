<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\Estadistica;
use App\Models\PerfilCompartido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Verificar y resetear racha si corresponde antes de cargar estadísticas
        $estadisticaService = new EstadisticaService();
        $estadisticaService->verificarRachaPerdida($user->idUsuario);

        // Obtenemos todos los perfiles del usuario con el conteo de sus tareas para calcular el progreso
        $perfiles = Perfil::where('idUsuario', $user->idUsuario)
            ->withCount([
                'tareas',
                'tareas as tareas_completadas_count' => function ($query) {
                    $query->where('estadoTarea', 'Completado');
                },
                'usuariosCompartidos'
            ])
            ->get();

        // Perfiles compartidos con este usuario (sin N+1 sobre PerfilCompartido)
        $permisosPorPerfil = PerfilCompartido::where('idUsuario', $user->idUsuario)
            ->pluck('permiso', 'idPerfil');

        $perfilesCompartidos = collect();
        if ($permisosPorPerfil->isNotEmpty()) {
            $perfilesCompartidos = Perfil::whereIn('idPerfil', $permisosPorPerfil->keys())
                ->with(['usuario:idUsuario,nombre,apellido'])
                ->withCount([
                    'tareas',
                    'tareas as tareas_completadas_count' => function ($query) {
                        $query->where('estadoTarea', 'Completado');
                    },
                    'usuariosCompartidos'
                ])
                ->get()
                ->map(function ($perfil) use ($permisosPorPerfil) {
                    $perfil->esCompartido = true;
                    $perfil->permisoCompartido = $permisosPorPerfil[$perfil->idPerfil] ?? 'Leer';
                    $perfil->propietario = $perfil->usuario ? ($perfil->usuario->nombre . ' ' . $perfil->usuario->apellido) : 'Desconocido';
                    return $perfil;
                });
        }

        $perfilActivoId = session('perfilActivo');
        $perfilActivo = null;
        $tareas = [];

        if ($perfilActivoId) {
            // Reutilizar de memoria si ya lo cargamos arriba
            $perfilActivo = $perfiles->firstWhere('idPerfil', (int) $perfilActivoId)
                ?? $perfilesCompartidos->firstWhere('idPerfil', (int) $perfilActivoId);

            if ($perfilActivo) {
                if ($perfilActivo->idUsuario === $user->idUsuario) {
                    $perfilActivo->esCompartido = false;
                    $perfilActivo->permisoCompartido = 'Administrador';
                    $perfilActivo->propietario = $user->nombre . ' ' . $user->apellido;
                }

                // Obtenemos solo las tareas pendientes de ese perfil sumando ciclos Pomodoro
                $tareas = Tarea::where('idPerfil', $perfilActivo->idPerfil)
                    ->where('estadoTarea', 'Pendiente')
                    ->withSum(['sesionesPomodoro' => function ($query) {
                        $query->where('estadoSesion', 'Completada');
                    }], 'ciclosCompletados')
                    ->get();
            }
        }

        // 1 sola consulta agrupada para los últimos 7 días (incluyendo HOY)
        $fechaInicioSemana = Carbon::today()->subDays(6)->startOfDay();
        $minutosPorDia = SesionPomodoro::join('ConfiguracionPomodoro', 'SesionPomodoro.idConfiguracionPomodoro', '=', 'ConfiguracionPomodoro.idConfiguracionPomodoro')
            ->where('ConfiguracionPomodoro.idUsuario', $user->idUsuario)
            ->where('SesionPomodoro.fechaCreacionSesion', '>=', $fechaInicioSemana)
            ->selectRaw('DATE(SesionPomodoro.fechaCreacionSesion) as fecha, SUM(SesionPomodoro.tiempoTrabajoTotalMinutos) as total_minutos')
            ->groupBy(DB::raw('DATE(SesionPomodoro.fechaCreacionSesion)'))
            ->pluck('total_minutos', 'fecha');

        $hoyStr = Carbon::today()->toDateString();
        $minutosHoy = (int) ($minutosPorDia[$hoyStr] ?? 0);
        $horasConcentracionDiaria = round($minutosHoy / 60, 2);

        $estadisticas = Estadistica::where('idUsuario', $user->idUsuario)->first();
        if (!$estadisticas) {
            $estadisticas = Estadistica::create([
                'idUsuario' => $user->idUsuario,
                'rachaActual' => 0,
                'rachaMasLarga' => 0,
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'horasConcentracionDiaria' => $horasConcentracionDiaria
            ]);
        } elseif (abs((float) $estadisticas->horasConcentracionDiaria - $horasConcentracionDiaria) > 0.001) {
            $estadisticas->update([
                'horasConcentracionDiaria' => $horasConcentracionDiaria
            ]);
        }

        // Construir Gráfico de Barras (Semana) en memoria sin hacer 7 queries
        $chartDataSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $fechaKey = $fecha->toDateString();
            $minutos = (int) ($minutosPorDia[$fechaKey] ?? 0);

            $chartDataSemana[] = [
                'fecha' => $fecha->format('d/m'),
                'horas' => round($minutos / 60, 2)
            ];
        }

        // 1 sola consulta agrupada por Perfil para el Gráfico de Dona
        $minutosPorPerfil = SesionPomodoro::join('ConfiguracionPomodoro', 'SesionPomodoro.idConfiguracionPomodoro', '=', 'ConfiguracionPomodoro.idConfiguracionPomodoro')
            ->join('Tarea', 'SesionPomodoro.idTarea', '=', 'Tarea.idTarea')
            ->where('ConfiguracionPomodoro.idUsuario', $user->idUsuario)
            ->selectRaw('Tarea.idPerfil as idPerfil, SUM(SesionPomodoro.tiempoTrabajoTotalMinutos) as total_minutos')
            ->groupBy('Tarea.idPerfil')
            ->pluck('total_minutos', 'idPerfil');

        $chartDataPerfil = [];
        $coloresDisponibles = ['#612c2d', '#c4a5a5', '#e5d5d5', '#8b4c4c', '#531d55'];
        foreach ($perfiles as $index => $p) {
            $minutos = (int) ($minutosPorPerfil[$p->idPerfil] ?? 0);
            if ($minutos > 0) {
                $chartDataPerfil[] = [
                    'perfil' => $p->tituloPerfil,
                    'color' => $p->colorPerfil ?? $coloresDisponibles[$index % count($coloresDisponibles)],
                    'horas' => round($minutos / 60, 2)
                ];
            }
        }

        return Inertia::render('Dashboard', [
            'perfiles' => $perfiles,
            'perfilesCompartidos' => $perfilesCompartidos,
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas,
            'estadisticas' => $estadisticas,
            'chartDataSemana' => $chartDataSemana,
            'chartDataPerfil' => $chartDataPerfil
        ]);
    }
}
