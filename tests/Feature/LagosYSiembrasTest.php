<?php

namespace Tests\Feature;

use App\Models\Especie;
use App\Models\Estanque;
use App\Models\Finca;
use App\Models\Pond;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LagosYSiembrasTest extends TestCase
{
    use RefreshDatabase;

    protected Finca $fincaA;

    protected Finca $fincaB;

    protected User $propietario;

    protected User $tecnico;

    protected User $trabajador;

    protected User $celador;

    protected Especie $mojarraRoja;

    protected Especie $cachamaBlanca;

    protected Especie $bagreRayado;

    protected Especie $bocachico;

    protected function setUp(): void
    {
        parent::setUp();

        // Finca A (Tenant Principal)
        $this->fincaA = Finca::updateOrCreate(['id' => 1], [
            'nombre' => 'Finca Piscícola La Esperanza',
            'codigo' => 'ESP-01',
        ]);

        // Finca B (Tenant Secundario para aislamiento)
        $this->fincaB = Finca::updateOrCreate(['id' => 2], [
            'nombre' => 'Finca Acuícola El Porvenir',
            'codigo' => 'POR-02',
        ]);

        // 4 Especies oficiales solicitadas en el alcance
        $this->mojarraRoja = Especie::firstOrCreate(
            ['nombre_comun' => 'Mojarra Roja'],
            ['nombre_cientifico' => 'Oreochromis sp.', 'clima' => 'cálido']
        );

        $this->cachamaBlanca = Especie::firstOrCreate(
            ['nombre_comun' => 'Cachama Blanca'],
            ['nombre_cientifico' => 'Piaractus brachypomus', 'clima' => 'cálido']
        );

        $this->bagreRayado = Especie::firstOrCreate(
            ['nombre_comun' => 'Bagre Rayado'],
            ['nombre_cientifico' => 'Pseudoplatystoma magdaleniatum', 'clima' => 'cálido']
        );

        $this->bocachico = Especie::firstOrCreate(
            ['nombre_comun' => 'Bocachico'],
            ['nombre_cientifico' => 'Prochilodus magdalenae', 'clima' => 'cálido']
        );

        // Usuarios Finca A
        $this->propietario = User::withoutGlobalScopes()->create([
            'name' => 'Don Fernando Propietario',
            'email' => 'propietario@finca-a.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_PROPIETARIO,
            'rol' => 'propietario',
            'finca_id' => $this->fincaA->id,
            'tenant_id' => $this->fincaA->id,
            'aprobado' => true,
        ]);

        $this->tecnico = User::withoutGlobalScopes()->create([
            'name' => 'Ing. Zootecnista',
            'email' => 'tecnico@finca-a.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_TECNICO_ACUICOLA,
            'rol' => 'tecnico_acuicola',
            'finca_id' => $this->fincaA->id,
            'tenant_id' => $this->fincaA->id,
            'aprobado' => true,
        ]);

        $this->trabajador = User::withoutGlobalScopes()->create([
            'name' => 'Operario Campo',
            'email' => 'operario@finca-a.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_OPERARIO_CAMPO,
            'rol' => 'operario_alimentador',
            'finca_id' => $this->fincaA->id,
            'tenant_id' => $this->fincaA->id,
            'aprobado' => true,
        ]);

        $this->celador = User::withoutGlobalScopes()->create([
            'name' => 'Celador Noche',
            'email' => 'celador@finca-a.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_CELADOR_NOCTURNO,
            'rol' => 'celador',
            'finca_id' => $this->fincaA->id,
            'tenant_id' => $this->fincaA->id,
            'aprobado' => true,
        ]);
    }

    protected function tearDown(): void
    {
        TenantScope::setActiveTenantId(null);
        parent::tearDown();
    }

    public function test_database_is_clean_without_ponds_by_default(): void
    {
        $this->assertSame(0, Pond::withoutGlobalScopes()->count());
    }

    public function test_access_to_admin_lagos_allowed_for_propietario_and_tecnico_acuicola(): void
    {
        $this->actingAs($this->propietario)
            ->get(route('admin.lagos.index'))
            ->assertOk();

        $this->actingAs($this->tecnico)
            ->get(route('admin.lagos.index'))
            ->assertOk();
    }

    public function test_access_to_admin_lagos_forbidden_for_operario_and_celador(): void
    {
        $this->actingAs($this->trabajador)
            ->get(route('admin.lagos.index'))
            ->assertForbidden();

        $this->actingAs($this->celador)
            ->get(route('admin.lagos.index'))
            ->assertForbidden();
    }

    public function test_can_register_new_pond_with_types_tierra_geomembrana_concreto(): void
    {
        $tipos = ['tierra', 'geomembrana', 'concreto'];

        foreach ($tipos as $index => $tipo) {
            $response = $this->actingAs($this->tecnico)->postJson(route('admin.lagos.store'), [
                'nombre' => 'Estanque Tipo '.ucfirst($tipo),
                'codigo' => 'EST-0'.($index + 1),
                'tipo_estanque' => $tipo,
                'especie_id' => $this->mojarraRoja->id,
                'stocked_at' => now()->toDateString(),
                'fingerlings_stocked' => 5000,
                'average_weight' => 2.50,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('ponds', [
                'name' => 'Estanque Tipo '.ucfirst($tipo),
                'tipo_estanque' => $tipo,
                'finca_id' => $this->fincaA->id,
                'fingerlings_stocked' => 5000,
            ]);
        }
    }

    public function test_stocking_calculations_fish_population_biomass_and_days_in_culture(): void
    {
        // Siembra de 8.000 alevinos con peso promedio de 3.20 g sembrados hace 25 días
        $fechaSiembra = now()->subDays(25)->toDateString();
        $cantidadAlevinos = 8000;
        $pesoInicialGramos = 3.20;

        // Biomasa esperada en kg = (8.000 * 3.20) / 1000 = 25.60 kg
        $biomasaEsperada = round(($cantidadAlevinos * $pesoInicialGramos) / 1000, 2);

        $response = $this->actingAs($this->propietario)->postJson(route('admin.lagos.store'), [
            'nombre' => 'Lago Engorde 1',
            'codigo' => 'LAGO-ENG-01',
            'tipo_estanque' => 'tierra',
            'especie_id' => $this->cachamaBlanca->id,
            'stocked_at' => $fechaSiembra,
            'fingerlings_stocked' => $cantidadAlevinos,
            'average_weight' => $pesoInicialGramos,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'peces_vivos' => 8000,
                'biomasa_kg' => 25.60,
                'dias_cultivo' => 25,
            ]);

        $estanque = Estanque::where('code', 'LAGO-ENG-01')->firstOrFail();

        $this->assertEquals(8000, $estanque->fish_population);
        $this->assertEquals(25.60, (float) $estanque->biomass);
        $this->assertEquals(25, $estanque->days_in_culture);
        $this->assertEquals('tierra', $estanque->tipo_estanque);
    }

    public function test_species_selection_supports_mojarra_cachama_bagre_and_bocachico(): void
    {
        $especiesPrueba = [
            $this->mojarraRoja,
            $this->cachamaBlanca,
            $this->bagreRayado,
            $this->bocachico,
        ];

        foreach ($especiesPrueba as $i => $esp) {
            $resp = $this->actingAs($this->tecnico)->postJson(route('admin.lagos.store'), [
                'nombre' => 'Lote de '.$esp->nombre_comun,
                'codigo' => 'LOT-'.$i,
                'tipo_estanque' => 'geomembrana',
                'especie_id' => $esp->id,
                'stocked_at' => now()->toDateString(),
                'fingerlings_stocked' => 1000 * ($i + 1),
                'average_weight' => 2.0,
            ]);

            $resp->assertStatus(201);
            $this->assertDatabaseHas('ponds', [
                'code' => 'LOT-'.$i,
                'especie_id' => $esp->id,
            ]);
        }
    }

    public function test_tenant_isolation_ponds_and_kpis_filtered_by_tenant(): void
    {
        // Propietario Tenant B
        $propietarioB = User::withoutGlobalScopes()->create([
            'name' => 'Don Rodrigo Porvenir',
            'email' => 'propietario@finca-b.com',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_PROPIETARIO,
            'rol' => 'propietario',
            'finca_id' => $this->fincaB->id,
            'tenant_id' => $this->fincaB->id,
            'aprobado' => true,
        ]);

        // Lago en Tenant A: 10.000 peces, 2.0g -> 20.0 kg biomasa
        $this->actingAs($this->propietario)->postJson(route('admin.lagos.store'), [
            'nombre' => 'Estanque Tenant A',
            'codigo' => 'TEN-A-01',
            'tipo_estanque' => 'tierra',
            'especie_id' => $this->mojarraRoja->id,
            'stocked_at' => now()->toDateString(),
            'fingerlings_stocked' => 10000,
            'average_weight' => 2.0,
        ])->assertStatus(201);

        // Lago en Tenant B: 4.000 peces, 3.0g -> 12.0 kg biomasa
        $this->actingAs($propietarioB)->postJson(route('admin.lagos.store'), [
            'nombre' => 'Estanque Tenant B',
            'codigo' => 'TEN-B-01',
            'tipo_estanque' => 'concreto',
            'especie_id' => $this->cachamaBlanca->id,
            'stocked_at' => now()->toDateString(),
            'fingerlings_stocked' => 4000,
            'average_weight' => 3.0,
        ])->assertStatus(201);

        // Consulta desde Tenant A: solo ve 1 lago, 10.000 peces, 20 kg
        $resA = $this->actingAs($this->propietario)->getJson(route('admin.lagos.index'));
        $resA->assertStatus(200)
            ->assertJson([
                'total_lagos_count' => 1,
                'total_peces_vivos' => 10000,
                'total_biomasa_kg' => 20.0,
            ]);

        // Consulta desde Tenant B: solo ve 1 lago, 4.000 peces, 12 kg
        $resB = $this->actingAs($propietarioB)->getJson(route('admin.lagos.index'));
        $resB->assertStatus(200)
            ->assertJson([
                'total_lagos_count' => 1,
                'total_peces_vivos' => 4000,
                'total_biomasa_kg' => 12.0,
            ]);
    }

    public function test_dashboard_cards_reflect_biomass_fish_population_and_active_ponds_dynamically(): void
    {
        // Crear 2 lagos en Tenant A
        $this->actingAs($this->propietario)->postJson(route('admin.lagos.store'), [
            'nombre' => 'Estanque Alfa',
            'codigo' => 'ALF-01',
            'tipo_estanque' => 'tierra',
            'especie_id' => $this->mojarraRoja->id,
            'stocked_at' => now()->subDays(10)->toDateString(),
            'fingerlings_stocked' => 6000,
            'average_weight' => 2.0, // 12.0 kg
        ])->assertStatus(201);

        $this->actingAs($this->propietario)->postJson(route('admin.lagos.store'), [
            'nombre' => 'Estanque Beta',
            'codigo' => 'BET-02',
            'tipo_estanque' => 'geomembrana',
            'especie_id' => $this->bocachico->id,
            'stocked_at' => now()->subDays(5)->toDateString(),
            'fingerlings_stocked' => 4000,
            'average_weight' => 3.0, // 12.0 kg
        ])->assertStatus(201);

        // Total: 10.000 peces vivos, 24.0 kg biomasa, 2 lagos activos

        // Dashboard Técnico / Admin
        $resAdmin = $this->actingAs($this->tecnico)->getJson(url('/admin/dashboard'));
        $resAdmin->assertStatus(200);
        $metricasAdmin = $resAdmin->json('metricas_operativas');
        $this->assertEquals(24.0, (float) $metricasAdmin['total_biomasa_kg']);
        $this->assertEquals(10000, (int) $metricasAdmin['total_peces_vivos']);
        $this->assertEquals(2, (int) $metricasAdmin['lagos_activos_count']);

        // Dashboard Jefe / Propietario
        $resJefe = $this->actingAs($this->propietario)->getJson(url('/jefe/dashboard'));
        $resJefe->assertStatus(200);
        $metricasJefe = $resJefe->json('metricas_macro');
        $this->assertEquals(24.0, (float) $metricasJefe['biomasa_total_kg']);
        $this->assertEquals(10000, (int) $metricasJefe['peces_vivos_total']);
        $this->assertEquals(2, (int) $metricasJefe['lagos_activos_count']);
    }
}

