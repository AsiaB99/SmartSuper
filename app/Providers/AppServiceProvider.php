<?php

namespace App\Providers;

use App\Models\Despensa;
use App\Models\Lista;
use App\Policies\DespensaPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Policies\ListaPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('es');

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Confirma tu correo en SmartSuper')
                ->greeting('Hola,')
                ->line('Gracias por registrarte en SmartSuper.')
                ->line('Confirma tu dirección de correo para activar tu cuenta y empezar a gestionar listas, despensas y comparativas de supermercados.')
                ->action('Confirmar correo', $url)
                ->line('Si no has creado esta cuenta, puedes ignorar este mensaje.');
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Restablece tu contraseña de SmartSuper')
                ->greeting('Hola,')
                ->line('Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.')
                ->action('Restablecer contraseña', $resetUrl)
                ->line('Este enlace caducará en 60 minutos.')
                ->line('Si no has solicitado este cambio, no necesitas hacer nada más.');
        });

        Gate::policy(Lista::class, ListaPolicy::class);
        Gate::policy(Despensa::class, DespensaPolicy::class);
    }
}
