<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estadistica extends Model
{
    protected $table = 'Estadistica';
    protected $primaryKey = 'idEstadistica';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'tareasTotales',
        'tiempoTotalPomodoro',
        'rachaMasLarga',
        'rachaActual',
        'sesionesCanceladas',
        'horasConcentracionDiaria'
    ];

    protected $casts = [
        'tareasTotales' => 'integer',
        'tiempoTotalPomodoro' => 'integer',
        'rachaMasLarga' => 'integer',
        'rachaActual' => 'integer',
        'sesionesCanceladas' => 'integer',
        'horasConcentracionDiaria' => 'decimal:2'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
