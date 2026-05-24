<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'fechaLimite' => 'required|date',
            'prioridadTarea' => 'required|in:Baja,Media,Alta'
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
            'estadoTarea' => 'Pendiente'
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
            'fechaLimite' => 'required|date',
            'prioridadTarea' => 'required|in:Baja,Media,Alta',
            'estadoTarea' => 'required|in:Pendiente,En Progreso,Completado'
        ]);

        $data = [
            'tituloTarea' => $request->tituloTarea,
            'descripcionTarea' => $request->descripcionTarea,
            'fechaLimite' => $request->fechaLimite,
            'prioridadTarea' => $request->prioridadTarea,
            'estadoTarea' => $request->estadoTarea
        ];

        if ($request->estadoTarea === 'Completado') {
            $data['fechaFinTarea'] = now()->format('Y-m-d');
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
