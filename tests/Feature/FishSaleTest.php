<?php

namespace Tests\Feature;

use App\Models\FishSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FishSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registers_visitor_sale_at_standard_rate_9000_per_kg(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        // 10 kilos a $9.000 = $90.000
        $response = $this->actingAs($admin)->postJson(route('api.fish_sales.store'), [
            'customer_type' => 'visitante',
            'customer_name' => 'Restaurante El Pescador',
            'kilos_sold' => 10.0,
            'payment_method' => 'efectivo',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Venta de pescado registrada exitosamente en la caja diaria.',
                'categoria_tarifa' => 'Visitante externo ($9.000/kg)',
                'data' => [
                    'customer_type' => 'visitante',
                    'kilos_sold' => 10.0,
                    'price_per_kg' => 9000.0,
                    'total_amount' => 90000.0,
                ],
            ]);

        $this->assertDatabaseHas('fish_sales', [
            'finca_id' => 1,
            'customer_type' => 'visitante',
            'kilos_sold' => 10.0,
            'price_per_kg' => 9000.0,
            'total_amount' => 90000.0,
        ]);
    }

    public function test_admin_registers_worker_sale_at_preferential_rate_7000_per_kg(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        // 5 kilos a $7.000 = $35.000
        $response = $this->actingAs($admin)->postJson(route('api.fish_sales.store'), [
            'customer_type' => 'trabajador',
            'customer_name' => 'Carlos Operario (Descuento Nómina)',
            'kilos_sold' => 5.0,
            'payment_method' => 'descuento_nomina',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Venta de pescado registrada exitosamente en la caja diaria.',
                'categoria_tarifa' => 'Trabajador interno ($7.000/kg)',
                'data' => [
                    'customer_type' => 'trabajador',
                    'kilos_sold' => 5.0,
                    'price_per_kg' => 7000.0,
                    'total_amount' => 35000.0,
                ],
            ]);

        $this->assertDatabaseHas('fish_sales', [
            'finca_id' => 1,
            'customer_type' => 'trabajador',
            'kilos_sold' => 5.0,
            'price_per_kg' => 7000.0,
            'total_amount' => 35000.0,
        ]);
    }

    public function test_daily_cashbox_computes_totals_and_breakdown_by_category(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $today = now()->toDateString();

        // 20 kg visitantes @ $9.000 = $180.000
        FishSale::create([
            'finca_id' => 1,
            'sale_date' => $today,
            'customer_type' => 'visitante',
            'customer_name' => 'Visitante 1',
            'kilos_sold' => 20.0,
            'price_per_kg' => 9000.0,
            'total_amount' => 180000.0,
            'registered_by_user_id' => $admin->id,
        ]);

        // 10 kg trabajadores @ $7.000 = $70.000
        FishSale::create([
            'finca_id' => 1,
            'sale_date' => $today,
            'customer_type' => 'trabajador',
            'customer_name' => 'Trabajador 1',
            'kilos_sold' => 10.0,
            'price_per_kg' => 7000.0,
            'total_amount' => 70000.0,
            'registered_by_user_id' => $admin->id,
        ]);

        // Total: 30 kg, $250.000
        $response = $this->actingAs($admin)->getJson(route('api.fish_sales.daily_cashbox', ['date' => $today]));

        $response->assertStatus(200)
            ->assertJson([
                'fecha_caja' => $today,
                'balance_caja_diaria' => [
                    'total_kilos_vendidos' => 30.0,
                    'total_dinero_recaudado' => 250000.0,
                ],
                'desglose_por_categoria' => [
                    'visitantes' => [
                        'kilos_vendidos' => 20.0,
                        'total_recaudado' => 180000.0,
                    ],
                    'trabajadores' => [
                        'kilos_vendidos' => 10.0,
                        'total_recaudado' => 70000.0,
                    ],
                ],
            ]);
    }

    public function test_jefe_finca_can_register_sale_via_web_interface(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        $response = $this->actingAs($jefeFinca)->postJson(route('web.ventas.store'), [
            'customer_type' => 'visitante',
            'customer_name' => 'pepe',
            'kilos_sold' => 1.0,
            'price_per_kg' => 9000,
            'payment_method' => 'efectivo',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Venta de pescado registrada exitosamente en la caja diaria.',
                'data' => [
                    'customer_name' => 'pepe',
                    'customer_type' => 'visitante',
                    'kilos_sold' => 1.0,
                    'price_per_kg' => 9000.0,
                    'total_amount' => 9000.0,
                ],
            ]);

        $this->assertDatabaseHas('fish_sales', [
            'finca_id' => 1,
            'customer_name' => 'pepe',
            'kilos_sold' => 1.0,
            'total_amount' => 9000.0,
        ]);
    }
}
