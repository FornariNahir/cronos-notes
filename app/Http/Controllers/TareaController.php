<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use Illuminate\Http\Request;

class TareaController extends Controller
{
    // Reemplaza a agregarTarea()
    public function store(Request $request)
    {
        $request->validate([
            'idPerfil' => 'required|exists:perfiles,idPerfil',
            'tituloTarea' => 'required|string|max:255',
            'descripcionTarea' => 'required|string',
            'fechaLimite' => 'required|date',
            'prioridadTarea' => 'required|string'
        ]);

        Tarea::create([
            'idPerfil' => $request->idPerfil,
            'titulo' => $request->tituloTarea,
            'descripcion' => $request->descripcionTarea,
            'fecha_limite' => $request->fechaLimite,
            'prioridad' => $request->prioridadTarea,
            'estado' => 'pendiente' // Estado inicial
        ]);

        return redirect()->back()->with('success', 'Tarea agregada correctamente');
    }

    // Reemplaza a modificarTarea()
    public function update(Request $request, $id)
    {
        $tarea = Tarea::findOrFail($id);

        $request->validate([
            'tituloTarea' => 'required|string|max:255',
            'descripcionTarea' => 'required|string',
            'fechaLimite' => 'required|date',
            'prioridadTarea' => 'required|string',
            'estadoTarea' => 'required|string'
        ]);

        $tarea->update([
            'titulo' => $request->tituloTarea,
            'descripcion' => $request->descripcionTarea,
            'fecha_limite' => $request->fechaLimite,
            'estado' => $request->estadoTarea,
            'prioridad' => $request->prioridadTarea
        ]);

        return redirect()->back()->with('success', 'Tarea modificada correctamente');
    }

    // Reemplaza a tareaTerminada()
    public function completar($id)
    {
        $tarea = Tarea::findOrFail($id);
        $tarea->update(['estado' => 'terminada']);

        return redirect()->back()->with('success', '¡Tarea terminada!');
    }

    // Reemplaza a eliminarTarea()
    public function destroy($id)
    {
        $tarea = Tarea::findOrFail($id);
        $tarea->delete();

        return redirect()->back()->with('success', 'Tarea eliminada correctamente');
    }
}