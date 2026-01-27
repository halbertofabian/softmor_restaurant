<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\PreparationArea;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_lifecycle()
    {
        $user = User::factory()->create();
        $table = Table::create(['name' => 'T1', 'status' => 'occupied']); 
        $area = PreparationArea::create(['name' => 'K']);
        $cat = Category::create(['name' => 'F']);
        
        // Product with stock
        $product = Product::create([
            'name' => 'Soda',
            'type' => 'finished',
            'price' => 10.00,
            'category_id' => $cat->id,
            'preparation_area_id' => $area->id,
            'controls_inventory' => true,
            'stock' => 50
        ]);

        // 1. Create Order
        $response = $this->actingAs($user)->post('/orders', ['table_id' => $table->id]);
        $order = Order::first();
        $this->assertEquals('open', $order->status);

        // 2. Add Item
        $this->actingAs($user)->post("/orders/{$order->id}/add-item", [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
        $order->refresh();
        $this->assertEquals(20.00, $order->total);
        $this->assertCount(1, $order->details);

        // 3. Close Order
        $this->actingAs($user)->put("/orders/{$order->id}/close");
        $order->refresh();
        $this->assertEquals('closed', $order->status);
        $this->assertNotNull($order->closed_at);
        
        // Verify Table Released
        $table->refresh();
        $this->assertEquals('free', $table->status);

        // Verify Inventory Deducted
        $product->refresh();
        $this->assertEquals(48, $product->stock); // 50 - 2
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => -2
        ]);
    }
}
