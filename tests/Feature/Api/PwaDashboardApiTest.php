<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPwaScenario;
use Tests\TestCase;

class PwaDashboardApiTest extends TestCase
{
    use CreatesPwaScenario, RefreshDatabase;

    public function test_dashboard_rejects_non_admin_role(): void
    {
        [, $branch] = $this->createScenario('mesero');

        $this->getJson("/api/dashboard?branch_id={$branch->id}")->assertForbidden();
    }

    public function test_dashboard_rejects_foreign_branch(): void
    {
        $foreignBranch = Branch::create([
            'tenant_id' => (string) Str::uuid(),
            'name' => 'Sucursal ajena',
            'is_active' => true,
        ]);

        [, $branch] = $this->createScenario('administrador');

        $this->assertNotSame($foreignBranch->id, $branch->id);

        $this->getJson("/api/dashboard?branch_id={$foreignBranch->id}")->assertForbidden();
    }

    public function test_dashboard_returns_branch_stats(): void
    {
        [$user, $branch] = $this->createScenario('administrador');
        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area, ['name' => 'Hamburguesa']);
        $table = $this->makeTable($branch, ['status' => 'occupied']);
        $this->makeTable($branch, ['name' => 'Mesa 2']);

        $closed = $this->makeOrder($table, $user, ['status' => 'closed', 'total' => 100]);
        $this->makeOrder($table, $user, ['status' => 'open', 'total' => 20]);
        $this->makeDetail($closed, $product, [
            'quantity' => 2,
            'price' => 50,
            'status' => 'sent',
        ]);

        $this->getJson("/api/dashboard?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.sales_today', 100)
            ->assertJsonPath('data.orders_today', 2)
            ->assertJsonPath('data.occupied_tables', 1)
            ->assertJsonPath('data.active_tables', 2)
            ->assertJsonPath('data.avg_ticket', 100)
            ->assertJsonPath('data.total_orders_value', 120)
            ->assertJsonCount(12, 'data.sales_by_month')
            ->assertJsonCount(2, 'data.latest_orders')
            ->assertJsonCount(1, 'data.top_products')
            ->assertJsonPath('data.top_products.0.product_name', 'Hamburguesa')
            ->assertJsonPath('data.top_products.0.quantity', 2)
            ->assertJsonPath('data.top_products.0.total', 100);
    }

    public function test_dashboard_does_not_mix_other_branches(): void
    {
        [$user, $branch] = $this->createScenario('administrador');

        $otherBranch = Branch::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Sucursal Norte',
            'is_active' => true,
        ]);

        $table = $this->makeTable($branch, ['status' => 'free']);
        $otherTable = $this->makeTable($otherBranch, ['name' => 'Mesa Norte', 'status' => 'free']);

        $this->makeOrder($table, $user, ['status' => 'closed', 'total' => 50]);
        $this->makeOrder($otherTable, $user, ['status' => 'closed', 'total' => 400]);

        $this->getJson("/api/dashboard?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.sales_today', 50)
            ->assertJsonPath('data.total_orders_value', 50)
            ->assertJsonPath('data.orders_today', 1);
    }

    public function test_waiter_dashboard_returns_only_own_orders(): void
    {
        [$user, $branch] = $this->createScenario('mesero');
        $otherWaiter = $this->makeUser('mesero', $branch);

        $area = $this->makeArea($branch);
        $category = $this->makeCategory($branch);
        $product = $this->makeProduct($branch, $category, $area);

        $table = $this->makeTable($branch, ['status' => 'occupied']);
        $otherTable = $this->makeTable($branch, ['name' => 'Mesa 2', 'status' => 'occupied']);
        $closedTable = $this->makeTable($branch, ['name' => 'Mesa 3']);

        $ownOrder = $this->makeOrder($table, $user, ['status' => 'open', 'total' => 80]);
        $this->makeOrder($otherTable, $otherWaiter, ['status' => 'closed', 'total' => 500]);
        $this->makeOrder($closedTable, $user, ['status' => 'closed', 'total' => 30]);
        $this->makeDetail($ownOrder, $product, ['status' => 'pending']);

        $this->getJson("/api/dashboard/waiter?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.active_orders', 1)
            ->assertJsonPath('data.orders_today', 2)
            ->assertJsonPath('data.pending_items', 1)
            ->assertJsonPath('data.sales_today', 30)
            ->assertJsonCount(1, 'data.open_orders')
            ->assertJsonPath('data.open_orders.0.id', $ownOrder->id)
            ->assertJsonPath('data.open_orders.0.table_name', 'Mesa 1')
            ->assertJsonPath('data.open_orders.0.pending_items', 1);
    }
}
