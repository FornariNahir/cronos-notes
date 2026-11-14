<?php

namespace App\Http\Controllers;

use App\Models\Subtarea;
use App\Models\Tarea;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubtareaController extends Controller
{
    use AuthorizesRequests;

    private function verificarAccesoPerfil($idPerfil, string $permiso = 'ver'): Perfil
    {
        $perfil = Perfil::findOrFail($idPerfil);
        $this->authorize($permiso, $perfil);
        return $perfil;
    }

    public function store(Request $request, $idTarea)
    {
        $tarea = Tarea::findOrFail($idTarea);
        $this->verificarAccesoPerfil($tarea->idPerfil, 'crear');

        $request->validate([
            'titulo' => 'required|string|max:150',
            'estimacionEsfuerzo' => 'nullable|integer|min:1|max:4',
        ]);

        $maxOrden = Subtarea::where('idTarea', $tarea->idTarea)->max('orden') ?? 0;

        $subtarea = Subtarea::create([
            'idTarea' => $tarea->idTarea,
            'titulo' => $request->titulo,
            'completada' => false,
            'estimacionEsfuerzo' => $request->estimacionEsfuerzo ?? 1,
            'orden' => $maxOrden + 1,
        ]);

        $tarea->recalcularProgreso();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subtarea' => $subtarea,
                'tarea' => $tarea->fresh(['subtareas']),
            ], 201);
        }

        return redirect()->back()->with('success', 'Subtarea agregada correctamente.');
    }

    public function update(Request $request, $idSubtarea)
    {
        $subtarea = Subtarea::findOrFail($idSubtarea);
        $tarea = $subtarea->tarea;
        $this->verificarAccesoPerfil($tarea->idPerfil, 'modificar');

        $request->validate([
            'titulo' => 'required|string|max:150',
            'estimacionEsfuerzo' => 'nullable|integer|min:1|max:4',
        ]);

        $subtarea->update([
            'titulo' => $request->titulo,
            'estimacionEsfuerzo' => $request->estimacionEsfuerzo ?? $subtarea->estimacionEsfuerzo,
        ]);

        $tarea->recalcularProgreso();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subtarea' => $subtarea,
                'tarea' => $tarea->fresh(['subtareas']),
            ]);
        }

        return redirect()->back()->with('success', 'Subtarea actualizada correctamente.');
    }

    public function toggle(Request $request, $idSubtarea)
    {
        $subtarea = Subtarea::findOrFail($idSubtarea);
        $tarea = $subtarea->tarea;
        $this->verificarAccesoPerfil($tarea->idPerfil, 'modificar');

        $subtarea->completada = !$subtarea->completada;
        $subtarea->save();

        $tarea->recalcularProgreso();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subtarea' => $subtarea,
                'tarea' => $tarea->fresh(['subtareas']),
            ]);
        }

        return redirect()->back()->with('success', 'Estado de subtarea actualizado.');
    }

    public function destroy(Request $request, $idSubtarea)
    {
        $subtarea = Subtarea::findOrFail($idSubtarea);
        $tarea = $subtarea->tarea;
        $this->verificarAccesoPerfil($tarea->idPerfil, 'modificar');

        $subtarea->delete();

        $tarea->recalcularProgreso();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tarea' => $tarea->fresh(['subtareas']),
            ]);
        }

        return redirect()->back()->with('success', 'Subtarea eliminada correctamente.');
    }
}
