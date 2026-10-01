<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestWeighingAndTareTest extends TestCase
{
    use RefreshDatabase;

    protected Finca $finca;

    protected User $admin;

    protected Pond $pond;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'El SAS Piscícola',
            'codigo' => 'SAS-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->admin = User::factory()->create([
            'finca_id' => 1,
            'email' => 'admin@finca.com',
            'role' => User::ROLE_JEFE_MAYOR,
        ]);

        $this->pond = Pond::create([
            'finca_id' => 1,
            'name' => 'Estanque 1 - Mojarra Roja',
            'code' => 'EST-01',
            'fish_population' => 10000,
            'average_weight' => 300.0,
            'biomass' => 3000.0,
            'status' => 'Sembrado',
        ]);
    }

    /**
     * 1. La vista de Báscula y Cosechas muestra las columnas y selectores de Canastilla y Canasta.
     */
    public function test_harvest_view_loads_with_canastas_and_canastillas_options(): void
    {
        $response = $this->actingAs($this->admin)->get(route('cosechas.index'));

        $response->assertStatus(200);
        $response->assertSee('Calculadora de Báscula');
        $response->assertSee('Canastillas y Canastas');
        $response->assertSee('Tipo / Tara Unitaria (kg)');
        $response->assertSee('Canastilla');
        $response->assertSee('Canasta');
        $response->assertSee('Personalizada');
        $response->assertSee('Subtotal Limpio');
        $response->assertSee('Descuento Tara Canastillas');
        $response->assertSee('Peso Limpio Real a Despacho');
    }

    /**
     * 2. Registrar despacho guarda los tipos de canasta, peso unitario y tara por tanda en la base de datos.
     */
    public function test_store_dispatch_session_records_batches_and_tare_breakdown(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'pond_id' => $this->pond->id,
            'clean_weight_kg' => 328.30,
            'gross_weight_kg' => 345.50,
            'total_tare_kg' => 17.20,
            'baskets_count' => 9,
            'driver_name' => 'Don Humberto Camargo',
            'driver_id_card' => '93456789',
            'driver_vehicle_plate' => 'TZR-890',
            'destination' => 'Central Corabastos Bogotá',
            'buyer_name' => 'Mayorista La Esperanza',
            'observations' => 'Despacho con hielo escamado y oxigenación',
            'batches' => [
                [
                    'batch_number' => 1,
                    'container_type' => 'canastilla',
                    'unit_tare' => 2.0,
                    'gross_kg' => 180.5,
                    'baskets' => 5,
                    'total_tare' => 10.0,
                    'subtotal_clean' => 170.5,
                ],
                [
                    'batch_number' => 2,
                    'container_type' => 'canasta',
                    'unit_tare' => 1.8,
                    'gross_kg' => 165.0,
                    'baskets' => 4,
                    'total_tare' => 7.2,
                    'subtotal_clean' => 157.8,
                ],
            ],
        ];

        $response = $this->postJson(route('web.cosechas.dispatch'), $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => '¡Despacho registrado con éxito con detalle de tandas y tara!',
            'resumen_despacho' => [
                'kilos_brutos_pescados' => 345.50,
                'tara_total_descontada_kg' => 17.20,
                'kilos_limpios_despachados' => 328.30,
                'canastas_despachadas' => 9,
                'conductor' => 'Don Humberto Camargo',
                'placa' => 'TZR-890',
            ],
        ]);

        $this->assertDatabaseHas('harvest_orders', [
            'pond_id' => $this->pond->id,
            'clean_weight_kg' => 328.30,
            'gross_weight_kg' => 345.50,
            'total_tare_kg' => 17.20,
            'baskets_count' => 9,
            'status' => HarvestOrder::STATUS_DESPACHADA,
            'driver_name' => 'Don Humberto Camargo',
            'driver_vehicle_plate' => 'TZR-890',
        ]);

        $order = HarvestOrder::latest()->first();
        $this->assertNotNull($order->weighing_batches);
        $this->assertCount(2, $order->weighing_batches);
        $this->assertEquals('canastilla', $order->weighing_batches[0]['container_type']);
        $this->assertEquals(2.0, $order->weighing_batches[0]['unit_tare']);
        $this->assertEquals('canasta', $order->weighing_batches[1]['container_type']);
        $this->assertEquals(1.8, $order->weighing_batches[1]['unit_tare']);
    }

    /**
     * 3. La ruta API 'api/harvest-orders/dispatch-session' opera de forma consistente.
     */
    public function test_api_dispatch_session_endpoint(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/api/harvest-orders/dispatch-session', [
            'pond_id' => $this->pond->id,
            'clean_weight_kg' => 195.0,
            'gross_weight_kg' => 205.0,
            'total_tare_kg' => 10.0,
            'baskets_count' => 5,
            'driver_name' => 'Pedro Sánchez',
            'driver_vehicle_plate' => 'ABC-123',
            'destination' => 'Ibagué, Tolima',
            'batches' => [
                [
                    'batch_number' => 1,
                    'container_type' => 'canastilla',
                    'unit_tare' => 2.0,
                    'gross_kg' => 205.0,
                    'baskets' => 5,
                    'total_tare' => 10.0,
                    'subtotal_clean' => 195.0,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('harvest_orders', [
            'clean_weight_kg' => 195.0,
            'driver_vehicle_plate' => 'ABC-123',
        ]);
    }
}
