<?php

namespace Tests\Feature;

use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_lifecycle()
    {
        $user = User::factory()->create();

        // 1. Create Table
        $response = $this->actingAs($user)->post('/tables', [
            'name' => 'Table 1',
            'capacity' => 4,
            'status' => 'free',
            'is_active' => 'on',
        ]);
        $response->assertRedirect('/tables');
        $this->assertDatabaseHas('tables', ['name' => 'Table 1', 'status' => 'free']);

        $table = Table::where('name', 'Table 1')->first();

        // 2. Occupy Table
        $response = $this->actingAs($user)->put("/tables/{$table->id}/occupy");
        $response->assertSessionHasNoErrors();
        $table->refresh();
        $this->assertEquals('occupied', $table->status);

        // 3. Release Table
        $response = $this->actingAs($user)->put("/tables/{$table->id}/release");
        $table->refresh();
        $this->assertEquals('free', $table->status);
    }

    public function test_cannot_occupy_inactive_table()
    {
        $user = User::factory()->create();
        $table = Table::create(['name' => 'Inactive Table', 'is_active' => false, 'status' => 'free']);

        $response = $this->actingAs($user)->put("/tables/{$table->id}/occupy");
        
        // Should redirect back with error
        $response->assertSessionHas('error');
        $table->refresh();
        $this->assertEquals('free', $table->status);
    }
}
