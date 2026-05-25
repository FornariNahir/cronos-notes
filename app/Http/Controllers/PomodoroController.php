<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionPomodoro;
use App\Models\SesionPomodoro;
use App\Models\Perfil;
use App\Models\Tarea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PomodoroController extends Controller
{
    public function index()
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $configs = ConfiguracionPomodoro::where('idUsuario', Auth::user()->idUsuario)->get();
        $tareas = Tarea::where('idPerfil', $perfilActivoId)
            ->where('estadoTarea', '!=', 'Completado')
            ->get();

        $sesionActiva = session('sesionPomodoroActiva');

        return Inertia::render('Pomodoro/Index', [
            'configs' => $configs,
            'tareas' => $tareas,
            'perfilActivo' => Perfil::find($perfilActivoId),
            'sesionActiva' => $sesionActiva
        ]);
    }

    public function iniciarSesion(Request $request)
    {
        $request->validate([
            'idConfiguracionPomodoro' => 'required|exists:ConfiguracionPomodoro,idConfiguracionPomodoro',
            'idTarea' => 'nullable|exists:Tarea,idTarea',
            'sonidoSeleccionado' => 'nullable|string',
            'volumenSonido' => 'nullable|integer|min:0|max:100'
        ]);

        $config = ConfiguracionPomodoro::findOrFail($request->idConfiguracionPomodoro);
        $tarea = $request->idTarea ? Tarea::find($request->idTarea) : null;

        $sesion = SesionPomodoro::create([
            'idConfiguracionPomodoro' => $config->idConfiguracionPomodoro,
            'idTarea' => $request->idTarea,
            'estadoSesion' => 'En Progreso',
            'ciclosObjetivo' => $config->sesionesPrevioDescansoLargo,
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
                'tituloTarea' => $tarea?->tituloTarea ?? 'Tarea sin título',
                'descripcionTarea' => $tarea?->descripcionTarea ?? 'Sin descripción',
                'duracionSesion' => $config->duracionSesion,
                'duracionDescansoCorto' => $config->duracionDescansoCorto,
                'duracionDescansoLargo' => $config->duracionDescansoLargo,
                'sesionesPrevioDescansoLargo' => $config->sesionesPrevioDescansoLargo,
                'sonidoSeleccionado' => $sonido,
                'volumenSonido' => $volumen
            ]
        ]);

        return redirect()->route('pomodoro.index');
    }

    public function registrarTrabajo(Request $request)
    {
        $request->validate([
            'minutosTrabajados' => 'nullable|integer|min:1'
        ]);

        $sesionActiva = session('sesionPomodoroActiva');
        if (!$sesionActiva) {
            return response()->json(['error' => 'No hay sesión activa'], 400);
        }

        $sesion = SesionPomodoro::find($sesionActiva['idSesion']);
        if ($sesion) {
            $minutos = $request->minutosTrabajados ?? $sesionActiva['duracionSesion'];
            $sesion->increment('tiempoTrabajoTotalMinutos', $minutos);
            $sesion->increment('ciclosCompletados');
        }

        return response()->json(['success' => true]);
    }

    public function finalizarSesion(Request $request)
    {
        $sesionActiva = session('sesionPomodoroActiva');
        if ($sesionActiva) {
            $sesion = SesionPomodoro::find($sesionActiva['idSesion']);
            if ($sesion) {
                $sesion->update(['estadoSesion' => 'Completada']);
            }
        }

        session()->forget('sesionPomodoroActiva');

        return redirect()->route('pomodoro.index')
            ->with('success', 'Sesión Pomodoro finalizada');
    }

    public function configIndex()
    {
        $configs = ConfiguracionPomodoro::where('idUsuario', Auth::user()->idUsuario)
            ->orderBy('fechaCreacionConfiguracion', 'desc')
            ->get();

        return Inertia::render('PomodoroConfig/Index', [
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
