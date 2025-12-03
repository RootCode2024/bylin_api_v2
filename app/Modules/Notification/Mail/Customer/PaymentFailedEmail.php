<?php

declare(strict_types=1);

namespace Modules\Notification\Mail\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $order,
        public $customer,
        public string $reason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Problème avec votre paiement - Commande #' . $this->order->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer.payment-failed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
