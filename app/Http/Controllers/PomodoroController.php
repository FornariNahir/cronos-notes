<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionPomodoro;
use App\Models\SesionPomodoro;
use App\Models\Perfil;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\EstadisticaService;

class PomodoroController extends Controller
{
    public function index()
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('dashboard')->with('error', 'Por favor, selecciona un perfil primero para acceder al espacio de concentración.');
        }

        $estadisticaService = new EstadisticaService();
        $estadisticaService->verificarRachaPerdida(Auth::user()->idUsuario);

        $configs = ConfiguracionPomodoro::where('idUsuario', Auth::user()->idUsuario)->get();
        $tareas = Tarea::where('idPerfil', $perfilActivoId)
            ->where('estadoTarea', '!=', 'Completado')
            ->withSum('sesionesPomodoro as sesiones_pomodoro_sum_ciclos_completados', 'ciclosCompletados')
            ->get();

        $sesionActiva = session('sesionPomodoroActiva');

        $apuntes = \App\Models\Apunte::where('idPerfil', $perfilActivoId)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return Inertia::render('pomodoro/SesionZen', [
            'configs' => $configs,
            'tareas' => $tareas,
            'perfilActivo' => Perfil::find($perfilActivoId),
            'sesionActiva' => $sesionActiva,
            'apuntes' => $apuntes,
            'tenorApiKey' => env('TENOR_API_KEY'),
            'giphyApiKey' => env('GIPHY_API_KEY')
        ]);
    }

    public function iniciarSesion(Request $request)
    {
        $esInicioRapido = $request->boolean('esInicioRapido');

        if ($esInicioRapido) {
            $request->validate([
                'idTarea' => 'nullable|exists:Tarea,idTarea',
                'sonidoSeleccionado' => 'nullable|string',
                'volumenSonido' => 'nullable|integer|min:0|max:100'
            ]);
        } else {
            $request->validate([
                'duracionSesion' => 'required|integer|min:1|max:120',
                'duracionDescansoCorto' => 'required|integer|min:1|max:30',
                'duracionDescansoLargo' => 'required|integer|min:5|max:60',
                'ciclosObjetivo' => 'required|integer|min:1|max:10',
                'idTarea' => 'nullable|exists:Tarea,idTarea',
                'sonidoSeleccionado' => 'nullable|string',
                'volumenSonido' => 'nullable|integer|min:0|max:100'
            ]);
        }

        $user = Auth::user();

        if ($esInicioRapido) {
            $config = ConfiguracionPomodoro::firstOrCreate([
                'idUsuario' => $user->idUsuario,
                'duracionSesion' => 25,
                'duracionDescansoCorto' => 5,
                'duracionDescansoLargo' => 5,
                'sesionesPrevioDescansoLargo' => 1
            ]);
            $ciclosObjetivo = 1;
        } else {
            $config = ConfiguracionPomodoro::firstOrCreate([
                'idUsuario' => $user->idUsuario,
                'duracionSesion' => $request->duracionSesion,
                'duracionDescansoCorto' => $request->duracionDescansoCorto,
                'duracionDescansoLargo' => $request->duracionDescansoLargo,
                'sesionesPrevioDescansoLargo' => $request->ciclosObjetivo
            ]);
            $ciclosObjetivo = $request->ciclosObjetivo;
        }

        $tarea = $request->idTarea ? Tarea::find($request->idTarea) : null;
        if ($tarea && $tarea->estadoTarea === 'Pendiente') {
            $tarea->update(['estadoTarea' => 'En Progreso']);
        }

        $sesion = SesionPomodoro::create([
            'idConfiguracionPomodoro' => $config->idConfiguracionPomodoro,
            'idTarea' => $request->idTarea,
            'estadoSesion' => 'En Progreso',
            'ciclosObjetivo' => $ciclosObjetivo,
            'ciclosCompletados' => 0,
            'tiempoTrabajoTotalMinutos' => 0
        ]);

        $sonido = $request->sonidoSeleccionado;
        $volumen = $request->volumenSonido ?? 50;

        session([
            'sesionPomodoroActiva' => [
                'idSesion' => $sesion->idSesionPomodoro,
                'idConfiguracionPomodoro' => $config->idConfiguracionPomodoro,
                'idTarea' => $request->idTarea,
                'tituloTarea' => $tarea?->tituloTarea ?? ($esInicioRapido ? 'Sesión rápida' : 'Sesión personalizada'),
                'descripcionTarea' => $tarea?->descripcionTarea ?? ($esInicioRapido ? 'Sin tarea asociada' : 'Configuración a medida'),
                'duracionSesion' => $config->duracionSesion,
                'duracionDescansoCorto' => $config->duracionDescansoCorto,
                'duracionDescansoLargo' => $config->duracionDescansoLargo,
                'sesionesPrevioDescansoLargo' => $config->sesionesPrevioDescansoLargo,
                'sonidoSeleccionado' => $sonido,
                'volumenSonido' => $volumen
            ]
        ]);

        return redirect()->back();
    }

    public function registrarTrabajo(Request $request)
    {
        $request->validate([
            'minutosTrabajados' => 'nullable|integer|min:0',
            'incrementarCiclo' => 'nullable|boolean'
        ]);

        $sesionActiva = session('sesionPomodoroActiva');
        if (!$sesionActiva) {
            return response()->json(['error' => 'No hay sesión activa'], 400);
        }

        $sesion = SesionPomodoro::find($sesionActiva['idSesion']);
        if ($sesion) {
            $minutos = $request->minutosTrabajados ?? 0;
            if ($minutos > 0) {
                $sesion->increment('tiempoTrabajoTotalMinutos', $minutos);

                $estadisticaService = new EstadisticaService();
                $estadisticaService->registrarTiempoTrabajo(Auth::user()->idUsuario, $minutos);
            }

            if ($request->boolean('incrementarCiclo')) {
                $sesion->increment('ciclosCompletados');

                $estadisticaService = new EstadisticaService();
                $estadisticaService->evaluarRachaAlCompletarSesion(Auth::user()->idUsuario, $sesion->configuracionPomodoro->duracionSesion);

                // Si al incrementar los ciclos completados, llegamos o superamos los ciclos objetivo (Regla de negocio)
                if ($sesion->ciclosCompletados >= $sesion->ciclosObjetivo) {
                    $sesion->update([
                        'estadoSesion' => 'Completada'
                    ]);

                    if ($sesion->idTarea) {
                        $tarea = Tarea::find($sesion->idTarea);
                        if ($tarea && $tarea->estadoTarea !== 'Completado') {
                            $tarea->update(['estadoTarea' => 'Completado']);
                            $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);
                        }
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function actualizarEstado(Request $request)
    {
        $request->validate([
            'estado' => 'required|in:Pausada,En Progreso'
        ]);

        $sesionActiva = session('sesionPomodoroActiva');
        if (!$sesionActiva) {
            return redirect()->back()->withErrors(['error' => 'No hay sesión activa']);
        }

        $sesion = SesionPomodoro::find($sesionActiva['idSesion']);
        if ($sesion) {
            $sesion->update([
                'estadoSesion' => $request->estado
            ]);

            if ($request->estado === 'En Progreso' && $sesion->idTarea) {
                $tarea = Tarea::find($sesion->idTarea);
                if ($tarea && $tarea->estadoTarea === 'Pendiente') {
                    $tarea->update(['estadoTarea' => 'En Progreso']);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function finalizarSesion(Request $request)
    {
        $request->validate([
            'estado' => 'required|in:Completada,Cancelada',
            'minutosTrabajados' => 'nullable|integer|min:0',
            'marcarTareaCompletada' => 'nullable|boolean'
        ]);

        $sesionActiva = session('sesionPomodoroActiva');
        $estadoFinal = 'Cancelada';
        if ($sesionActiva) {
            $sesion = SesionPomodoro::find($sesionActiva['idSesion']);
            if ($sesion) {
                if ($request->minutosTrabajados && $request->minutosTrabajados > 0) {
                    $sesion->increment('tiempoTrabajoTotalMinutos', $request->minutosTrabajados);
                    $estadisticaService = new EstadisticaService();
                    $estadisticaService->registrarTiempoTrabajo(Auth::user()->idUsuario, $request->minutosTrabajados);
                }

                // Evaluar si se especificó explícitamente el estado de la tarea (Paso 2 del flujo)
                if ($request->has('marcarTareaCompletada') && !is_null($request->marcarTareaCompletada)) {
                    $estadoFinal = $request->boolean('marcarTareaCompletada') ? 'Completada' : 'Cancelada';

                    $sesion->update([
                        'estadoSesion' => $estadoFinal
                    ]);

                    if ($sesion->idTarea) {
                        $tarea = Tarea::find($sesion->idTarea);
                        if ($tarea) {
                            if ($estadoFinal === 'Completada') {
                                if ($tarea->estadoTarea !== 'Completado') {
                                    $tarea->update(['estadoTarea' => 'Completado']);
                                    $estadisticaService = new EstadisticaService();
                                    $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);
                                }
                            } else {
                                if ($tarea->estadoTarea === 'Pendiente') {
                                    $tarea->update(['estadoTarea' => 'En Progreso']);
                                }
                            }
                        }
                    }

                    $estadisticaService = new EstadisticaService();
                    if ($estadoFinal === 'Cancelada') {
                        $estadisticaService->registrarCancelacion(Auth::user()->idUsuario);
                    } elseif ($estadoFinal === 'Completada') {
                        $estadisticaService->evaluarRachaAlCompletarSesion(Auth::user()->idUsuario, $sesion->configuracionPomodoro->duracionSesion);
                    }
                } else {
                    // Si la sesión ya estaba marcada como 'Completada' en la base de datos,
                    // mantenemos 'Completada' independientemente de cualquier otra cosa para evitar duplicar estadísticas
                    if ($sesion->estadoSesion === 'Completada') {
                        $estadoFinal = 'Completada';
                    } else {
                        // Determinar el estado final real basado en ciclosCompletados y ciclosObjetivo de la BD (Regla 2.1)
                        $estadoFinal = $sesion->ciclosCompletados >= $sesion->ciclosObjetivo ? 'Completada' : 'Cancelada';

                        $sesion->update([
                            'estadoSesion' => $estadoFinal
                        ]);

                        if ($estadoFinal === 'Completada' && $sesion->idTarea) {
                            $tarea = Tarea::find($sesion->idTarea);
                            if ($tarea && $tarea->estadoTarea !== 'Completado') {
                                $tarea->update(['estadoTarea' => 'Completado']);
                                $estadisticaService = new EstadisticaService();
                                $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);
                            }
                        }

                        $estadisticaService = new EstadisticaService();
                        if ($estadoFinal === 'Cancelada') {
                            $estadisticaService->registrarCancelacion(Auth::user()->idUsuario);
                        } elseif ($estadoFinal === 'Completada') {
                            $estadisticaService->evaluarRachaAlCompletarSesion(Auth::user()->idUsuario, $sesion->configuracionPomodoro->duracionSesion);
                        }
                    }
                }
            }
        }

        session()->forget('sesionPomodoroActiva');

        return redirect()->route('pomodoro.index')
            ->with('success', $estadoFinal === 'Completada' ? 'Sesión Pomodoro finalizada' : 'Sesión cancelada. ¡No te preocupes! Cada pequeño paso cuenta, vuelve a intentarlo cuando estés listo. 💪');
    }



    public function configIndex()
    {
        $configs = ConfiguracionPomodoro::where('idUsuario', Auth::user()->idUsuario)
            ->orderBy('fechaCreacionConfiguracion', 'desc')
            ->get();

        return Inertia::render('configuracion-pomodoro/Index', [
            'configs' => $configs
        ]);
    }

    public function configStore(Request $request)
    {
        $request->validate([
            'duracionSesion' => 'required|integer|min:1|max:120',
            'duracionDescansoCorto' => 'required|integer|min:1|max:30',
            'duracionDescansoLargo' => 'required|integer|min:5|max:60',
            'sesionesPrevioDescansoLargo' => 'required|integer|min:2|max:10',
        ]);

        ConfiguracionPomodoro::create([
            'idUsuario' => Auth::user()->idUsuario,
            'duracionSesion' => $request->duracionSesion,
            'duracionDescansoCorto' => $request->duracionDescansoCorto,
            'duracionDescansoLargo' => $request->duracionDescansoLargo,
            'sesionesPrevioDescansoLargo' => $request->sesionesPrevioDescansoLargo,
        ]);

        return redirect()->back()->with('success', 'Configuración creada');
    }

    public function configUpdate(Request $request, $id)
    {
        $config = ConfiguracionPomodoro::where('idConfiguracionPomodoro', $id)
            ->where('idUsuario', Auth::user()->idUsuario)
            ->firstOrFail();

        $request->validate([
            'duracionSesion' => 'required|integer|min:1|max:120',
            'duracionDescansoCorto' => 'required|integer|min:1|max:30',
            'duracionDescansoLargo' => 'required|integer|min:5|max:60',
            'sesionesPrevioDescansoLargo' => 'required|integer|min:2|max:10',
        ]);

        $config->update($request->only([
            'duracionSesion', 'duracionDescansoCorto', 'duracionDescansoLargo', 'sesionesPrevioDescansoLargo'
        ]));

        return redirect()->back()->with('success', 'Configuración actualizada');
    }

    public function configDestroy($id)
    {
        $config = ConfiguracionPomodoro::where('idConfiguracionPomodoro', $id)
            ->where('idUsuario', Auth::user()->idUsuario)
            ->firstOrFail();

        $config->delete();

        return redirect()->back()->with('success', 'Configuración eliminada');
    }
}
