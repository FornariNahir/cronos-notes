<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilCompartido extends Model
{
    protected $table = 'PerfilCompartido';
    protected $primaryKey = ['idUsuario', 'idPerfil'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'idPerfil',
        'permiso'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'idPerfil', 'idPerfil');
    }
}
