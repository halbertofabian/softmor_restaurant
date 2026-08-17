<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    private $tenantId;
    private $branch;
    private $admin;
    private $mesero;

    private function setupContext()
    {
        $this->tenantId = Str::uuid()->toString();
        $this->branch = Branch::create(['tenant_id' => $this->tenantId, 'name' => 'Sucursal 1', 'is_active' => true]);

        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'tenant_id' => null]);
        $meseroRole = Role::firstOrCreate(['name' => 'mesero', 'tenant_id' => null]);
        $cajaRole = Role::firstOrCreate(['name' => 'caja', 'tenant_id' => null]);

        $this->admin = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->admin->roles()->attach($adminRole->id);
        $this->admin->branches()->attach($this->branch->id, ['is_active' => true, 'tenant_id' => $this->tenantId]);

        $this->mesero = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->mesero->roles()->attach($meseroRole->id);
        $this->mesero->branches()->attach($this->branch->id, ['is_active' => true, 'tenant_id' => $this->tenantId]);

        $this->caja = User::factory()->create(['tenant_id' => $this->tenantId]);
        $this->caja->roles()->attach($cajaRole->id);
        $this->caja->branches()->attach($this->branch->id, ['is_active' => true, 'tenant_id' => $this->tenantId]);
    }

    private function makeTable($overrides = [])
    {
        $table = Table::create(array_merge([
            'name' => 'Mesa 1',
            'capacity' => 4,
            'status' => 'free',
            'is_active' => true,
            'tenant_id' => $this->tenantId,
        ], $overrides));

        // branch_id is not mass-assignable; it is normally set by the auth hook
        $table->branch_id = $this->branch->id;
        $table->save();

        return $table;
    }

    public function test_table_lifecycle()
    {
        $this->setupContext();

        // 1. Create Table (via HTTP, mirrors real flow)
        $response = $this->actingAs($this->admin)->post('/tables', [
            'name' => 'Table 1',
            'capacity' => 4,
            'status' => 'free',
            'is_active' => 'on',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tables', ['name' => 'Table 1', 'status' => 'free']);

        $table = Table::where('name', 'Table 1')->first();

        // 2. Occupy Table assigning the mesero
        $response = $this->actingAs($this->admin)->put("/tables/{$table->id}/occupy", ['waiter_id' => $this->mesero->id]);
        $response->assertSessionHasNoErrors();
        $table->refresh();
        $this->assertEquals('occupied', $table->status);
        $this->assertDatabaseHas('orders', ['table_id' => $table->id, 'user_id' => $this->mesero->id]);

        // 3. Release Table
        $response = $this->actingAs($this->admin)->put("/tables/{$table->id}/release");
        $table->refresh();
        $this->assertEquals('free', $table->status);
    }

    public function test_mesero_occupies_with_own_account()
    {
        $this->setupContext();

        $table = $this->makeTable();

        // Mesero occupies with his own account, no waiter_id needed
        $response = $this->actingAs($this->mesero)->put("/tables/{$table->id}/occupy");
        $response->assertSessionHasNoErrors();
        $table->refresh();
        $this->assertEquals('occupied', $table->status);
        $this->assertDatabaseHas('orders', ['table_id' => $table->id, 'user_id' => $this->mesero->id]);
    }

    public function test_non_mesero_must_assign_waiter_to_occupy()
    {
        $this->setupContext();

        $table = $this->makeTable();

        // Non-mesero without waiter_id → validation error, table stays free
        $response = $this->actingAs($this->admin)->put("/tables/{$table->id}/occupy");
        $response->assertSessionHasErrors('waiter_id');
        $table->refresh();
        $this->assertEquals('free', $table->status);

        // Invalid waiter (a caja user, not mesero) → rejected, table stays free
        $response = $this->actingAs($this->admin)->put("/tables/{$table->id}/occupy", ['waiter_id' => $this->caja->id]);
        $response->assertSessionHas('error');
        $table->refresh();
        $this->assertEquals('free', $table->status);

        // Valid mesero → occupied, order assigned to the mesero
        $response = $this->actingAs($this->admin)->put("/tables/{$table->id}/occupy", ['waiter_id' => $this->mesero->id]);
        $response->assertSessionHasNoErrors();
        $table->refresh();
        $this->assertEquals('occupied', $table->status);
        $this->assertDatabaseHas('orders', ['table_id' => $table->id, 'user_id' => $this->mesero->id]);
    }

    public function test_cannot_occupy_inactive_table()
    {
        $this->setupContext();

        $table = $this->makeTable(['is_active' => false]);

        $response = $this->actingAs($this->admin)->put("/tables/{$table->id}/occupy", ['waiter_id' => $this->mesero->id]);
        $response->assertSessionHas('error');
        $table->refresh();
        $this->assertEquals('free', $table->status);
    }

    public function test_admin_can_create_waiter_via_json()
    {
        $this->setupContext();

        $meseroRoleId = Role::where('name', 'mesero')->whereNull('tenant_id')->first()->id;

        $response = $this->actingAs($this->admin)->postJson('/users', [
            'name' => 'Nuevo Mesero',
            'email' => 'nuevo.mesero@example.com',
            'pais_whatsapp' => '+52 111 222 3333',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $meseroRoleId,
            'branches' => [$this->branch->id],
        ]);

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $created = User::where('email', 'nuevo.mesero@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('mesero'));
        $this->assertTrue($created->branches()->where('branches.id', $this->branch->id)->exists());
    }

    public function test_admin_can_create_waiter_without_credentials()
    {
        $this->setupContext();

        $meseroRoleId = Role::where('name', 'mesero')->whereNull('tenant_id')->first()->id;

        // No email/password: the system generates them internally
        $response = $this->actingAs($this->admin)->postJson('/users', [
            'name' => 'Mesero Sin Credenciales',
            'role_id' => $meseroRoleId,
            'branches' => [$this->branch->id],
        ]);

        $response->assertStatus(200)->assertJsonPath('status', 'success');

        $created = User::where('name', 'Mesero Sin Credenciales')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('mesero'));
        $this->assertNotNull($created->email);
        $this->assertNotNull($created->password);
    }

    public function test_non_mesero_role_still_requires_password()
    {
        $this->setupContext();

        $adminRoleId = Role::where('name', 'administrador')->whereNull('tenant_id')->first()->id;

        $response = $this->actingAs($this->admin)->post('/users', [
            'name' => 'Nuevo Admin',
            'email' => 'admin.nuevo@example.com',
            'role_id' => $adminRoleId,
            'branches' => [$this->branch->id],
        ]);

        $response->assertSessionHasErrors('password');
    }
}
