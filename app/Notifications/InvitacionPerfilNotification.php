<?php

namespace App\Notifications;

use App\Models\InvitacionPerfil;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class InvitacionPerfilNotification extends Notification
{
    use Queueable;

    public $invitacion;

    /**
     * Create a new notification instance.
     */
    public function __construct(InvitacionPerfil $invitacion)
    {
        $this->invitacion = $invitacion;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $perfilNombre = $this->invitacion->perfil->tituloPerfil;
        $invitador = $this->invitacion->usuarioQueInvita->nombre . ' ' . $this->invitacion->usuarioQueInvita->apellido;
        $permiso = $this->invitacion->permisoOfrecido;
        $url = url(route('invitacion.ver', $this->invitacion->token));

        return (new MailMessage)
            ->subject(Lang::get('Invitación a colaborar en el perfil ":perfil" - Cronos Notes', ['perfil' => $perfilNombre]))
            ->greeting(Lang::get('¡Hola!'))
            ->line(Lang::get(':invitador te ha invitado a colaborar en su perfil/espacio de trabajo ":perfil" en Cronos Notes.', [
                'invitador' => $invitador,
                'perfil' => $perfilNombre
            ]))
            ->line(Lang::get('Se te ha asignado el rol de: **:permiso**.', ['permiso' => $permiso]))
            ->action(Lang::get('Aceptar Invitación'), $url)
            ->line(Lang::get('Este enlace de invitación expirará en 7 días.'))
            ->line(Lang::get('Si no esperabas esta invitación, puedes ignorar este correo de forma segura.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'idInvitacion' => $this->invitacion->idInvitacion,
            'idPerfil' => $this->invitacion->idPerfil,
            'emailInvitado' => $this->invitacion->emailInvitado,
        ];
    }
}
