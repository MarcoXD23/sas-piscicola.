<?php

namespace Tests\Feature;

use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_view_loads_successfully_with_kpis(): void
    {
        Pond::factory()->create(['finca_id' => 1, 'name' => 'Estanque Demo']);
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        $response = $this->actingAs($admin)->get(route('dashboard.index'));

        $response->assertStatus(200)
            ->assertSee('El SAS Piscícola')
            ->assertSee('Dashboard Ejecutivo')
            ->assertSee('Kilos Vendidos Hoy')
            ->assertSee('Dinero en Caja Hoy')
            ->assertSee('Personal Activo');
    }

    public function test_harvests_view_loads_successfully_with_weighing_scale(): void
    {
        $jefe = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        $response = $this->actingAs($jefe)->get(route('cosechas.index'));

        $response->assertStatus(200)
            ->assertSee('Calculadora de Báscula')
            ->assertSee('Canastillas')
            ->assertSee('weighingCalculator()')
            ->assertSee('Peso Limpio Real')
            ->assertSee('Volver al Dashboard');

        // Verificar que un admin/técnico acuícola no puede acceder (exclusivo Jefe Mayor)
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $this->actingAs($admin)->get(route('cosechas.index'))->assertStatus(403);
    }

    public function test_harvests_view_loads_successfully_with_existing_harvest_orders(): void
    {
        $jefe = User::factory()->jefeFinca()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1, 'name' => 'Lago Cachama Real']);

        HarvestOrder::create([
            'finca_id' => 1,
            'pond_id' => $pond->id,
            'scheduled_by_user_id' => $jefe->id,
            'scheduled_date' => now()->toDateString(),
            'estimated_kg' => 250.0,
            'gross_weight_kg' => 260.0,
            'baskets_count' => 5,
            'basket_tare_kg' => 2.0,
            'total_tare_kg' => 10.0,
            'net_weight_kg' => 250.0,
            'clean_weight_kg' => 245.0,
            'driver_name' => 'Alirio Gómez',
            'driver_vehicle_plate' => 'ABC-123',
            'destination' => 'Ibagué',
            'buyer_name' => 'Pescadería El Puerto',
            'status' => HarvestOrder::STATUS_DESPACHADA,
        ]);

        $response = $this->actingAs($jefe)->get(route('cosechas.index'));

        $response->assertStatus(200)
            ->assertSee('Lago Cachama Real')
            ->assertSee('Alirio Gómez')
            ->assertSee('Pescadería El Puerto')
            ->assertSee('245,0 kg');
    }

    public function test_payroll_view_loads_successfully_with_saturday_liquidation(): void
    {
        $jefe = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        $response = $this->actingAs($jefe)->get(route('nomina.index'));

        $response->assertStatus(200)
            ->assertSee('Liquidación Semanal')
            ->assertSee('Planilla de Personal Temporal')
            ->assertSee('Trabajadores Fijos (Excluidos')
            ->assertSee('Deducción Pescado Fiado')
            ->assertSee('Volver al Dashboard');

        // Verificar que admin no puede acceder a Nómina
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $this->actingAs($admin)->get(route('nomina.index'))->assertStatus(403);
    }

    public function test_sales_view_loads_successfully_with_cashbox_and_pricing(): void
    {
        $jefe = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        $response = $this->actingAs($jefe)->get(route('ventas.index'));

        $response->assertStatus(200)
            ->assertSee('Caja Diaria de Ventas')
            ->assertSee('Visitante Externo ($9.000 / kg)')
            ->assertSee('Trabajador Interno ($7.000 / kg)')
            ->assertSee('salesBox()')
            ->assertSee('Volver al Dashboard');

        // Verificar que admin no puede acceder a Ventas (Caja Diaria)
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $this->actingAs($admin)->get(route('ventas.index'))->assertStatus(403);
    }

    public function test_agenda_view_loads_successfully_with_back_button(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        $response = $this->actingAs($jefeFinca)->get(route('agenda.index'));

        $response->assertStatus(200)
            ->assertSee('Agenda Jefe de Finca')
            ->assertSee('Volver al Dashboard');
    }

    public function test_master_layout_includes_gemini_ai_chat_widget(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        $response = $this->actingAs($admin)->get(route('dashboard.index'));

        $response->assertStatus(200)
            ->assertSee('Asistente Gemini')
            ->assertSee('geminiChatWidget()')
            ->assertSee('Asistente IA Piscícola')
            ->assertSee('Google Gemini API');
    }
}
