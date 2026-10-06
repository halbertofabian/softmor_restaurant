<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPwaScenario;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use CreatesPwaScenario, RefreshDatabase;

    public function test_login_returns_token_role_and_branches(): void
    {
        [$user, $branch] = $this->createScenario('mesero');

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Tablet A',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('role', 'mesero')
            ->assertJsonPath('tenant_id', $this->tenantId)
            ->assertJsonPath('user.role', 'mesero')
            ->assertJsonPath('branches.0.id', $branch->id)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'estado', 'role'],
                'branches' => [['id', 'name', 'address', 'is_active']],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'pwa-Tablet A']);
    }

    public function test_login_rejects_inactive_user(): void
    {
        [$user] = $this->createScenario('mesero');
        $user->update(['estado' => 'inactivo']);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        [$user] = $this->createScenario('mesero');

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password-incorrecto',
        ])->assertUnauthorized();
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        [$user] = $this->createScenario('mesero');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'password-incorrecto',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password-incorrecto',
        ])->assertStatus(429);
    }

    public function test_me_returns_permissions_and_branches(): void
    {
        [$user, $branch] = $this->createScenario('administrador');

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('role', 'administrador')
            ->assertJsonPath('permissions.take_orders', true)
            ->assertJsonPath('permissions.reprint', true)
            ->assertJsonPath('permissions.manage_printers', true)
            ->assertJsonPath('branches.0.id', $branch->id);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_only_lists_branches_of_the_user_tenant(): void
    {
        [$user] = $this->createScenario('mesero');

        Branch::withoutEvents(fn () => Branch::create([
            'tenant_id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Sucursal Ajena',
            'is_active' => true,
        ]));

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('branches'));
        $this->assertSame('Sucursal Centro', $response->json('branches.0.name'));
    }
}
