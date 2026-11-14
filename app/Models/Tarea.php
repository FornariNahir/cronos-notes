<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    protected $table = 'Tarea';

    protected $primaryKey = 'idTarea';

    protected $fillable = [
        'idPerfil',
        'tituloTarea',
        'descripcionTarea',
        'fechaInicioTarea',
        'fechaFinTarea',
        'fechaLimite',
        'estadoTarea',
        'prioridadTarea',
        'estimacionEsfuerzo'
    ];

    protected $casts = [
        'estimacionEsfuerzo' => 'integer',
        'fechaInicioTarea' => 'date',
        'fechaFinTarea' => 'date',
        'fechaLimite' => 'date'
    ];

    // Relación Inversa: Una Tarea PERTENECE A un Perfil (belongsTo)
    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'idPerfil', 'idPerfil');
    }

    public function sesionesPomodoro()
    {
        return $this->hasMany(SesionPomodoro::class, 'idTarea', 'idTarea');
    }

    public function subtareas()
    {
        return $this->hasMany(Subtarea::class, 'idTarea', 'idTarea')->orderBy('orden', 'asc');
    }

    /**
     * Recalcula la estimación de esfuerzo y el estado de la tarea en base a sus subtareas.
     */
    public function recalcularProgreso(): void
    {
        $subtareas = $this->subtareas()->get();
        $total = $subtareas->count();

        if ($total > 0) {
            $this->estimacionEsfuerzo = (int) $subtareas->sum('estimacionEsfuerzo');

            $completadas = $subtareas->where('completada', true)->count();

            if ($completadas === $total) {
                $this->estadoTarea = 'Completado';
                $this->fechaFinTarea = now()->toDateString();
            } elseif ($completadas > 0) {
                $this->estadoTarea = 'En Progreso';
                $this->fechaFinTarea = null;
            } else {
                if ($this->estadoTarea === 'Completado') {
                    $this->estadoTarea = 'Pendiente';
                }
                $this->fechaFinTarea = null;
            }

            $this->save();
        }
    }
}
