<?php

declare(strict_types=1);

namespace Modules\Notification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Mail\Customer\WelcomeEmail;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $customer
    ) {}

    public function handle(): void
    {
        Mail::to($this->customer->email)
            ->send(new WelcomeEmail($this->customer));
    }

    public int $tries = 3;
    public int $backoff = 60;
}
