<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Hold;

class HoldController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            // Lock the product row for update to prevent race conditions
            $product = Product::where('id', $request->product_id)->lockForUpdate()->first();

            // Calculate active holds
            $activeHolds = $product->holds()
                ->where('expires_at', '>', now())
                ->where('is_used', false)
                ->sum('quantity');

            $availableStock = $product->total_stock - $activeHolds;

            if ($availableStock < $request->quantity) {
                return response()->json(['error' => 'Insufficient stock'], 409);
            }

            // Create the hold
            $hold = Hold::create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'expires_at' => now()->addMinutes(15), // Hold valid for 15 minutes
                'is_used' => false,
            ]);

            // Invalidate cache
            Cache::forget("product_{$product->id}_stock");

            return response()->json($hold, 201);
        });
    }
}
