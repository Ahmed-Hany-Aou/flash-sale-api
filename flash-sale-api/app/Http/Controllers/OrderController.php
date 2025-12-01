<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'hold_id' => 'required|exists:holds,id',
        ]);

        return DB::transaction(function () use ($request) {
            // Lock the hold for update
            $hold = Hold::where('id', $request->hold_id)->lockForUpdate()->first();

            // Check if hold is valid
            if ($hold->is_used) {
                return response()->json(['error' => 'Hold already used'], 409);
            }

            if ($hold->expires_at < now()) {
                return response()->json(['error' => 'Hold expired'], 409);
            }

            // Mark hold as used
            $hold->update(['is_used' => true]);

            // Create order
            $order = Order::create([
                'hold_id' => $hold->id,
                'product_id' => $hold->product_id,
                'quantity' => $hold->quantity,
                'total_amount' => $hold->quantity * $hold->product->price,
            ]);

            // Decrement product stock
            $product = Product::where('id', $hold->product_id)->lockForUpdate()->first();
            $product->decrement('total_stock', $hold->quantity);

            // Invalidate cache
            Cache::forget("product_{$product->id}_stock");

            return response()->json($order, 201);
        });
    }
}
