<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Services\EstadisticaService;

class TareaController extends Controller
{
    public function index(Request $request)
    {
        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->route('perfiles.index')
                ->with('error', 'Selecciona un perfil primero');
        }

        $mostrarCompletadas = $request->boolean('completadas');

        $query = Tarea::where('idPerfil', $perfilActivoId);

        if ($mostrarCompletadas) {
            $query->where('estadoTarea', 'Completado');
        } else {
            $query->where('estadoTarea', '!=', 'Completado');
        }

        $tareas = $query->orderBy('fechaLimite', 'asc')->get();

        $perfilActivo = Perfil::find($perfilActivoId);

        return Inertia::render('Tasks/Index', [
            'tareas' => $tareas,
            'perfilActivo' => $perfilActivo,
            'mostrarCompletadas' => $mostrarCompletadas
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tituloTarea' => 'required|string|max:45',
            'descripcionTarea' => 'nullable|string|max:200',
            'fechaLimite' => 'required|date|after_or_equal:today',
            'prioridadTarea' => 'required|in:Baja,Media,Alta',
            'estimacionEsfuerzo' => 'nullable|integer|min:1'
        ]);

        $perfilActivoId = session('perfilActivo');
        if (!$perfilActivoId) {
            return redirect()->back()->with('error', 'No hay perfil activo');
        }

        Tarea::create([
            'idPerfil' => $perfilActivoId,
            'tituloTarea' => $request->tituloTarea,
            'descripcionTarea' => $request->descripcionTarea,
            'fechaInicioTarea' => now()->format('Y-m-d'),
            'fechaLimite' => $request->fechaLimite,
            'prioridadTarea' => $request->prioridadTarea,
            'estadoTarea' => 'Pendiente',
            'estimacionEsfuerzo' => $request->estimacionEsfuerzo
        ]);

        return redirect()->back()->with('success', 'Tarea creada correctamente');
    }

    public function show($id)
    {
        $perfilActivoId = session('perfilActivo');
        $tarea = Tarea::where('idTarea', $id)
            ->where('idPerfil', $perfilActivoId)
            ->firstOrFail();

        return response()->json($tarea);
    }

    public function update(Request $request, $id)
    {
        $tarea = Tarea::where('idTarea', $id)
            ->where('idPerfil', session('perfilActivo'))
            ->firstOrFail();

        $request->validate([
            'tituloTarea' => 'required|string|max:45',
            'descripcionTarea' => 'nullable|string|max:200',
            'fechaLimite' => 'required|date|after_or_equal:' . $tarea->fechaInicioTarea->format('Y-m-d'),
            'prioridadTarea' => 'required|in:Baja,Media,Alta',
            'estadoTarea' => 'required|in:Pendiente,En Progreso,Completado',
            'estimacionEsfuerzo' => 'nullable|integer|min:1'
        ]);

        if ($tarea->estadoTarea === 'Completado' && $request->estadoTarea !== 'Completado') {
            return redirect()->back()->with('error', 'No se puede cambiar el estado de una tarea ya completada');
        }

        $data = [
            'tituloTarea' => $request->tituloTarea,
            'descripcionTarea' => $request->descripcionTarea,
            'fechaLimite' => $request->fechaLimite,
            'prioridadTarea' => $request->prioridadTarea,
            'estadoTarea' => $request->estadoTarea,
            'estimacionEsfuerzo' => $request->estimacionEsfuerzo
        ];

        if ($request->estadoTarea === 'Completado') {
            $data['fechaFinTarea'] = now()->format('Y-m-d');
            
            $estadisticaService = new EstadisticaService();
            $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);
        }

        $tarea->update($data);

        return redirect()->back()->with('success', 'Tarea modificada correctamente');
    }

    public function completar($id)
    {
        $tarea = Tarea::where('idTarea', $id)
            ->where('idPerfil', session('perfilActivo'))
            ->firstOrFail();

        $tarea->update([
            'estadoTarea' => 'Completado',
            'fechaFinTarea' => now()->format('Y-m-d')
        ]);

        $estadisticaService = new EstadisticaService();
        $estadisticaService->sumarTareaCompletada(Auth::user()->idUsuario);

        return redirect()->back()->with('success', '¡Tarea completada!');
    }

    public function destroy($id)
    {
        $tarea = Tarea::where('idTarea', $id)
            ->where('idPerfil', session('perfilActivo'))
            ->firstOrFail();

        $tarea->delete();

        return redirect()->back()->with('success', 'Tarea eliminada correctamente');
    }
}
