<?php

namespace App\Services;

use App\Models\Estadistica;
use App\Models\Racha;
use App\Models\SesionPomodoro;
use Carbon\Carbon;

class EstadisticaService
{
    /**
     * Verifica si la racha activa se ha roto (no hubo sesión ayer ni hoy)
     */
    public function verificarRachaPerdida($idUsuario)
    {
        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $idUsuario],
            [
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'rachaMasLarga' => 0,
                'rachaActual' => 0,
                'sesionesCanceladas' => 0,
                'horasConcentracionDiaria' => 0
            ]
        );

        $rachaActiva = Racha::where('idUsuario', $idUsuario)
            ->where('rachaActiva', 1)
            ->first();

        if ($rachaActiva && $rachaActiva->fechaFinRacha) {
            $ultimaFecha = Carbon::parse($rachaActiva->fechaFinRacha);
            $ayer = Carbon::yesterday();

            // Si la última sesión fue antes de ayer, la racha se rompió
            if ($ultimaFecha->lessThan($ayer)) {
                $rachaActiva->update(['rachaActiva' => 0]);
                
                $estadistica->update([
                    'rachaActual' => 0
                ]);
            }
        } else {
            // Si no hay racha activa, asegurar que rachaActual en Estadistica sea 0
            if ($estadistica->rachaActual > 0) {
                $estadistica->update([
                    'rachaActual' => 0
                ]);
            }
        }
    }
    /**
     * Registra el tiempo de trabajo y actualiza las horas diarias.
     */
    public function registrarTiempoTrabajo($idUsuario, $minutos)
    {
        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $idUsuario],
            [
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'rachaMasLarga' => 0,
                'rachaActual' => 0,
                'sesionesCanceladas' => 0,
                'horasConcentracionDiaria' => 0
            ]
        );

        $estadistica->increment('tiempoTotalPomodoro', $minutos);

        // Calcular dinámicamente las horas de concentración diarias para HOY
        $minutosHoy = SesionPomodoro::whereHas('configuracionPomodoro', function($q) use ($idUsuario) {
                $q->where('idUsuario', $idUsuario);
            })
            ->whereDate('fechaCreacionSesion', Carbon::today()->toDateString())
            ->sum('tiempoTrabajoTotalMinutos');
        $horasConcentracionDiaria = round($minutosHoy / 60, 2);

        $estadistica->update([
            'horasConcentracionDiaria' => $horasConcentracionDiaria
        ]);
    }

    /**
     * Registra una sesión cancelada en la estadística.
     */
    public function registrarCancelacion($idUsuario)
    {
        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $idUsuario],
            [
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'rachaMasLarga' => 0,
                'rachaActual' => 0,
                'sesionesCanceladas' => 0,
                'horasConcentracionDiaria' => 0
            ]
        );

        $estadistica->increment('sesionesCanceladas');
    }

    /**
     * Evalúa y actualiza la racha al completar una sesión.
     * Utiliza tanto la tabla Estadistica como la tabla Racha.
     * La racha solo aumenta cuando se completa una sesión de 25 minutos.
     */
    public function evaluarRachaAlCompletarSesion($idUsuario, $duracionSesion = null)
    {
        if ($duracionSesion === null) {
            $sesionActiva = session('sesionPomodoroActiva');
            $duracionSesion = $sesionActiva['duracionSesion'] ?? null;
        }

        if ($duracionSesion !== null && (int)$duracionSesion !== 25) {
            return;
        }

        $hoy = Carbon::today();
        $ayer = Carbon::yesterday();

        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $idUsuario],
            [
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'rachaMasLarga' => 0,
                'rachaActual' => 0,
                'sesionesCanceladas' => 0,
                'horasConcentracionDiaria' => 0
            ]
        );

        // Obtener la racha activa actual
        $rachaActiva = Racha::where('idUsuario', $idUsuario)
            ->where('rachaActiva', 1)
            ->first();

        // Si la racha activa tiene como fecha fin HOY, significa que la racha del día ya se sumó. No hacemos nada con la racha.
        if ($rachaActiva && $rachaActiva->fechaFinRacha && Carbon::parse($rachaActiva->fechaFinRacha)->isSameDay($hoy)) {
            return;
        }

        if ($rachaActiva) {
            if ($rachaActiva->fechaFinRacha && Carbon::parse($rachaActiva->fechaFinRacha)->isSameDay($ayer)) {
                $nuevaRachaActual = $rachaActiva->rachaActual + 1;
                $rachaActiva->update([
                    'fechaFinRacha' => $hoy->toDateString(),
                    'rachaActual' => $nuevaRachaActual
                ]);
            } else {
                $rachaActiva->update(['rachaActiva' => 0]);

                $nuevaRachaActual = 1;
                Racha::create([
                    'idUsuario' => $idUsuario,
                    'fechaInicioRacha' => $hoy->toDateString(),
                    'fechaFinRacha' => $hoy->toDateString(),
                    'rachaActual' => 1,
                    'rachaActiva' => 1
                ]);
            }
        } else {
            // No hay racha activa, creamos la primera
            $nuevaRachaActual = 1;
            Racha::create([
                'idUsuario' => $idUsuario,
                'fechaInicioRacha' => $hoy->toDateString(),
                'fechaFinRacha' => $hoy->toDateString(),
                'rachaActual' => 1,
                'rachaActiva' => 1
            ]);
        }

        // Actualizamos la tabla Estadistica con los nuevos valores de la racha
        $rachaMasLarga = max($estadistica->rachaMasLarga, $nuevaRachaActual);
        $estadistica->update([
            'rachaActual' => $nuevaRachaActual,
            'rachaMasLarga' => $rachaMasLarga
        ]);
    }

    /**
     * Incrementa la cantidad de tareas completadas.
     */
    public function sumarTareaCompletada($idUsuario)
    {
        $estadistica = Estadistica::firstOrCreate(
            ['idUsuario' => $idUsuario],
            [
                'tareasTotales' => 0,
                'tiempoTotalPomodoro' => 0,
                'rachaMasLarga' => 0,
                'rachaActual' => 0,
                'sesionesCanceladas' => 0,
                'horasConcentracionDiaria' => 0
            ]
        );

        $estadistica->increment('tareasTotales');
    }
}
