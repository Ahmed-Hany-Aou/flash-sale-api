<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\ProcessedWebhook;
use App\Jobs\ReleaseExpiredHolds;
use Illuminate\Support\Facades\Cache;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a product for testing
        Product::create([
            'name' => 'Test Product',
            'total_stock' => 10,
            'price' => 100.00,
        ]);
    }

    public function test_hold_expiry_returns_availability()
    {
        $product = Product::first();

        // 1. Create a hold
        $response = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
        $response->assertStatus(201);

        // Check stock reduced
        $this->assertEquals(5, $this->getAvailableStock($product->id));

        // 2. Travel time forward (20 mins)
        $this->travel(20)->minutes();

        // 3. Run the cleanup job
        (new ReleaseExpiredHolds)->handle();

        // 4. Check stock returned
        $this->assertEquals(10, $this->getAvailableStock($product->id));
    }

    public function test_webhook_idempotency()
    {
        $payload = [
            'idempotency_key' => 'unique_key_123',
            'payload' => ['status' => 'paid', 'order_id' => 1],
        ];

        // First request
        $response1 = $this->postJson('/api/payments/webhook', $payload);
        $response1->assertStatus(200);

        // Second request (duplicate)
        $response2 = $this->postJson('/api/payments/webhook', $payload);
        $response2->assertStatus(200);

        // Assert only one record exists
        $this->assertCount(1, ProcessedWebhook::all());
    }

    public function test_webhook_stored_before_order()
    {
        $payload = [
            'idempotency_key' => 'early_webhook',
            'payload' => ['status' => 'paid', 'order_id' => 999], // Order 999 doesn't exist yet
        ];

        $response = $this->postJson('/api/payments/webhook', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('processed_webhooks', [
            'idempotency_key' => 'early_webhook',
        ]);
    }

    public function test_cannot_exceed_stock()
    {
        $product = Product::first();

        // Try to hold 11 items (stock is 10)
        $response = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'quantity' => 11,
        ]);

        $response->assertStatus(409)
            ->assertJson(['error' => 'Insufficient stock']);
    }

    private function getAvailableStock($productId)
    {
        $response = $this->getJson("/api/products/{$productId}");
        return $response->json('available_stock');
    }
}
