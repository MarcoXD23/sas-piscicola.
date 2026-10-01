<?php

namespace Tests\Feature;

use App\Models\Especie;
use App\Models\Estanque;
use App\Models\Pond;
use App\Models\PondEspecie;
use App\Models\User;
use Database\Seeders\EspecieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicultivoLagosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EspecieSeeder::class);
    }

    public function test_can_register_lake_with_multiple_species_polyculture(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $cachama = Especie::where('nombre_cientifico', 'Piaractus brachypomus')->firstOrFail();
        $bocachico = Especie::where('nombre_cientifico', 'Prochilodus magdalenae')->firstOrFail();

        // Siembra de policultivo: 8.000 Cachamas a 1.5g + 2.000 Bocachicos a 2.0g
        // Biomasa Cachama: (8000 * 1.5) / 1000 = 12.0 kg
        // Biomasa Bocachico: (2000 * 2.0) / 1000 = 4.0 kg
        // Total peces: 10.000
        // Total biomasa: 16.0 kg
        $payload = [
            'name' => 'Lago Policultivo Tolima 1',
            'code' => 'POLI-01',
            'tipo_estanque' => 'tierra',
            'stocked_at' => now()->toDateString(),
            'numero_lote' => 'LOTE-POLI-2026',
            'alevinera_origen' => 'Piscícola del Tolima',
            'especies' => [
                [
                    'especie_id' => $cachama->id,
                    'cantidad' => 8000,
                    'peso' => 1.50,
                ],
                [
                    'especie_id' => $bocachico->id,
                    'cantidad' => 2000,
                    'peso' => 2.00,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.lagos.store'), $payload);

        $response->assertRedirect(route('admin.lagos.index'));

        // Verificar estanque consolidado en ponds
        $pond = Estanque::where('code', 'POLI-01')->firstOrFail();
        $this->assertEquals('Lago Policultivo Tolima 1', $pond->name);
        $this->assertEquals(10000, $pond->fish_population);
        $this->assertEquals(10000, $pond->fingerlings_stocked);
        $this->assertEquals(16.00, (float) $pond->biomass);
        $this->assertTrue($pond->es_policultivo);
        $this->assertEquals($cachama->id, $pond->especie_id);

        // Verificar desglose independiente en pond_especies
        $detalles = PondEspecie::where('pond_id', $pond->id)->get();
        $this->assertCount(2, $detalles);

        $detalleCachama = $detalles->where('especie_id', $cachama->id)->first();
        $this->assertNotNull($detalleCachama);
        $this->assertEquals(8000, $detalleCachama->fish_population);
        $this->assertEquals(8000, $detalleCachama->fingerlings_stocked);
        $this->assertEquals(1.50, (float) $detalleCachama->average_weight);
        $this->assertEquals(12.00, (float) $detalleCachama->biomass);

        $detalleBocachico = $detalles->where('especie_id', $bocachico->id)->first();
        $this->assertNotNull($detalleBocachico);
        $this->assertEquals(2000, $detalleBocachico->fish_population);
        $this->assertEquals(2000, $detalleBocachico->fingerlings_stocked);
        $this->assertEquals(2.00, (float) $detalleBocachico->average_weight);
        $this->assertEquals(4.00, (float) $detalleBocachico->biomass);
    }

    public function test_can_register_three_species_polyculture(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $mojarraRoja = Especie::where('nombre_cientifico', 'Oreochromis sp.')->firstOrFail();
        $bocachico = Especie::where('nombre_cientifico', 'Prochilodus magdalenae')->firstOrFail();
        $bagre = Especie::where('nombre_cientifico', 'Pseudoplatystoma fasciatum')->firstOrFail();

        // 3 Especies: 5.000 Mojarras (1.0g = 5.0kg) + 1.000 Bocachicos (2.0g = 2.0kg) + 200 Bagres (10.0g = 2.0kg)
        // Total población: 6.200 peces
        // Total biomasa: 9.0 kg
        $payload = [
            'name' => 'Lago Tri-Policultivo',
            'code' => 'TRI-01',
            'tipo_estanque' => 'geomembrana',
            'stocked_at' => now()->toDateString(),
            'especies' => [
                [
                    'especie_id' => $mojarraRoja->id,
                    'cantidad' => 5000,
                    'peso' => 1.00,
                ],
                [
                    'especie_id' => $bocachico->id,
                    'cantidad' => 1000,
                    'peso' => 2.00,
                ],
                [
                    'especie_id' => $bagre->id,
                    'cantidad' => 200,
                    'peso' => 10.00,
                ],
            ],
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.lagos.store'), $payload);

        $response->assertRedirect(route('admin.lagos.index'));

        $pond = Estanque::where('code', 'TRI-01')->firstOrFail();
        $this->assertEquals(6200, $pond->fish_population);
        $this->assertEquals(9.00, (float) $pond->biomass);
        $this->assertTrue($pond->es_policultivo);
        $this->assertCount(3, $pond->especiesDetalle);
    }

    public function test_maintains_backward_compatibility_with_monoculture_registration(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $mojarraNegra = Especie::where('nombre_cientifico', 'Oreochromis niloticus')->firstOrFail();

        // Envío clásico con especie_id, fingerlings_stocked y average_weight
        $payload = [
            'name' => 'Estanque Monocultivo Tradicional',
            'code' => 'MONO-01',
            'tipo_estanque' => 'tierra',
            'especie_id' => $mojarraNegra->id,
            'stocked_at' => now()->toDateString(),
            'fingerlings_stocked' => 5000,
            'average_weight' => 2.50,
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.lagos.store'), $payload);

        $response->assertRedirect(route('admin.lagos.index'));

        $pond = Estanque::where('code', 'MONO-01')->firstOrFail();
        $this->assertEquals(5000, $pond->fish_population);
        $this->assertEquals(12.50, (float) $pond->biomass);
        $this->assertFalse($pond->es_policultivo);
        $this->assertEquals($mojarraNegra->id, $pond->especie_id);

        // Se registra 1 detalle en pond_especies para consistencia
        $this->assertCount(1, $pond->especiesDetalle);
    }

    public function test_polyculture_respects_multi_tenant_isolation_by_finca_id(): void
    {
        $adminFinca1 = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $adminFinca2 = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'finca_id' => 2,
        ]);

        $cachama = Especie::where('nombre_cientifico', 'Piaractus brachypomus')->firstOrFail();
        $bocachico = Especie::where('nombre_cientifico', 'Prochilodus magdalenae')->firstOrFail();

        // Registrar policultivo en Finca 1
        $this->actingAs($adminFinca1)->post(route('admin.lagos.store'), [
            'name' => 'Lago Privado Finca 1',
            'code' => 'F1-POLI',
            'tipo_estanque' => 'tierra',
            'stocked_at' => now()->toDateString(),
            'especies' => [
                ['especie_id' => $cachama->id, 'cantidad' => 3000, 'peso' => 1.5],
                ['especie_id' => $bocachico->id, 'cantidad' => 1000, 'peso' => 2.0],
            ],
        ]);

        // Finca 1 debe ver su lago
        $resFinca1 = $this->actingAs($adminFinca1)->get(route('admin.lagos.index'));
        $resFinca1->assertStatus(200)
            ->assertSee('Lago Privado Finca 1')
            ->assertSee('Policultivo')
            ->assertSee('Cachama Blanca')
            ->assertSee('Bocachico');

        // Finca 2 NO debe ver el lago de Finca 1
        $resFinca2 = $this->actingAs($adminFinca2)->get(route('admin.lagos.index'));
        $resFinca2->assertStatus(200)
            ->assertDontSee('Lago Privado Finca 1')
            ->assertDontSee('F1-POLI');
    }

    public function test_view_renders_polyculture_badge_and_species_breakdown(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $cachama = Especie::where('nombre_cientifico', 'Piaractus brachypomus')->firstOrFail();
        $bocachico = Especie::where('nombre_cientifico', 'Prochilodus magdalenae')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.lagos.store'), [
            'name' => 'Lago Tolima Especial',
            'code' => 'TOL-99',
            'tipo_estanque' => 'tierra',
            'stocked_at' => now()->toDateString(),
            'especies' => [
                ['especie_id' => $cachama->id, 'cantidad' => 8000, 'peso' => 1.5],
                ['especie_id' => $bocachico->id, 'cantidad' => 2000, 'peso' => 2.0],
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.lagos.index'));

        $response->assertStatus(200)
            ->assertSee('Lago Tolima Especial')
            ->assertSee('Policultivo (2 especies)')
            ->assertSee('Cachama Blanca:')
            ->assertSee('Bocachico:')
            ->assertSee('8.000')
            ->assertSee('2.000');
    }
}

