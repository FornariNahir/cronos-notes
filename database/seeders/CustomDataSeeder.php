<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Perfil;
use App\Models\Tarea;
use App\Models\ConfiguracionPomodoro;
use App\Models\SesionPomodoro;
use App\Models\Estadistica;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Get or create the user
        $user = User::firstOrCreate(
            ['email' => 'fornarinahir21@gmail.com'],
            [
                'nombre' => 'Nahir', 
                'apellido' => 'Fornari', 
                'password' => Hash::make('password123')
            ]
        );

        // 2. Create some profiles
        $perfilTrabajo = Perfil::firstOrCreate(
            ['idUsuario' => $user->idUsuario, 'tituloPerfil' => 'Trabajo']
        );
        $perfilEstudio = Perfil::firstOrCreate(
            ['idUsuario' => $user->idUsuario, 'tituloPerfil' => 'Estudio']
        );
        $perfilPersonal = Perfil::firstOrCreate(
            ['idUsuario' => $user->idUsuario, 'tituloPerfil' => 'Personal']
        );

        // 3. Create a config pomodoro
        $config = ConfiguracionPomodoro::firstOrCreate(
            ['idUsuario' => $user->idUsuario],
            [
                'duracionSesion' => 25, 
                'duracionDescansoCorto' => 5, 
                'duracionDescansoLargo' => 15, 
                'sesionesPrevioDescansoLargo' => 4
            ]
        );

        // 4. Create tasks for each profile
        $tareas = [];
        $tareas[] = Tarea::firstOrCreate(
            ['idPerfil' => $perfilTrabajo->idPerfil, 'tituloTarea' => 'Presentación Final'], 
            ['descripcionTarea' => 'Terminar diapositivas para la clase', 'fechaInicioTarea' => Carbon::today(), 'fechaLimite' => Carbon::today()->addDays(2), 'estadoTarea' => 'Pendiente', 'prioridadTarea' => 'Alta', 'estimacionEsfuerzo' => 4]
        );
        $tareas[] = Tarea::firstOrCreate(
            ['idPerfil' => $perfilTrabajo->idPerfil, 'tituloTarea' => 'Revisión de Código'], 
            ['descripcionTarea' => 'Revisar PRs pendientes', 'fechaInicioTarea' => Carbon::today(), 'fechaLimite' => Carbon::today()->addDays(1), 'estadoTarea' => 'Pendiente', 'prioridadTarea' => 'Media', 'estimacionEsfuerzo' => 2]
        );
        $tareas[] = Tarea::firstOrCreate(
            ['idPerfil' => $perfilEstudio->idPerfil, 'tituloTarea' => 'Estudiar para Web'], 
            ['descripcionTarea' => 'Capítulo 4 y 5 del libro', 'fechaInicioTarea' => Carbon::today(), 'fechaLimite' => Carbon::today()->addDays(5), 'estadoTarea' => 'Pendiente', 'prioridadTarea' => 'Alta', 'estimacionEsfuerzo' => 6]
        );
        $tareas[] = Tarea::firstOrCreate(
            ['idPerfil' => $perfilPersonal->idPerfil, 'tituloTarea' => 'Leer Libro'], 
            ['descripcionTarea' => 'Leer 20 páginas de la novela', 'fechaInicioTarea' => Carbon::today(), 'fechaLimite' => Carbon::today()->addDays(1), 'estadoTarea' => 'Completado', 'prioridadTarea' => 'Baja', 'estimacionEsfuerzo' => 1]
        );

        // 5. Create Pomodoro Sessions distributed in the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::today()->subDays($i);
            
            // Randomly create 1 to 3 sessions per day
            $sesionesDelDia = rand(1, 3);
            
            for ($j = 0; $j < $sesionesDelDia; $j++) {
                $tareaRandom = $tareas[array_rand($tareas)];
                
                $minutos = rand(25, 75); // De 1 a 3 ciclos
                
                SesionPomodoro::create([
                    'idConfiguracionPomodoro' => $config->idConfiguracionPomodoro,
                    'idTarea' => $tareaRandom->idTarea,
                    'tiempoTrabajoTotalMinutos' => $minutos,
                    'estadoSesion' => 'Completada',
                    'ciclosObjetivo' => 4,
                    'ciclosCompletados' => round($minutos / 25),
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                    'fechaCreacionSesion' => $fecha,
                ]);
            }
        }

        // 6. Generate statistics
        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $user->idUsuario],
            ['rachaMasLarga' => 5, 'rachaActual' => 0, 'tareasTotales' => 10, 'tiempoTotalPomodoro' => 300, 'sesionesCanceladas' => 2, 'horasConcentracionDiaria' => 2.5]
        );
        
        $estadistica->rachaActual = 5;
        $estadistica->tiempoTotalPomodoro = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($user) {
            $q->where('idUsuario', $user->idUsuario);
        })->where('estadoSesion', 'Completada')->sum('tiempoTrabajoTotalMinutos');
        
        $estadistica->save();

        echo "Datos generados correctamente para fornarinahir21@gmail.com\n";
    }
}
