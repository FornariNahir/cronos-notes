<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresenciaSalaEstudio extends Model
{
    protected $table = 'PresenciaSalaEstudio';
    public $incrementing = false; 
    protected $primaryKey = ['idPerfil', 'idUsuario'];
    public $timestamps = false;

    protected $fillable = [
        'idPerfil', 'idUsuario', 'estadoUsuario', 'ultimoPing'
    ];
}
