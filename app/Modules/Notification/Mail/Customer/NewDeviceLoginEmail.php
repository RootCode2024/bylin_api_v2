<?php

declare(strict_types=1);

namespace Modules\Notification\Mail\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDeviceLoginEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public $deviceInfo,
        public $location
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔒 Nouvelle Connexion Détectée',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.new-device-login',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
