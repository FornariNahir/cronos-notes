<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PomodoroController;
use App\Http\Controllers\ApunteController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth.custom', 'verified'])->name('dashboard');

Route::middleware('auth.custom')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // DASHBOARD (principalGestion.html)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PERFILES
    Route::get('/perfiles', [PerfilController::class, 'index'])->name('perfiles.index');
    Route::get('/perfiles/{id}', [PerfilController::class, 'show'])->name('perfiles.show');
    Route::post('/perfiles', [PerfilController::class, 'store'])->name('perfiles.store');
    Route::put('/perfiles/{id}', [PerfilController::class, 'update'])->name('perfiles.update');
    Route::delete('/perfiles/{id}', [PerfilController::class, 'destroy'])->name('perfiles.destroy');
    Route::post('/perfiles/activar', [PerfilController::class, 'setActivo'])->name('perfiles.activar');

    // TAREAS
    Route::get('/tareas', [TareaController::class, 'index'])->name('tareas.index');
    Route::get('/tareas/{id}', [TareaController::class, 'show'])->name('tareas.show');
    Route::post('/tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::put('/tareas/{id}', [TareaController::class, 'update'])->name('tareas.update');
    Route::patch('/tareas/{id}/completar', [TareaController::class, 'completar'])->name('tareas.completar');
    Route::delete('/tareas/{id}', [TareaController::class, 'destroy'])->name('tareas.destroy');

    // POMODORO
    Route::get('/pomodoro', [PomodoroController::class, 'index'])->name('pomodoro.index');
    Route::post('/pomodoro/iniciar', [PomodoroController::class, 'iniciarSesion'])->name('pomodoro.iniciar');
    Route::post('/pomodoro/registrar', [PomodoroController::class, 'registrarTrabajo'])->name('pomodoro.registrar');
    Route::patch('/pomodoro/estado', [PomodoroController::class, 'actualizarEstado'])->name('pomodoro.estado');
    Route::post('/pomodoro/finalizar', [PomodoroController::class, 'finalizarSesion'])->name('pomodoro.finalizar');
    Route::get('/pomodoro/config', [PomodoroController::class, 'configIndex'])->name('pomodoro.config.index');
    Route::post('/pomodoro/config', [PomodoroController::class, 'configStore'])->name('pomodoro.config.store');
    Route::put('/pomodoro/config/{id}', [PomodoroController::class, 'configUpdate'])->name('pomodoro.config.update');
    Route::delete('/pomodoro/config/{id}', [PomodoroController::class, 'configDestroy'])->name('pomodoro.config.destroy');

    // APUNTES
    Route::get('/apuntes', [ApunteController::class, 'index'])->name('apuntes.index');
    Route::get('/apuntes/crear', [ApunteController::class, 'create'])->name('apuntes.create');
    Route::post('/apuntes', [ApunteController::class, 'store'])->name('apuntes.store');
    Route::get('/apuntes/{id}/editar', [ApunteController::class, 'edit'])->name('apuntes.edit');
    Route::put('/apuntes/{id}', [ApunteController::class, 'update'])->name('apuntes.update');
    Route::delete('/apuntes/{id}', [ApunteController::class, 'destroy'])->name('apuntes.destroy');

});


Route::get('/uso', function () {
    return Inertia::render('Uso');
})->name('uso');

Route::get('/gestion-perfil', function () {
    return Inertia::render('GestionPerfil');
})->name('gestion-perfil');

Route::get('/gestion-tareas', function () {
    return Inertia::render('GestionTareas');
})->name('gestion-tareas');

Route::get('/calendario', function () {
    return Inertia::render('Calendario');
})->name('calendario');

Route::get('/perfil-usuario', function () {
    return Inertia::render('PerfilUsuario');
})->name('perfil-usuario');

require __DIR__.'/auth.php';
