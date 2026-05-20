<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apunte extends Model
{
    protected $table = 'Apunte';
    protected $primaryKey = 'idApunte';
    public $timestamps = false;

    protected $fillable = [
        'idPerfil',
        'tituloApunte',
        'contenidoApunte',
        'fechaCreacion'
    ];

    protected $casts = [
        'fechaCreacion' => 'datetime'
    ];

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'idPerfil', 'idPerfil');
    }
}
