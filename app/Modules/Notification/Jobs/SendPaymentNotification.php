<?php

declare(strict_types=1);

namespace Modules\Notification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Mail\Customer\PaymentSuccessEmail;
use Modules\Notification\Mail\Customer\PaymentFailedEmail;

class SendPaymentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $order,
        public $customer,
        public string $status, // 'success' or 'failed'
        public ?string $reason = null
    ) {}

    public function handle(): void
    {
        if ($this->status === 'success') {
            Mail::to($this->customer->email)
                ->send(new PaymentSuccessEmail($this->order, $this->customer));
        } elseif ($this->status === 'failed') {
            Mail::to($this->customer->email)
                ->send(new PaymentFailedEmail($this->order, $this->customer, $this->reason ?? 'Unknown reason'));
        }
    }

    public int $tries = 3;
    public int $backoff = 60;
}
