<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApunteAudio extends Model
{
    protected $table = 'ApunteAudio';
    protected $primaryKey = 'idApunteAudio';
    public $timestamps = false;

    protected $fillable = [
        'idApunte',
        'rutaAudio',
        'fechaCreacion',
        'transcripcion',
        'resumen_ia',
        'estado',
        'error_mensaje',
    ];

    protected $casts = [
        'fechaCreacion' => 'datetime',
        'resumen_ia' => 'array',
    ];

    public function apunte()
    {
        return $this->belongsTo(Apunte::class, 'idApunte', 'idApunte');
    }
}
