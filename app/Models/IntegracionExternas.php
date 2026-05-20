<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegracionExterna extends Model
{
    protected $table = 'IntegracionExterna';
    protected $primaryKey = 'idIntegracionExterna';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'plataforma',
        'tokenAcceso',
        'tokenNuevo'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
