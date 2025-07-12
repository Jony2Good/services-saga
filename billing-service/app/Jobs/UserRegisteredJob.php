<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\BillingAccount;

class UserRegisteredJob implements ShouldQueue
{
    use Queueable;

    protected $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $eventData)
    {
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {        
        try {
            BillingAccount::create([
                'user_id' => $this->eventData['user_id'],              
                'balance' => 0,
            ]);          
        } catch (\Exception $e) {
            \Log::error('Failed to handle UserRegistered event: ' . $e->getMessage());
            throw $e;
        }
    }
}
