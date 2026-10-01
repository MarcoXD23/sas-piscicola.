<?php

namespace Tests\Feature;

use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_executive_dashboard_and_financial_reports(): void
    {
        $owner = User::factory()->owner()->create(['finca_id' => null]);

        // Crear datos de prueba
        Pond::factory()->create(['finca_id' => 1, 'fish_population' => 1000, 'average_weight' => 200, 'biomass' => 200]);
        Pond::factory()->create(['finca_id' => 2, 'fish_population' => 3000, 'average_weight' => 300, 'biomass' => 900]);

        $responseOverview = $this->actingAs($owner)->getJson(route('api.owner.overview'));
        $responseOverview->assertStatus(200)
            ->assertJson([
                'kpis_operativos' => [
                    'total_estanques' => 2,
                    'biomasa_total_kg' => 1100.0,
                ],
            ]);

        $responseFinancial = $this->actingAs($owner)->getJson(route('api.owner.financial_report'));
        $responseFinancial->assertStatus(200)
            ->assertJsonStructure([
                'resumen_financiero',
                'desglose_por_alimento_y_proteina',
            ]);

        $responseTeam = $this->actingAs($owner)->getJson(route('api.owner.team_members'));
        $responseTeam->assertStatus(200)
            ->assertJsonStructure([
                'total_miembros',
                'data',
            ]);
    }

    public function test_admin_and_workers_cannot_access_owner_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $worker = User::factory()->worker()->create(['finca_id' => 1]);

        $this->actingAs($admin)->getJson(route('api.owner.overview'))
            ->assertStatus(403)
            ->assertJson([
                'error' => 'FORBIDDEN_ROLE',
            ]);

        $this->actingAs($worker)->getJson(route('api.owner.overview'))
            ->assertStatus(403)
            ->assertJson([
                'error' => 'FORBIDDEN_ROLE',
            ]);
    }

    public function test_owner_has_unrestricted_access_to_admin_and_worker_operations(): void
    {
        // Owner puede crear estanques (ruta admin) y alimentar (ruta worker)
        $owner = User::factory()->owner()->create(['finca_id' => 1]);

        // 1. Crear estanque
        $responsePond = $this->actingAs($owner)->postJson(route('api.ponds.store'), [
            'finca_id' => 1,
            'name' => 'Estanque Creado por Dueño',
            'fish_population' => 5000,
            'average_weight' => 100,
        ]);
        $responsePond->assertStatus(201);

        // 2. Crear lote de inventario (ruta admin)
        $responseFeed = $this->actingAs($owner)->postJson(route('api.feed_inventories.store'), [
            'name' => 'Alimento Premium Gerencia',
            'category' => 'Alimento Terminado',
            'brand' => 'Solla',
            'protein_percentage' => 45.0,
            'quantity_kg' => 500.0,
        ]);
        $responseFeed->assertStatus(201);
    }

    public function test_jefe_de_finca_role_alias_also_has_owner_privileges(): void
    {
        $jefeFinca = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        $response = $this->actingAs($jefeFinca)->getJson(route('api.owner.overview'));
        $response->assertStatus(200);
    }
}
