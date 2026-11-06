<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedResetPassword extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120];
}