<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show($id)
    {
        $productData = Cache::remember("product_{$id}_stock", 60, function () use ($id) {
            $product = \App\Models\Product::findOrFail($id);

            // Calculate active holds (not expired and not used)
            $activeHolds = $product->holds()
                ->where('expires_at', '>', now())
                ->where('is_used', false)
                ->sum('quantity');

            $availableStock = $product->total_stock - $activeHolds;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'total_stock' => $product->total_stock,
                'available_stock' => $availableStock,
                'price' => $product->price,
            ];
        });

        return response()->json($productData);
    }
}
