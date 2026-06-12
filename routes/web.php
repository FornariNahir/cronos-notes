<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PomodoroController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\ApunteController;

use App\Http\Controllers\PerfilCompartidoController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// POMODORO INVITADO (Público)
Route::get('/pomodoro/invitado', function () {
    return Inertia::render('Pomodoro/SesionZen', [
        'isGuest' => true,
        'configs' => [],
        'tareas' => []
    ]);
})->name('pomodoro.invitado');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth.custom', 'verified'])->name('dashboard');

Route::middleware('auth.custom')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // DASHBOARD (principalGestion.html)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // GESTION DINAMICA
    Route::get('/gestion-perfil', [PerfilController::class, 'index'])->name('gestion-perfil');
    Route::get('/gestion-tareas', [TareaController::class, 'index'])->name('gestion-tareas');

    // PERFILES
    Route::get('/perfiles', [PerfilController::class, 'index'])->name('perfiles.index');
    Route::get('/perfiles/{id}', [PerfilController::class, 'show'])->name('perfiles.show');
    Route::post('/perfiles', [PerfilController::class, 'store'])->name('perfiles.store');
    Route::put('/perfiles/{id}', [PerfilController::class, 'update'])->name('perfiles.update');
    Route::delete('/perfiles/{id}', [PerfilController::class, 'destroy'])->name('perfiles.destroy');
    Route::post('/perfiles/activar', [PerfilController::class, 'setActivo'])->name('perfiles.activar');

    // TAREAS
    Route::get('/tareas', [TareaController::class, 'index'])->name('tareas.index');
    Route::get('/tareas/priorizar-ia', [TareaController::class, 'priorizarConIA'])
        ->name('tareas.priorizar-ia')
        ->middleware('throttle:3,1440'); // Límite: 3 peticiones cada 1440 minutos (24hs)
    Route::get('/tareas/{id}', [TareaController::class, 'show'])->name('tareas.show');
    Route::post('/tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::put('/tareas/{id}', [TareaController::class, 'update'])->name('tareas.update');
    Route::patch('/tareas/{id}/completar', [TareaController::class, 'completar'])->name('tareas.completar');
    Route::delete('/tareas/{id}', [TareaController::class, 'destroy'])->name('tareas.destroy');

    // POMODORO (Autenticado)
    Route::get('/pomodoro', [PomodoroController::class, 'index'])->name('pomodoro.index');
    Route::post('/pomodoro/iniciar', [PomodoroController::class, 'iniciarSesion'])->name('pomodoro.iniciar');
    Route::post('/pomodoro/registrar', [PomodoroController::class, 'registrarTrabajo'])->name('pomodoro.registrar');
    Route::patch('/pomodoro/estado', [PomodoroController::class, 'actualizarEstado'])->name('pomodoro.estado');
    Route::post('/pomodoro/finalizar', [PomodoroController::class, 'finalizarSesion'])->name('pomodoro.finalizar');
    Route::get('/pomodoro/config', [PomodoroController::class, 'configIndex'])->name('pomodoro.config.index');
    Route::post('/pomodoro/config', [PomodoroController::class, 'configStore'])->name('pomodoro.config.store');
    Route::put('/pomodoro/config/{id}', [PomodoroController::class, 'configUpdate'])->name('pomodoro.config.update');
    Route::delete('/pomodoro/config/{id}', [PomodoroController::class, 'configDestroy'])->name('pomodoro.config.destroy');

    // ESTADISTICAS (Global)
    Route::get('/estadisticas', [EstadisticaController::class, 'index'])->name('estadisticas.index');

    Route::get('/calendario', function () {
        $userId = Auth::user()->idUsuario;

        $tareas = \App\Models\Tarea::whereHas('perfil', function ($query) use ($userId) {
            $query->where('idUsuario', $userId);
        })
        ->with('perfil')
        ->get()
        ->map(function ($tarea) {
            return [
                'id' => $tarea->idTarea,
                'perfil' => $tarea->perfil ? $tarea->perfil->tituloPerfil : 'Sin perfil',
                'nombre' => $tarea->tituloTarea,
                'desc' => $tarea->descripcionTarea,
                'fecha' => $tarea->fechaLimite ? \Carbon\Carbon::parse($tarea->fechaLimite)->format('Y-m-d') : null,
                'prioridad' => $tarea->prioridadTarea ?? 'Media',
            ];
        })
        ->filter(function ($tarea) {
            return !is_null($tarea['fecha']);
        })
        ->values();

        $perfiles = \App\Models\Perfil::where('idUsuario', $userId)->get();

        return Inertia::render('Calendario', [
            'tareasCargadas' => $tareas,
            'perfilesDisponibles' => $perfiles
        ]);
    })->name('calendario');

    Route::get('/perfil-usuario', function () {
        $userId = Auth::user()->idUsuario;

        // Verificar y de ser necesario resetear la racha del usuario
        $estadisticaService = new \App\Services\EstadisticaService();
        $estadisticaService->verificarRachaPerdida($userId);

        $estadisticas = \App\Models\Estadistica::where('idUsuario', $userId)->first();
        if (!$estadisticas) {
            $estadisticas = (object) [
                'rachaActual' => 0,
                'rachaMasLarga' => 0,
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'horasConcentracionDiaria' => 0
            ];
        }

        return Inertia::render('PerfilUsuario', [
            'estadisticas' => $estadisticas
        ]);
    })->name('perfil-usuario');

    // APUNTES
    Route::get('/apuntes', [ApunteController::class, 'index'])->name('apuntes.index');
    Route::get('/apuntes/crear', [ApunteController::class, 'create'])->name('apuntes.create');
    Route::post('/apuntes', [ApunteController::class, 'store'])->name('apuntes.store');
    Route::get('/apuntes/{id}/editar', [ApunteController::class, 'edit'])->name('apuntes.edit');
    Route::put('/apuntes/{id}', [ApunteController::class, 'update'])->name('apuntes.update');
    Route::delete('/apuntes/{id}', [ApunteController::class, 'destroy'])->name('apuntes.destroy');

    // PERFIL COMPARTIDO — Gestión del propietario
    Route::get('/perfiles/{idPerfil}/compartido', [PerfilCompartidoController::class, 'index'])->name('perfil-compartido.index');
    Route::post('/perfiles/{idPerfil}/compartir', [PerfilCompartidoController::class, 'compartir'])->name('perfil-compartido.compartir');
    Route::put('/perfiles/{idPerfil}/compartido/{idUsuario}', [PerfilCompartidoController::class, 'actualizarPermiso'])->name('perfil-compartido.actualizar');
    Route::delete('/perfiles/{idPerfil}/compartido/{idUsuario}', [PerfilCompartidoController::class, 'revocar'])->name('perfil-compartido.revocar');
    Route::delete('/invitaciones/{idInvitacion}', [PerfilCompartidoController::class, 'cancelarInvitacion'])->name('perfil-compartido.cancelar-invitacion');

    // PERFIL COMPARTIDO — Flujo del invitado (requiere auth)
    Route::get('/invitacion/{token}/entrar', function ($token) {
        return redirect()->route('invitacion.ver', $token);
    })->name('invitacion.entrar');
    Route::post('/invitacion/{token}/aceptar', [PerfilCompartidoController::class, 'aceptarInvitacion'])->name('invitacion.aceptar');
    Route::post('/invitacion/{token}/rechazar', [PerfilCompartidoController::class, 'rechazarInvitacion'])->name('invitacion.rechazar');
});

// Invitación pública — ver invitación sin auth (para que el link del email funcione)
Route::get('/invitacion/{token}', [PerfilCompartidoController::class, 'verInvitacion'])->name('invitacion.ver');

Route::get('/uso', function () {
    return Inertia::render('Uso');
})->name('uso');

require __DIR__.'/auth.php';
