<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Racha extends Model
{
    protected $table = 'Racha';
    protected $primaryKey = 'idRacha';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'fechaInicioRacha',
        'fechaFinRacha',
        'rachaActual',
        'rachaActiva'
    ];

    protected $casts = [
        'rachaActual' => 'integer',
        'rachaActiva' => 'boolean',
        'fechaInicioRacha' => 'date',
        'fechaFinRacha' => 'date'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
