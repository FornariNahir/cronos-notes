<?php

namespace App\Console\Commands;

use App\Models\Tarea;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios de tareas pendientes que vencen en las próximas 24 horas al webhook de n8n (RF-M20)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $webhookUrl = env('N8N_WEBHOOK_RECORDATORIOS_URL', 'http://localhost:5678/webhook/recordatorios-tareas');

        $hoy = Carbon::now()->toDateString();
        $manana = Carbon::now()->addDay()->toDateString();

        $this->info("Buscando tareas pendientes con vencimiento entre {$hoy} y {$manana}...");

        // En la BD: Tabla 'Tarea', estado 'estadoTarea' != 'Completado', relación Tarea -> Perfil -> Usuario
        $tareas = Tarea::with('perfil.usuario')
            ->where('estadoTarea', '!=', 'Completado')
            ->whereNotNull('fechaLimite')
            ->whereBetween('fechaLimite', [$hoy, $manana])
            ->whereHas('perfil.usuario', function ($query) {
                $query->whereNotNull('email')
                      ->where('email', '!=', '');
            })
            ->get();

        if ($tareas->isEmpty()) {
            $this->info('No se encontraron tareas próximas a vencer en las siguientes 24 horas.');
            return self::SUCCESS;
        }

        $this->info("Se encontraron {$tareas->count()} tarea(s) próxima(s) a vencer.");

        $enviados = 0;
        $errores = 0;

        foreach ($tareas as $tarea) {
            $usuario = $tarea->perfil?->usuario;
            $email = $usuario?->email;

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->warn("Omitiendo tarea ID {$tarea->idTarea} ('{$tarea->tituloTarea}'): el usuario no tiene un email válido.");
                continue;
            }

            // Como 'fechaLimite' es de tipo DATE (YYYY-MM-DD), tomamos el final del día (23:59:59)
            // y acotamos el cálculo al rango de las próximas 24 horas.
            $vencimiento = Carbon::parse($tarea->fechaLimite)->endOfDay();
            $minutosRestantes = Carbon::now()->diffInMinutes($vencimiento, false);
            $horasRestantes = max(1, min(24, (int) ceil($minutosRestantes / 60)));

            $payload = [
                'email' => $email,
                'tarea' => $tarea->tituloTarea,
                'horas_restantes' => $horasRestantes,
            ];

            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->post($webhookUrl, $payload);

                if ($response->successful()) {
                    $enviados++;
                    $this->info("✔ Recordatorio enviado a [{$email}] para la tarea '{$tarea->tituloTarea}' ({$horasRestantes}h restantes).");
                } else {
                    $errores++;
                    $status = $response->status();
                    $body = $response->body() ?: '(respuesta vacía)';
                    $this->error("✖ Error HTTP {$status} al enviar recordatorio a [{$email}] para '{$tarea->tituloTarea}': {$body}");
                    Log::error("RF-M20 SendTaskReminders: Fallo HTTP {$status}", [
                        'payload' => $payload,
                        'response' => $body,
                    ]);
                }
            } catch (\Throwable $e) {
                $errores++;
                $this->error("✖ Excepción al conectar con n8n para [{$email}] ('{$tarea->tituloTarea}'): {$e->getMessage()}");
                Log::error('RF-M20 SendTaskReminders: Excepción de conexión con n8n', [
                    'payload' => $payload,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Resumen: {$enviados} enviado(s) correctamente, {$errores} con error.");

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}
