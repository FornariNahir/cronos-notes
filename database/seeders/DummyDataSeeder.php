<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\ConfiguracionPomodoro;
use App\Models\SesionPomodoro;
use App\Models\Racha;
use App\Models\Estadistica;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener o crear usuario
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'nombre' => 'Usuario',
                'apellido' => 'Prueba',
                'email' => 'prueba@cronos.com',
                'password' => Hash::make('password')
            ]);
        }

        $idUsuario = $user->idUsuario ?? $user->id;

        // Limpiar datos anteriores del usuario para no duplicar
        Perfil::where('idUsuario', $idUsuario)->delete();
        ConfiguracionPomodoro::where('idUsuario', $idUsuario)->delete();
        Racha::where('idUsuario', $idUsuario)->delete();
        Estadistica::where('idUsuario', $idUsuario)->delete();

        // 2. Crear Configuración Pomodoro
        $config = ConfiguracionPomodoro::create([
            'idUsuario' => $idUsuario,
            'duracionSesion' => 25,
            'duracionDescansoCorto' => 5,
            'duracionDescansoLargo' => 15,
            'sesionesPrevioDescansoLargo' => 4
        ]);

        // 3. Crear Perfiles
        $perfilUniversidad = Perfil::create(['idUsuario' => $idUsuario, 'tituloPerfil' => 'Universidad', 'descripcionPerfil' => 'Tareas de la facultad']);
        $perfilTrabajo = Perfil::create(['idUsuario' => $idUsuario, 'tituloPerfil' => 'Trabajo', 'descripcionPerfil' => 'Proyectos laborales']);

        // 4. Crear Tareas
        // Completadas a tiempo
        for ($i = 0; $i < 15; $i++) {
            Tarea::create([
                'idPerfil' => $perfilUniversidad->idPerfil,
                'tituloTarea' => "Tarea Uni $i",
                'fechaInicioTarea' => Carbon::now()->subDays(rand(1, 10)),
                'fechaLimite' => Carbon::now()->subDays(rand(1, 5)),
                'fechaFinTarea' => Carbon::now()->subDays(rand(6, 10)),
                'estadoTarea' => 'Completado',
                'prioridadTarea' => 'Alta'
            ]);
        }

        // Completadas con retraso
        for ($i = 0; $i < 5; $i++) {
            Tarea::create([
                'idPerfil' => $perfilTrabajo->idPerfil,
                'tituloTarea' => "Tarea Trabajo Retrasada $i",
                'fechaInicioTarea' => Carbon::now()->subDays(15),
                'fechaLimite' => Carbon::now()->subDays(10),
                'fechaFinTarea' => Carbon::now()->subDays(2),
                'estadoTarea' => 'Completado',
                'prioridadTarea' => 'Media'
            ]);
        }

        // Pendientes
        for ($i = 0; $i < 10; $i++) {
            Tarea::create([
                'idPerfil' => $perfilUniversidad->idPerfil,
                'tituloTarea' => "Tarea Pendiente $i",
                'fechaInicioTarea' => Carbon::now()->subDays(1),
                'fechaLimite' => Carbon::now()->addDays(5),
                'estadoTarea' => 'Pendiente',
                'prioridadTarea' => 'Baja'
            ]);
        }

        // 5. Crear Sesiones Pomodoro históricas (últimos 7 días)
        $minutosTotales = 0;
        for ($dia = 7; $dia >= 0; $dia--) {
            // Entre 1 y 4 pomodoros por día
            $numPomodoros = rand(1, 4);
            for ($p = 0; $p < $numPomodoros; $p++) {
                $minutos = rand(20, 25);
                $minutosTotales += $minutos;
                SesionPomodoro::create([
                    'idConfiguracionPomodoro' => $config->idConfiguracionPomodoro,
                    'idTarea' => null, // Opcional
                    'fechaCreacionSesion' => Carbon::now()->subDays($dia)->format('Y-m-d H:i:s'),
                    'tiempoTrabajoTotalMinutos' => $minutos,
                    'estadoSesion' => 'Completada',
                    'ciclosObjetivo' => 4,
                    'ciclosCompletados' => 1
                ]);
            }
        }

        // 6. Crear Rachas
        Racha::create([
            'idUsuario' => $idUsuario,
            'fechaInicioRacha' => Carbon::now()->subDays(7),
            'fechaFinRacha' => Carbon::now(),
            'rachaActual' => 8,
            'rachaActiva' => 1
        ]);

        // 7. Crear Estadística Base
        Estadistica::create([
            'idUsuario' => $idUsuario,
            'tareasTotales' => 30, // 15 + 5 + 10
            'tiempoTotalPomodoro' => $minutosTotales,
            'rachaMasLarga' => 12,
            'rachaActual' => 8,
            'sesionesCanceladas' => 2,
            'horasConcentracionDiaria' => 2
        ]);
    }
}
