<?php

namespace Tests\Feature;

use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_daily_ration_correctly(): void
    {
        $pond = Pond::factory()->create([
            'fish_population' => 1000,
            'average_weight' => 250,
            'biomass' => 250,
        ]);

        $ration = $pond->calculateDailyRation(2.0);

        $this->assertEquals(5.0, $ration);
    }

    public function test_api_can_calculate_ration(): void
    {
        $user = User::factory()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'fish_population' => 1000,
            'average_weight' => 250,
            'biomass' => 250,
        ]);

        $response = $this->actingAs($user)->postJson(route('api.ponds.calculate_ration', $pond), [
            'feeding_rate' => 3.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Ración calculada exitosamente.',
                'pond_id' => $pond->id,
                'biomass_kg' => 250,
                'feeding_rate_percentage' => 3.0,
                'daily_ration_kg' => 7.5,
            ]);
    }

    public function test_api_can_apply_ration_and_create_feeding_log(): void
    {
        $user = User::factory()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'fish_population' => 1000,
            'average_weight' => 250,
            'biomass' => 250,
        ]);

        $feed = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Engorde Especial',
            'category' => 'Alimento Terminado',
            'brand' => 'Italcol',
            'feed_type' => 'Extrudizado',
            'protein_percentage' => 38.00,
            'bag_weight_kg' => 40.00,
            'quantity_kg' => 100.00,
        ]);

        // Ración al 2% de 250kg = 5.0 kg
        $response = $this->actingAs($user)->postJson(route('api.ponds.apply_ration', $pond), [
            'feeding_rate' => 2.0,
            'feed_inventory_id' => $feed->id,
            'observations' => 'Alimentación matutina',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Ración calculada, validada contra inventario y registrada exitosamente en la bitácora.',
                'pond_id' => $pond->id,
                'daily_ration_kg' => 5.0,
                'feed' => [
                    'id' => $feed->id,
                    'remaining_stock_kg' => 95.0,
                ],
            ]);

        $this->assertDatabaseHas('feeding_logs', [
            'finca_id' => 1,
            'pond_id' => $pond->id,
            'feed_inventory_id' => $feed->id,
            'amount_kg' => 5.0,
            'feed_brand' => 'Italcol',
            'feed_protein_percentage' => 38.00,
            'feed_bag_weight_kg' => 40.00,
            'observations' => 'Alimentación matutina',
        ]);
    }

    public function test_api_rejects_ration_when_inventory_stock_is_insufficient(): void
    {
        $user = User::factory()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'fish_population' => 1000,
            'average_weight' => 250,
            'biomass' => 250,
        ]);

        // Solo 3.0 kg disponibles
        $feed = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Engorde Bajo Stock',
            'category' => 'Alimento Terminado',
            'brand' => 'Purina',
            'feed_type' => 'Extrudizado',
            'protein_percentage' => 32.00,
            'bag_weight_kg' => 40.00,
            'quantity_kg' => 3.00,
        ]);

        // Ración al 2% de 250kg = 5.0 kg (requiere 5kg pero solo hay 3kg)
        $response = $this->actingAs($user)->postJson(route('api.ponds.apply_ration', $pond), [
            'feeding_rate' => 2.0,
            'feed_inventory_id' => $feed->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'INSUFFICIENT_STOCK',
                'required_ration_kg' => 5.0,
                'available_stock_kg' => 3.0,
                'deficit_kg' => 2.0,
            ]);

        // El inventario NO debe haberse descontado
        $this->assertEquals(3.00, $feed->fresh()->quantity_kg);

        // NO se debe haber creado ningún registro en la bitácora
        $this->assertDatabaseMissing('feeding_logs', [
            'pond_id' => $pond->id,
            'feed_inventory_id' => $feed->id,
        ]);
    }
}
