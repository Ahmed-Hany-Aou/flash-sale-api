<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Hold;

class ReleaseExpiredHolds implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Delete holds that are expired and not used
        Hold::where('expires_at', '<', now())
            ->where('is_used', false)
            ->delete();
    }
}
