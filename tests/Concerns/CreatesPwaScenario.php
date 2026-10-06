<?php

namespace Tests\Concerns;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PreparationArea;
use App\Models\Product;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

trait CreatesPwaScenario
{
    protected string $tenantId;

    protected function createScenario(string $role = 'mesero'): array
    {
        $this->tenantId = (string) Str::uuid();

        $branch = Branch::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Sucursal Centro',
            'is_active' => true,
        ]);

        $user = $this->makeUser($role, $branch);

        Sanctum::actingAs($user);

        return [$user, $branch];
    }

    protected function makeUser(string $role, Branch $branch, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'tenant_id' => $this->tenantId,
            'estado' => 'activo',
        ], $overrides));

        $roleModel = Role::firstOrCreate(
            ['name' => $role, 'tenant_id' => null],
            ['description' => 'Rol de prueba'],
        );

        $user->roles()->attach($roleModel->id);
        $user->branches()->attach($branch->id, [
            'tenant_id' => $this->tenantId,
            'is_active' => true,
        ]);

        return $user;
    }

    protected function makeArea(Branch $branch, array $overrides = []): PreparationArea
    {
        $area = PreparationArea::create(array_merge([
            'tenant_id' => $this->tenantId,
            'name' => 'Cocina',
            'print_ticket' => true,
            'status' => true,
            'sort_order' => 0,
        ], $overrides));

        $area->branch_id = $branch->id;
        $area->save();

        return $area;
    }

    protected function makeCategory(Branch $branch, array $overrides = []): Category
    {
        $category = Category::create(array_merge([
            'tenant_id' => $this->tenantId,
            'name' => 'Platos',
            'status' => true,
        ], $overrides));

        $category->branch_id = $branch->id;
        $category->save();

        return $category;
    }

    protected function makeProduct(Branch $branch, Category $category, PreparationArea $area, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'tenant_id' => $this->tenantId,
            'branch_id' => $branch->id,
            'name' => 'Hamburguesa',
            'type' => 'dish',
            'price' => 12.50,
            'category_id' => $category->id,
            'preparation_area_id' => $area->id,
            'status' => true,
            'stock' => 0,
        ], $overrides));
    }

    protected function makeTable(Branch $branch, array $overrides = []): Table
    {
        $table = Table::create(array_merge([
            'tenant_id' => $this->tenantId,
            'name' => 'Mesa 1',
            'status' => 'free',
            'is_active' => true,
        ], $overrides));

        $table->branch_id = $branch->id;
        $table->save();

        return $table;
    }

    protected function makeOrder(Table $table, User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'tenant_id' => $this->tenantId,
            'branch_id' => $table->branch_id,
            'table_id' => $table->id,
            'user_id' => $user->id,
            'status' => 'open',
            'total' => 0,
        ], $overrides));
    }

    protected function makeDetail(Order $order, Product $product, array $overrides = []): OrderDetail
    {
        return OrderDetail::create(array_merge([
            'tenant_id' => $this->tenantId,
            'branch_id' => $order->branch_id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'preparation_area_id' => $product->preparation_area_id,
            'status' => 'pending',
        ], $overrides));
    }
}
