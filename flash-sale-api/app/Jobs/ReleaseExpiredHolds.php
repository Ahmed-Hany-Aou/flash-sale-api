<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

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
        // Find expired holds to get product IDs for cache invalidation
        $expiredHolds = Hold::where('expires_at', '<', now())
            ->where('is_used', false)
            ->get();

        if ($expiredHolds->isEmpty()) {
            return;
        }

        // Delete holds
        Hold::whereIn('id', $expiredHolds->pluck('id'))->delete();

        // Invalidate cache for affected products
        foreach ($expiredHolds->unique('product_id') as $hold) {
            Cache::forget("product_{$hold->product_id}_stock");
        }
    }
}
