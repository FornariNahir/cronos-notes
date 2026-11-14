<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtarea extends Model
{
    protected $table = 'subtareas';

    protected $primaryKey = 'idSubtarea';

    protected $fillable = [
        'idTarea',
        'titulo',
        'completada',
        'estimacionEsfuerzo',
        'orden',
    ];

    protected $casts = [
        'completada' => 'boolean',
        'estimacionEsfuerzo' => 'integer',
        'orden' => 'integer',
    ];

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'idTarea', 'idTarea');
    }
}
