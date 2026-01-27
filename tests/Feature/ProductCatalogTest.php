<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\PreparationArea;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_lifecycle_with_inventory()
    {
        // 1. Setup
        $area = PreparationArea::create(['name' => 'Kitchen']);
        $cat = Category::create(['name' => 'Food']);
        $user = User::factory()->create();

        // 2. Create Product (Dish - No Stock)
        $response = $this->actingAs($user)->post('/products', [
            'name' => 'Burger',
            'type' => 'dish',
            'price' => 10.00,
            'category_id' => $cat->id,
            'preparation_area_id' => $area->id,
            'status' => 'on',
        ]);
        $response->assertRedirect('/products');
        $this->assertDatabaseHas('products', ['name' => 'Burger', 'stock' => 0]);
        // Verify no inventory movement for Burger
        $burger = Product::where('name', 'Burger')->first();
        $this->assertDatabaseMissing('inventory_movements', ['product_id' => $burger->id]);

        // 3. Create Product (Finished - Stock 10)
        $response = $this->actingAs($user)->post('/products', [
            'name' => 'Soda',
            'type' => 'finished',
            'price' => 2.00,
            'category_id' => $cat->id,
            'preparation_area_id' => $area->id,
            'status' => 'on',
            'controls_inventory' => 'on',
            'stock' => 10,
        ]);
        $response->assertRedirect('/products');
        $soda = Product::where('name', 'Soda')->first();
        $this->assertEquals(10, $soda->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $soda->id,
            'quantity' => 10,
            'type' => 'adjustment'
        ]);

        // 4. Update Stock (10 -> 15)
        $response = $this->actingAs($user)->put("/products/{$soda->id}", [
            'name' => 'Soda',
            'type' => 'finished',
            'price' => 2.00,
            'category_id' => $cat->id,
            'preparation_area_id' => $area->id,
            'status' => 'on',
            'controls_inventory' => 'on',
            'stock' => 15,
        ]);
        $soda->refresh();
        $this->assertEquals(15, $soda->stock);
        
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $soda->id,
            'quantity' => 5, // Delta
            'previous_stock' => 10,
            'new_stock' => 15
        ]);
    }
}
