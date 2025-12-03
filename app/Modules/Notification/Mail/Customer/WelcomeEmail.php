<?php

declare(strict_types=1);

namespace Modules\Notification\Mail\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $customer
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue chez ' . config('app.name') . ' ! 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
