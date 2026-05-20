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
}
