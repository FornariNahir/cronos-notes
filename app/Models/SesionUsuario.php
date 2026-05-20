<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionUsuario extends Model
{
    protected $table = 'SesionUsuario';
    protected $primaryKey = 'idSesionUsuario';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'tokenSesionUsuario',
        'fechaAlta',
        'fechaCaducidad',
        'activa'
    ];

    protected $casts = [
        'activa' => 'boolean',
        'fechaAlta' => 'datetime',
        'fechaCaducidad' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
