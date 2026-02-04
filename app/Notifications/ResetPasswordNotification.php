<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(Lang::get('Reporte de Estatus - Conciliación: Restablecimiento de Contraseña'))
            ->greeting(Lang::get('¡Hola!'))
            ->line(Lang::get('Estás recibiendo este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.'))
            ->action(Lang::get('Restablecer Contraseña'), url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)))
            ->line(Lang::get('Este enlace de restablecimiento caducará en :count minutos.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(Lang::get('Si no solicitaste este cambio, no se requiere ninguna otra acción.'))
            ->salutation(Lang::get('Saludos,')."\n".config('app.name'));
    }
}
