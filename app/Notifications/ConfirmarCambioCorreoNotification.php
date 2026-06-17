<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ConfirmarCambioCorreoNotification extends Notification
{
    use Queueable;

    public $url;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $url)
    {
        $this->url = $url;
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
        return (new MailMessage)
            ->subject(Lang::get('Confirmación de Cambio de Correo Electrónico - Cronos Notes'))
            ->greeting(Lang::get('¡Hola!'))
            ->line(Lang::get('Recibimos una solicitud para cambiar tu dirección de correo electrónico en Cronos Notes.'))
            ->line(Lang::get('Para confirmar este cambio y activar tu nueva dirección, por favor hacé clic en el botón de abajo:'))
            ->action(Lang::get('Confirmar Cambio de Correo'), $this->url)
            ->line(Lang::get('Este enlace de confirmación expirará en 2 horas.'))
            ->line(Lang::get('Si vos no solicitaste este cambio, podés ignorar este correo de forma segura.'));
    }
}
