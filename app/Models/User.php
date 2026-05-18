<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // 1. Le decimos a Laravel cuál es nuestra clave primaria real
    protected $primaryKey = 'idUsuario';

    // 2. Definimos la relación: Un Usuario tiene MUCHOS Perfiles (hasMany)
    public function perfiles()
    {
        // Parámetros: Modelo a conectar, Clave foránea en Perfiles, Clave local en User
        return $this->hasMany(Perfil::class, 'idUsuario', 'idUsuario');
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'ultimoAcceso', 
        'usuarioConectado'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
