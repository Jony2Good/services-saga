<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendUserRegistratedToBilling implements ShouldQueue
{
    use Queueable;

    protected array $userData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $userData)
    {
        $this->userData = $userData;
        $this->onQueue('billing_queue');
    }

    /**
     * Execute the job.
     */
    public function handle(): void {}
}
