<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\Estadistica;
use App\Models\PerfilCompartido;
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

        // Perfiles compartidos con este usuario
        $perfilesCompartidos = Perfil::whereHas('usuariosCompartidos', function ($query) use ($user) {
                $query->where('PerfilCompartido.idUsuario', $user->idUsuario);
            })
            ->with([
                'usuario:idUsuario,nombre,apellido'
            ])
            ->withCount([
                'tareas',
                'tareas as tareas_completadas_count' => function ($query) {
                    $query->where('estadoTarea', 'Completado');
                },
                'usuariosCompartidos'
            ])
            ->get()
            ->map(function ($perfil) use ($user) {
                $perfil->esCompartido = true;
                $perfil->permisoCompartido = PerfilCompartido::where('idUsuario', $user->idUsuario)
                    ->where('idPerfil', $perfil->idPerfil)
                    ->value('permiso');
                $perfil->propietario = $perfil->usuario ? ($perfil->usuario->nombre . ' ' . $perfil->usuario->apellido) : 'Desconocido';
                return $perfil;
            });

        $perfilActivoId = session('perfilActivo');
        $perfilActivo = null;
        $tareas = [];

        if ($perfilActivoId) {
            // Obtenemos los datos del perfil activo (propietario o compartido)
            $perfilActivo = Perfil::where('idPerfil', $perfilActivoId)
                                  ->where(function ($query) use ($user) {
                                      $query->where('idUsuario', $user->idUsuario)
                                            ->orWhereHas('usuariosCompartidos', function ($q) use ($user) {
                                                $q->where('PerfilCompartido.idUsuario', $user->idUsuario);
                                            });
                                  })
                                  ->with('usuario:idUsuario,nombre,apellido')
                                  ->first();

            if ($perfilActivo) {
                // Determinar rol/permiso del usuario actual
                if ($perfilActivo->idUsuario === $user->idUsuario) {
                    $perfilActivo->esCompartido = false;
                    $perfilActivo->permisoCompartido = 'Administrador';
                    $perfilActivo->propietario = $user->nombre . ' ' . $user->apellido;
                } else {
                    $perfilActivo->esCompartido = true;
                    $perfilActivo->permisoCompartido = PerfilCompartido::where('idUsuario', $user->idUsuario)
                        ->where('idPerfil', $perfilActivoId)
                        ->value('permiso');
                    $perfilActivo->propietario = $perfilActivo->usuario ? ($perfilActivo->usuario->nombre . ' ' . $perfilActivo->usuario->apellido) : 'Desconocido';
                }

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

        // Calcular dinámicamente las horas de concentración diarias para HOY
        $minutosHoy = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($user) {
                $q->where('idUsuario', $user->idUsuario);
            })
            ->whereDate('fechaCreacionSesion', Carbon::today()->toDateString())
            ->sum('tiempoTrabajoTotalMinutos');
        $horasConcentracionDiaria = round($minutosHoy / 60, 2);

        if (!$estadisticas) {
            $estadisticas = Estadistica::create([
                'idUsuario' => $user->idUsuario,
                'rachaActual' => 0,
                'rachaMasLarga' => 0,
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'horasConcentracionDiaria' => $horasConcentracionDiaria
            ]);
        } else {
            $estadisticas->update([
                'horasConcentracionDiaria' => $horasConcentracionDiaria
            ]);
        }

        // Datos para el Gráfico de Barras (Semana)
        $chartDataSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            $minutos = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($user) {
                    $q->where('idUsuario', $user->idUsuario);
                })
                ->whereDate('fechaCreacionSesion', $fecha->toDateString())
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
            'perfilesCompartidos' => $perfilesCompartidos,
            'perfilActivo' => $perfilActivo,
            'tareas' => $tareas,
            'estadisticas' => $estadisticas,
            'chartDataSemana' => $chartDataSemana,
            'chartDataPerfil' => $chartDataPerfil
        ]);
    }
}