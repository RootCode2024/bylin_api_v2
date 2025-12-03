<?php

declare(strict_types=1);

namespace Modules\Notification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Mail\Customer\NewDeviceLoginEmail;

class SendSecurityAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $user,
        public array $deviceInfo,
        public array $location
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new NewDeviceLoginEmail($this->user, $this->deviceInfo, $this->location));
    }

    public int $tries = 3;
    public int $backoff = 30;
}
