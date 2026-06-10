<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    // Opcional, pero buena práctica para evitar que Laravel busque una tabla "perfils"
    protected $table = 'Perfil';

    protected $primaryKey = 'idPerfil';

    // Campos que permitimos guardar en la base de datos
    protected $fillable = [
        'idUsuario',
        'tituloPerfil',
        'descripcionPerfil',
        'iconoPerfil'
    ];

    // Relación Inversa: Un Perfil PERTENECE A un Usuario (belongsTo)
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    // Relación Directa: Un Perfil tiene MUCHAS Tareas (hasMany)
    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'idPerfil', 'idPerfil');
    }

    public function apuntes()
    {
        return $this->hasMany(Apunte::class, 'idPerfil', 'idPerfil');
    }

    public function usuariosCompartidos()
    {
        return $this->belongsToMany(User::class, 'PerfilCompartido', 'idPerfil', 'idUsuario')
            ->withPivot('permiso', 'compartidoPor', 'fechaCompartido');
    }

    public function invitaciones()
    {
        return $this->hasMany(InvitacionPerfil::class, 'idPerfil', 'idPerfil');
    }
}
