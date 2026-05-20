<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionAmbiente extends Model
{
    protected $table = 'ConfiguracionAmbiente';
    protected $primaryKey = 'idConfiguracionAmbiente';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'modoZen',
        'modoOscuro'
    ];

    protected $casts = [
        'modoZen' => 'boolean',
        'modoOscuro' => 'boolean'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
