<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionPomodoro extends Model
{
    protected $table = 'ConfiguracionPomodoro';
    protected $primaryKey = 'idConfiguracionPomodoro';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'duracionSesion',
        'duracionDescansoCorto',
        'duracionDescansoLargo',
        'sesionesPrevioDescansoLargo',
        'fechaCreacionConfiguracion'
    ];

    protected $casts = [
        'duracionSesion' => 'integer',
        'duracionDescansoCorto' => 'integer',
        'duracionDescansoLargo' => 'integer',
        'sesionesPrevioDescansoLargo' => 'integer',
        'fechaCreacionConfiguracion' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    public function sesiones()
    {
        return $this->hasMany(SesionPomodoro::class, 'idConfiguracionPomodoro', 'idConfiguracionPomodoro');
    }
}
