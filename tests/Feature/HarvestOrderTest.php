<?php

namespace Tests\Feature;

use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\User;
use App\Notifications\HarvestScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HarvestOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_jefe_de_finca_schedules_harvest_and_notifies_administrator(): void
    {
        Notification::fake();

        $owner = User::factory()->owner()->create(['finca_id' => 1, 'name' => 'Don Rodrigo (Jefe de Finca)']);
        $admin = User::factory()->admin()->create(['finca_id' => 1, 'name' => 'Admin Juan']);

        $pond = Pond::factory()->create([
            'finca_id' => 1,
            'name' => 'Lago No. 3 (Engorde)',
            'fish_population' => 4000,
            'average_weight' => 500,
            'biomass' => 2000,
        ]);

        $response = $this->actingAs($owner)->postJson(route('api.harvest_orders.store'), [
            'pond_id' => $pond->id,
            'scheduled_date' => '2026-10-05',
            'estimated_kg' => 1800.0,
            'observations' => 'Pesca para pedido de cadena de supermercados',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Orden de cosecha programada exitosamente y notificación enviada al Administrador.',
                'data' => [
                    'pond_id' => $pond->id,
                    'estimated_kg' => 1800.0,
                    'status' => 'programada',
                ],
            ]);

        $this->assertDatabaseHas('harvest_orders', [
            'finca_id' => 1,
            'pond_id' => $pond->id,
            'estimated_kg' => 1800.0,
            'status' => 'programada',
        ]);

        // Verificamos que el Administrador recibió la notificación
        Notification::assertSentTo($admin, HarvestScheduledNotification::class);
    }

    public function test_admin_records_scale_gross_weight_in_digital_notebook(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $owner = User::factory()->owner()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create(['finca_id' => 1, 'name' => 'Estanque 1']);

        $order = HarvestOrder::create([
            'finca_id' => 1,
            'pond_id' => $pond->id,
            'scheduled_by_user_id' => $owner->id,
            'scheduled_date' => '2026-10-05',
            'estimated_kg' => 1500.0,
            'status' => 'programada',
        ]);

        $response = $this->actingAs($admin)->postJson(route('api.harvest_orders.gross_weight', $order), [
            'gross_weight_kg' => 1540.5,
            'observations' => 'Pesaje completado en báscula digital de tolva',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Pesaje bruto en báscula registrado en la libreta digital del Administrador.',
                'data' => [
                    'id' => $order->id,
                    'gross_weight_kg' => 1540.5,
                    'status' => 'pesaje_completado',
                ],
            ]);

        $this->assertDatabaseHas('harvest_orders', [
            'id' => $order->id,
            'gross_weight_kg' => 1540.5,
            'status' => 'pesaje_completado',
            'weighed_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_records_cleaning_and_dispatch_with_driver_and_destination(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $owner = User::factory()->owner()->create(['finca_id' => 1]);

        $pond = Pond::factory()->create(['finca_id' => 1, 'name' => 'Estanque 1']);

        $order = HarvestOrder::create([
            'finca_id' => 1,
            'pond_id' => $pond->id,
            'scheduled_by_user_id' => $owner->id,
            'scheduled_date' => '2026-10-05',
            'estimated_kg' => 1000.0,
            'gross_weight_kg' => 1000.0,
            'status' => 'pesaje_completado',
        ]);

        // Kilos limpios: 880 kg (88% rendimiento de limpieza, 120 kg de merma)
        $response = $this->actingAs($admin)->postJson(route('api.harvest_orders.dispatch', $order), [
            'clean_weight_kg' => 880.0,
            'baskets_count' => 44,
            'driver_name' => 'Héctor Fabio Ramírez',
            'driver_id_card' => '1098765432',
            'driver_vehicle_plate' => 'WTF-789',
            'destination' => 'Central Mayorista de Corabastos, Bogotá',
            'observations' => 'Despacho refrigerado a 2 grados centígrados',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Despacho y proceso de limpieza registrados exitosamente.',
                'resumen_despacho' => [
                    'kilos_brutos_pescados' => 1000.0,
                    'kilos_limpios_despachados' => 880.0,
                    'merma_kg' => 120.0,
                    'rendimiento_limpieza_porcentaje' => 88.0,
                    'canastas_despachadas' => 44,
                    'conductor' => 'Héctor Fabio Ramírez',
                    'placa' => 'WTF-789',
                    'destino' => 'Central Mayorista de Corabastos, Bogotá',
                ],
            ]);

        $this->assertDatabaseHas('harvest_orders', [
            'id' => $order->id,
            'clean_weight_kg' => 880.0,
            'baskets_count' => 44,
            'driver_vehicle_plate' => 'WTF-789',
            'destination' => 'Central Mayorista de Corabastos, Bogotá',
            'status' => 'despachada',
            'dispatched_by_user_id' => $admin->id,
        ]);
    }
}
