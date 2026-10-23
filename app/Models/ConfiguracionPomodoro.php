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
        'sonido_activado',
        'volumen_sonido',
        'notificaciones_navegador',
        'fechaCreacionConfiguracion'
    ];

    protected $casts = [
        'duracionSesion' => 'integer',
        'duracionDescansoCorto' => 'integer',
        'duracionDescansoLargo' => 'integer',
        'sesionesPrevioDescansoLargo' => 'integer',
        'sonido_activado' => 'boolean',
        'volumen_sonido' => 'integer',
        'notificaciones_navegador' => 'boolean',
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
