<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = \App\Models\Product::findOrFail($id);

        // Calculate active holds (not expired and not used)
        $activeHolds = $product->holds()
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->sum('quantity');

        $availableStock = $product->total_stock - $activeHolds;

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'total_stock' => $product->total_stock,
            'available_stock' => $availableStock,
            'price' => $product->price,
        ]);
    }
}
