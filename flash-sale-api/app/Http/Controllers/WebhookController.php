<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProcessedWebhook;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $request->validate([
            'idempotency_key' => 'required|string',
            'payload' => 'required|array',
        ]);

        // Idempotency check: find existing or create new
        ProcessedWebhook::firstOrCreate(
            ['idempotency_key' => $request->idempotency_key],
            ['payload' => $request->payload]
        );

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
