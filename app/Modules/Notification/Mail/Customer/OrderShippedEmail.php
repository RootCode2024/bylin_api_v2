<?php

declare(strict_types=1);

namespace Modules\Notification\Mail\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $order,
        public $customer,
        public ?string $trackingNumber = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre commande est en route ! 🚚',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.order-shipped',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
