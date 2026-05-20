<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $table = 'Usuario';
    // 1. Le decimos a Laravel cuál es nuestra clave primaria real
    protected $primaryKey = 'idUsuario';

    // 2. Definimos la relación: Un Usuario tiene MUCHOS Perfiles (hasMany)
    public function perfiles()
    {
        // Parámetros: Modelo a conectar, Clave foránea en Perfiles, Clave local en User
        return $this->hasMany(Perfil::class, 'idUsuario', 'idUsuario');
    }

    public function configuracionPomodoro()
    {
        return $this->hasOne(ConfiguracionPomodoro::class, 'idUsuario', 'idUsuario');
    }

    public function configuracionAmbiente()
    {
        return $this->hasOne(ConfiguracionAmbiente::class, 'idUsuario', 'idUsuario');
    }

    public function estadistica()
    {
        return $this->hasOne(Estadistica::class, 'idUsuario', 'idUsuario');
    }

    public function rachas()
    {
        return $this->hasMany(Racha::class, 'idUsuario', 'idUsuario');
    }

    public function sesionesUsuario()
    {
        return $this->hasMany(SesionUsuario::class, 'idUsuario', 'idUsuario');
    }

    public function recuperacionesPassword()
    {
        return $this->hasMany(RecuperacionPassword::class, 'idUsuario', 'idUsuario');
    }

    public function integracionExterna()
    {
        return $this->hasMany(IntegracionExterna::class, 'idUsuario', 'idUsuario');
    }

    public function perfilesCompartidos()
    {
        return $this->belongsToMany(Perfil::class, 'PerfilCompartido', 'idUsuario', 'idPerfil')
            ->withPivot('permiso');
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
