<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionPomodoro extends Model
{
    protected $table = 'SesionPomodoro';
    protected $primaryKey = 'idSesionPomodoro';
    public $timestamps = false;

    protected $fillable = [
        'idConfiguracionPomodoro',
        'idTarea',
        'fechaCreacionSesion',
        'tiempoTrabajoTotalMinutos',
        'estadoSesion',
        'ciclosObjetivo',
        'ciclosCompletados'
    ];

    protected $casts = [
        'tiempoTrabajoTotalMinutos' => 'integer',
        'ciclosObjetivo' => 'integer',
        'ciclosCompletados' => 'integer',
        'fechaCreacionSesion' => 'datetime'
    ];

    public function configuracionPomodoro()
    {
        return $this->belongsTo(ConfiguracionPomodoro::class, 'idConfiguracionPomodoro', 'idConfiguracionPomodoro');
    }

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'idTarea', 'idTarea');
    }
}
