<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaEstudio extends Model
{
    use HasFactory;
    
    protected $table = 'SalaEstudio';
    protected $primaryKey = 'idSalaEstudio';
    public $timestamps = false; 

    protected $fillable = [
        'idPerfil',
        'idUsuarioCreador',
        'googleMeetUrl',
        'googleMeetId',
        'tituloEvento',
        'estado',
        'fechaCreacion',
        'fechaFin'
    ];

    protected $casts = [
        'fechaCreacion' => 'datetime',
        'fechaFin' => 'datetime',
    ];

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'idPerfil', 'idPerfil');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'idUsuarioCreador', 'idUsuario');
    }

    public function presencias()
    {
        return $this->hasMany(PresenciaSalaEstudio::class, 'idPerfil', 'idPerfil');
    }
}
