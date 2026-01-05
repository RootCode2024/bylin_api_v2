<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = $this->isCustomer($notifiable)
            ? config('app.frontend_url')
            : config('app.frontend_url_admin');

        $url = $frontendUrl . '/auth/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe - ' . config('app.name'))
            ->markdown('emails.auth.reset-password', [
                'notifiable' => $notifiable,
                'url' => $url
            ]);
    }

    /**
     * Vérifie si l'utilisateur est un customer
     */
    private function isCustomer($notifiable): bool
    {
        // Vérifier si c'est une instance de Customer
        if (get_class($notifiable) === 'Modules\Customer\Models\Customer') {
            return true;
        }

        // Vérifier si c'est un User avec le rôle 'customer'
        if (method_exists($notifiable, 'hasRole')) {
            return $notifiable->hasRole('customer');
        }

        return false;
    }
}
