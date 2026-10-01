<?php

namespace Tests\Feature;

use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_pond(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        $response = $this->actingAs($admin)->postJson(route('api.ponds.store'), [
            'finca_id' => 1,
            'name' => 'Estanque Principal Admin',
            'fish_population' => 2000,
            'average_weight' => 150.0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ponds', ['name' => 'Estanque Principal Admin']);
    }

    public function test_worker_cannot_create_pond(): void
    {
        $worker = User::factory()->worker()->create(['finca_id' => 1]);

        $response = $this->actingAs($worker)->postJson(route('api.ponds.store'), [
            'finca_id' => 1,
            'name' => 'Estanque de Worker',
            'fish_population' => 1000,
            'average_weight' => 100.0,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'FORBIDDEN_ROLE',
                'tu_rol' => 'worker',
            ]);
    }

    public function test_worker_can_apply_ration_to_pond(): void
    {
        $worker = User::factory()->worker()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'fish_population' => 1000,
            'average_weight' => 200,
            'biomass' => 200,
        ]);

        $feed = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Alimento Worker',
            'category' => 'Alimento Terminado',
            'brand' => 'Italcol',
            'protein_percentage' => 32.0,
            'quantity_kg' => 50.0,
        ]);

        $response = $this->actingAs($worker)->postJson(route('api.ponds.apply_ration', $pond), [
            'feed_inventory_id' => $feed->id,
            'feeding_rate' => 2.0,
        ]);

        $response->assertStatus(201);
    }

    public function test_guard_cannot_apply_ration_to_pond(): void
    {
        $guard = User::factory()->guard()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'fish_population' => 1000,
            'average_weight' => 200,
            'biomass' => 200,
        ]);

        $feed = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Alimento Guard',
            'category' => 'Alimento Terminado',
            'brand' => 'Purina',
            'protein_percentage' => 32.0,
            'quantity_kg' => 50.0,
        ]);

        $response = $this->actingAs($guard)->postJson(route('api.ponds.apply_ration', $pond), [
            'feed_inventory_id' => $feed->id,
            'feeding_rate' => 2.0,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'FORBIDDEN_ROLE',
                'tu_rol' => 'guard',
            ]);
    }

    public function test_guard_can_view_feed_inventory_and_logs(): void
    {
        $guard = User::factory()->guard()->create(['finca_id' => 1]);

        FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Inventario Visible Guard',
            'category' => 'Alimento Terminado',
            'quantity_kg' => 100.0,
        ]);

        $responseInventory = $this->actingAs($guard)->getJson(route('api.feed_inventories.index'));
        $responseInventory->assertStatus(200);

        $responseLogs = $this->actingAs($guard)->getJson(route('api.feeding_logs.index'));
        $responseLogs->assertStatus(200);
    }

    public function test_guard_cannot_add_feed_inventory(): void
    {
        $guard = User::factory()->guard()->create(['finca_id' => 1]);

        $response = $this->actingAs($guard)->postJson(route('api.feed_inventories.store'), [
            'name' => 'Intento de Compra',
            'quantity_kg' => 200,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'FORBIDDEN_ROLE',
                'tu_rol' => 'guard',
            ]);
    }
}
