<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecuperacionPassword extends Model
{
    protected $table = 'RecuperacionPassword';
    protected $primaryKey = 'idRecuperacionPassword';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'tokenRecuperacion',
        'fechaGeneracion',
        'utilizado'
    ];

    protected $casts = [
        'utilizado' => 'boolean',
        'fechaGeneracion' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
