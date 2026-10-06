<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\OrderDetail;
use App\Models\ProductRecipeItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesPwaScenario;
use Tests\TestCase;

class PwaOrdersApiTest extends TestCase
{
    use CreatesPwaScenario, RefreshDatabase;

    public function test_lists_preparation_areas_for_branch(): void
    {
        [$user, $branch] = $this->createScenario();
        $this->makeArea($branch, ['name' => 'Cocina', 'sort_order' => 0]);
        $this->makeArea($branch, ['name' => 'Barra', 'sort_order' => 1]);
        $this->makeArea($branch, ['name' => 'Inactiva', 'status' => false]);

        $this->getJson("/api/preparation-areas?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Cocina')
            ->assertJsonPath('data.1.name', 'Barra')
            ->assertJsonPath('data.0.print_ticket', true);
    }

    public function test_preparation_areas_requires_branch_access(): void
    {
        [$user, $branch] = $this->createScenario();

        $foreignBranch = Branch::withoutEvents(fn () => Branch::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Sucursal no asignada',
            'is_active' => true,
        ]));

        $this->getJson("/api/preparation-areas?branch_id={$foreignBranch->id}")
            ->assertForbidden();
    }

    public function test_orders_index_returns_paginated_orders(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user, ['total' => 25]);
        $this->makeDetail($order, $product, ['status' => 'pending']);
        $this->makeOrder($table, $user, ['status' => 'closed', 'total' => 10]);

        $response = $this->getJson("/api/orders?branch_id={$branch->id}&status=open")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.table_name', 'Mesa 1')
            ->assertJsonPath('data.0.has_pending', true)
            ->assertJsonPath('meta.total', 1);

        $this->assertSame(1, $response->json('meta.current_page'));
    }

    public function test_show_includes_preparation_area_name_and_print_state(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch, ['name' => 'Cocina']);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $this->makeDetail($order, $product, ['status' => 'sent', 'is_printed' => false]);

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('order.table.name', 'Mesa 1')
            ->assertJsonPath('order.details.0.preparation_area_name', 'Cocina')
            ->assertJsonPath('order.details.0.is_printed', false);
    }

    public function test_send_client_mode_marks_items_sent_and_returns_print_payload(): void
    {
        [$user, $branch] = $this->createScenario();
        $kitchen = $this->makeArea($branch, ['name' => 'Cocina', 'sort_order' => 0]);
        $bar = $this->makeArea($branch, ['name' => 'Barra', 'sort_order' => 1]);
        $category = $this->makeCategory($branch);
        $burger = $this->makeProduct($branch, $category, $kitchen, ['name' => 'Hamburguesa']);
        $coffee = $this->makeProduct($branch, $category, $bar, ['name' => 'Café']);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $this->makeDetail($order, $burger, ['quantity' => 2, 'notes' => 'sin cebolla']);
        $this->makeDetail($order, $coffee, ['quantity' => 1]);

        $this->postJson("/api/orders/{$order->id}/send", ['print_mode' => 'client'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('updated_count', 2)
            ->assertJsonCount(2, 'print.areas')
            ->assertJsonPath('print.areas.0.area_name', 'Cocina')
            ->assertJsonPath('print.areas.0.items.0.name', 'Hamburguesa')
            ->assertJsonPath('print.areas.0.items.0.notes', 'sin cebolla')
            ->assertJsonPath('print.areas.1.area_name', 'Barra')
            ->assertJsonPath('print.areas.1.items.0.name', 'Café');

        $this->assertSame(2, OrderDetail::where('order_id', $order->id)->where('status', 'sent')->count());
        $this->assertDatabaseCount('print_jobs', 0);
    }

    public function test_send_client_mode_does_not_print_areas_with_print_ticket_disabled(): void
    {
        [$user, $branch] = $this->createScenario();
        $silentArea = $this->makeArea($branch, ['name' => 'Sin ticket', 'print_ticket' => false]);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $silentArea);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $this->makeDetail($order, $product);

        $this->postJson("/api/orders/{$order->id}/send", ['print_mode' => 'client'])
            ->assertOk()
            ->assertJsonPath('updated_count', 1)
            ->assertJsonCount(0, 'print.areas');
    }

    public function test_send_client_mode_returns_inventory_warning_when_stock_is_short(): void
    {
        [$user, $branch, $order, $product] = $this->scenarioWithRecipe(quantity: 2, stock: 1, recipeQuantity: 2);

        $this->postJson("/api/orders/{$order->id}/send", ['print_mode' => 'client'])
            ->assertStatus(409)
            ->assertJsonPath('status', 'inventory_warning')
            ->assertJsonCount(1, 'shortages');

        $this->assertSame(1, OrderDetail::where('order_id', $order->id)->where('status', 'pending')->count());
    }

    public function test_send_client_mode_allows_negative_inventory_when_confirmed(): void
    {
        [$user, $branch, $order, $product] = $this->scenarioWithRecipe(quantity: 2, stock: 1, recipeQuantity: 2);

        $this->postJson("/api/orders/{$order->id}/send", [
            'print_mode' => 'client',
            'allow_negative_inventory' => true,
        ])
            ->assertOk()
            ->assertJsonPath('updated_count', 1);

        $this->assertSame(1, OrderDetail::where('order_id', $order->id)->where('status', 'sent')->count());
    }

    public function test_mark_printed_updates_only_order_details(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $printed = $this->makeDetail($order, $product, ['status' => 'sent', 'is_printed' => false]);
        $other = $this->makeDetail($order, $product, ['status' => 'sent', 'is_printed' => false]);

        $this->postJson("/api/orders/{$order->id}/mark-printed", ['detail_ids' => [$printed->id]])
            ->assertOk()
            ->assertJsonPath('updated', 1);

        $this->assertTrue($printed->fresh()->is_printed);
        $this->assertFalse($other->fresh()->is_printed);
    }

    public function test_print_payload_groups_sent_items_by_area_and_filters_by_area(): void
    {
        [$user, $branch] = $this->createScenario();
        $kitchen = $this->makeArea($branch, ['name' => 'Cocina', 'sort_order' => 0]);
        $bar = $this->makeArea($branch, ['name' => 'Barra', 'sort_order' => 1]);
        $category = $this->makeCategory($branch);
        $burger = $this->makeProduct($branch, $category, $kitchen, ['name' => 'Hamburguesa']);
        $coffee = $this->makeProduct($branch, $category, $bar, ['name' => 'Café']);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $this->makeDetail($order, $burger, ['status' => 'sent']);
        $this->makeDetail($order, $coffee, ['status' => 'sent']);
        $this->makeDetail($order, $burger, ['status' => 'pending']);
        $this->makeDetail($order, $burger, ['status' => 'canceled']);

        $this->getJson("/api/orders/{$order->id}/print-payload")
            ->assertOk()
            ->assertJsonCount(2, 'print.areas')
            ->assertJsonPath('print.areas.0.area_name', 'Cocina')
            ->assertJsonPath('print.areas.1.area_name', 'Barra');

        $this->getJson("/api/orders/{$order->id}/print-payload?area_id={$bar->id}")
            ->assertOk()
            ->assertJsonCount(1, 'print.areas')
            ->assertJsonPath('print.areas.0.area_name', 'Barra')
            ->assertJsonPath('print.areas.0.items.0.name', 'Café');
    }

    public function test_other_tenant_cannot_access_order(): void
    {
        [$userA, $branchA] = $this->createScenario();
        $area = $this->makeArea($branchA);
        $category = $this->makeCategory($branchA);
        $product = $this->makeProduct($branchA, $category, $area);
        $table = $this->makeTable($branchA);
        $order = $this->makeOrder($table, $userA);
        $this->makeDetail($order, $product, ['status' => 'sent']);

        $this->createScenario();

        $this->getJson("/api/orders/{$order->id}")->assertNotFound();
        $this->postJson("/api/orders/{$order->id}/mark-printed", ['detail_ids' => [1]])->assertNotFound();
        $this->getJson("/api/orders/{$order->id}/print-payload")->assertNotFound();
    }

    public function test_tables_endpoint_includes_zone(): void
    {
        [$user, $branch] = $this->createScenario();
        $this->makeTable($branch, ['name' => 'Mesa 1', 'zone' => 'Terraza']);
        $this->makeTable($branch, ['name' => 'Mesa 2', 'capacity' => 6]);

        $this->getJson("/api/tables?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Mesa 1')
            ->assertJsonPath('data.0.zone', 'Terraza')
            ->assertJsonPath('data.0.seats', null)
            ->assertJsonPath('data.1.name', 'Mesa 2')
            ->assertJsonPath('data.1.seats', 6);
    }

    public function test_update_pending_item_quantity_and_notes(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area, ['price' => 10]);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $detail = $this->makeDetail($order, $product, ['quantity' => 1]);

        $response = $this->patchJson("/api/orders/{$order->id}/items/{$detail->id}", [
            'quantity' => 3,
            'notes' => 'sin sal',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('detail.quantity', 3)
            ->assertJsonPath('detail.notes', 'sin sal');

        $this->assertSame(30.0, (float) $response->json('order_total'));
        $this->assertDatabaseHas('order_details', ['id' => $detail->id, 'quantity' => 3, 'notes' => 'sin sal']);
    }

    public function test_update_item_rejects_sent_items(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $detail = $this->makeDetail($order, $product, ['status' => 'sent']);

        $this->patchJson("/api/orders/{$order->id}/items/{$detail->id}", ['quantity' => 2])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_update_item_rejects_items_from_another_order(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch);
        $orderA = $this->makeOrder($table, $user);
        $orderB = $this->makeOrder($table, $user);
        $detail = $this->makeDetail($orderB, $product, ['quantity' => 1]);

        $this->patchJson("/api/orders/{$orderA->id}/items/{$detail->id}", ['quantity' => 2])
            ->assertStatus(400)
            ->assertJsonPath('status', 'error');
    }

    public function test_get_or_create_self_assigns_mesero(): void
    {
        [$user, $branch] = $this->createScenario('mesero');
        $table = $this->makeTable($branch);

        $response = $this->postJson('/api/orders/get-or-create', [
            'table_id' => $table->id,
            'branch_id' => $branch->id,
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'user_id' => $user->id,
            'status' => 'open',
        ]);
    }

    public function test_get_or_create_requires_waiter_for_admin(): void
    {
        [$admin, $branch] = $this->createScenario('administrador');
        $table = $this->makeTable($branch);

        $this->postJson('/api/orders/get-or-create', [
            'table_id' => $table->id,
            'branch_id' => $branch->id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('tables', ['id' => $table->id, 'status' => 'free']);
    }

    public function test_get_or_create_admin_assigns_selected_waiter(): void
    {
        [$admin, $branch] = $this->createScenario('administrador');
        $waiter = $this->makeUser('mesero', $branch);
        $table = $this->makeTable($branch);

        $response = $this->postJson('/api/orders/get-or-create', [
            'table_id' => $table->id,
            'branch_id' => $branch->id,
            'waiter_id' => $waiter->id,
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'user_id' => $waiter->id,
        ]);
        $this->assertDatabaseHas('tables', ['id' => $table->id, 'status' => 'occupied']);
    }

    public function test_get_or_create_rejects_waiter_without_mesero_role(): void
    {
        [$admin, $branch] = $this->createScenario('administrador');
        $cashier = $this->makeUser('caja', $branch);
        $table = $this->makeTable($branch);

        $this->postJson('/api/orders/get-or-create', [
            'table_id' => $table->id,
            'branch_id' => $branch->id,
            'waiter_id' => $cashier->id,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Selecciona un mesero válido de esta sucursal');
    }

    public function test_waiters_endpoint_lists_only_meseros_of_branch(): void
    {
        [$admin, $branch] = $this->createScenario('administrador');
        $waiter = $this->makeUser('mesero', $branch);
        $this->makeUser('caja', $branch);

        $this->getJson("/api/waiters?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $waiter->id)
            ->assertJsonPath('data.0.name', $waiter->name);
    }

    public function test_mesero_cannot_open_order_of_another_waiter(): void
    {
        [$owner, $branch] = $this->createScenario('mesero');
        $other = $this->makeUser('mesero', $branch);
        $table = $this->makeTable($branch, ['status' => 'occupied']);
        $order = $this->makeOrder($table, $owner);

        Sanctum::actingAs($other);

        $this->getJson("/api/orders/{$order->id}")->assertForbidden();

        $this->postJson('/api/orders/get-or-create', [
            'table_id' => $table->id,
            'branch_id' => $branch->id,
        ])->assertForbidden();
    }

    public function test_release_frees_table_without_active_items(): void
    {
        [$user, $branch] = $this->createScenario('mesero');
        $table = $this->makeTable($branch, ['status' => 'occupied']);
        $order = $this->makeOrder($table, $user);

        $this->putJson("/api/tables/{$table->id}/release")
            ->assertOk()
            ->assertJsonPath('table.status', 'free');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'closed']);
        $this->assertDatabaseHas('tables', ['id' => $table->id, 'status' => 'free']);
    }

    public function test_release_rejects_order_with_active_items(): void
    {
        [$user, $branch] = $this->createScenario('mesero');
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch, ['status' => 'occupied']);
        $order = $this->makeOrder($table, $user);
        $this->makeDetail($order, $product, ['status' => 'pending']);

        $this->putJson("/api/tables/{$table->id}/release")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('tables', ['id' => $table->id, 'status' => 'occupied']);
    }

    public function test_mesero_cannot_release_order_of_another_waiter(): void
    {
        [$owner, $branch] = $this->createScenario('mesero');
        $other = $this->makeUser('mesero', $branch);
        $table = $this->makeTable($branch, ['status' => 'occupied']);
        $this->makeOrder($table, $owner);

        Sanctum::actingAs($other);

        $this->putJson("/api/tables/{$table->id}/release")->assertForbidden();

        $this->assertDatabaseHas('tables', ['id' => $table->id, 'status' => 'occupied']);
    }

    public function test_pre_check_returns_items_and_totals(): void
    {
        [$user, $branch] = $this->createScenario();
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area, ['name' => 'Hamburguesa', 'price' => 10]);
        $table = $this->makeTable($branch, ['name' => 'M1']);
        $order = $this->makeOrder($table, $user, ['total' => 25]);
        $this->makeDetail($order, $product, ['quantity' => 2, 'notes' => 'sin cebolla']);

        $response = $this->getJson("/api/orders/{$order->id}/pre-check")
            ->assertOk()
            ->assertJsonPath('pre_check.table_name', 'M1')
            ->assertJsonPath('pre_check.items.0.name', 'Hamburguesa')
            ->assertJsonPath('pre_check.items.0.quantity', 2)
            ->assertJsonPath('pre_check.items.0.notes', 'sin cebolla')
            ->assertJsonPath('pre_check.tips_enabled', false);

        $this->assertSame(20.0, (float) $response->json('pre_check.items.0.line_total'));
        $this->assertSame(25.0, (float) $response->json('pre_check.total'));
        $this->assertCount(4, $response->json('pre_check.tip_suggestions'));
    }

    public function test_pre_check_requires_items(): void
    {
        [$user, $branch] = $this->createScenario();
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);

        $this->getJson("/api/orders/{$order->id}/pre-check")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_pre_check_includes_configured_tips(): void
    {
        [$user, $branch] = $this->createScenario('administrador');
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area, ['price' => 100]);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user, ['total' => 100]);
        $this->makeDetail($order, $product);

        Setting::create([
            'tenant_id' => $this->tenantId,
            'branch_id' => $branch->id,
            'key' => 'ticket_tips_enabled',
            'value' => '1',
        ]);
        Setting::create([
            'tenant_id' => $this->tenantId,
            'branch_id' => $branch->id,
            'key' => 'ticket_tip_1_percent',
            'value' => '15',
        ]);

        $response = $this->getJson("/api/orders/{$order->id}/pre-check")
            ->assertOk()
            ->assertJsonPath('pre_check.tips_enabled', true);

        $this->assertSame(15.0, (float) $response->json('pre_check.tip_suggestions.0.percent'));
        $this->assertSame(15.0, (float) $response->json('pre_check.tip_suggestions.0.amount'));
    }

    private function scenarioWithRecipe(int $quantity, float $stock, float $recipeQuantity): array
    {
        [$user, $branch] = $this->createScenario('administrador');
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);
        $table = $this->makeTable($branch);
        $order = $this->makeOrder($table, $user);
        $this->makeDetail($order, $product, ['quantity' => $quantity]);

        $ingredient = InventoryItem::create([
            'tenant_id' => $this->tenantId,
            'branch_id' => $branch->id,
            'name' => 'Pan',
            'base_unit' => 'u',
            'stock' => $stock,
            'status' => true,
        ]);

        ProductRecipeItem::create([
            'product_id' => $product->id,
            'inventory_item_id' => $ingredient->id,
            'quantity' => $recipeQuantity,
            'tenant_id' => $this->tenantId,
            'branch_id' => $branch->id,
        ]);

        return [$user, $branch, $order, $product];
    }
}
