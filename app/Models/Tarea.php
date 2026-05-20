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
        'prioridadTarea'
    ];

    // Relación Inversa: Una Tarea PERTENECE A un Perfil (belongsTo)
    public function perfil() 
    {
        return $this->belongsTo(Perfil::class, 'idPerfil', 'idPerfil');
    }
}
