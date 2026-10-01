<?php

namespace Tests\Feature;

use App\Models\Estanque;
use App\Models\Finca;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LagosBiomasaAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca;

    private User $admin;

    private User $jefe;

    private User $trabajador;

    private User $celador;

    private Estanque $estanque;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'Piscícola San Jerónimo',
            'codigo' => 'SJN-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Carlos Administrador',
            'email' => 'admin@piscicola.com',
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $this->jefe = User::factory()->create([
            'name' => 'Don Fernando Propietario',
            'email' => 'jefe@piscicola.com',
            'role' => User::ROLE_OWNER,
            'finca_id' => 1,
        ]);

        $this->trabajador = User::factory()->create([
            'name' => 'Pedro Trabajador',
            'email' => 'pedro@piscicola.com',
            'role' => User::ROLE_TRABAJADOR,
            'finca_id' => 1,
        ]);

        $this->celador = User::factory()->create([
            'name' => 'Ramiro Celador',
            'email' => 'ramiro@piscicola.com',
            'role' => User::ROLE_CELADOR_NOCTURNO,
            'finca_id' => 1,
        ]);

        $this->estanque = Estanque::create([
            'finca_id' => 1,
            'name' => 'Lago Principal 1',
            'code' => 'LAGO-01',
            'fingerlings_stocked' => 6000,
            'fish_population' => 5800,
            'average_weight' => 480.0,
            'biomass' => 2784.0, // 5800 * 480 / 1000
            'status' => 'Sembrado',
            'stocked_at' => Carbon::now()->subDays(120),
        ]);
    }

    /**
     * Verifica que solo Administrador y Jefe Mayor puedan acceder a las rutas de lagos y muestreos.
     * Trabajador y Celador reciben error 403 Forbidden.
     */
    public function test_only_admin_and_jefe_can_access_lagos_and_muestreos_routes(): void
    {
        // 1. Trabajador intenta ingresar -> 403
        $this->actingAs($this->trabajador)->get('/admin/lagos')->assertForbidden();
        $this->actingAs($this->trabajador)->get("/admin/lagos/{$this->estanque->id}")->assertForbidden();
        $this->actingAs($this->trabajador)->get('/admin/muestreos')->assertForbidden();

        // 2. Celador Nocturno intenta ingresar -> 403
        $this->actingAs($this->celador)->get('/admin/lagos')->assertForbidden();
        $this->actingAs($this->celador)->get("/admin/lagos/{$this->estanque->id}")->assertForbidden();
        $this->actingAs($this->celador)->get('/admin/muestreos')->assertForbidden();

        // 3. Administrador accede correctamente -> 200
        $this->actingAs($this->admin)->get('/admin/lagos')->assertOk();
        $this->actingAs($this->admin)->get("/admin/lagos/{$this->estanque->id}")->assertOk();
        $this->actingAs($this->admin)->get('/admin/muestreos')->assertOk();

        // 4. Jefe Mayor accede correctamente -> 200
        $this->actingAs($this->jefe)->get('/admin/lagos')->assertOk();
        $this->actingAs($this->jefe)->get("/admin/lagos/{$this->estanque->id}")->assertOk();
        $this->actingAs($this->jefe)->get('/admin/muestreos')->assertOk();
    }

    /**
     * Verifica que el menú de navegación muestre 'Lagos y Muestreos' EXCLUSIVAMENTE para Admin y Jefe.
     */
    public function test_navigation_menu_displays_lagos_link_exclusively_for_admin_and_jefe(): void
    {
        $adminView = $this->actingAs($this->admin)->get('/admin/lagos');
        $adminView->assertSee('Lagos y Muestreos');

        $trabajadorView = $this->actingAs($this->trabajador)->get('/trabajador/dashboard');
        $trabajadorView->assertDontSee('Lagos y Muestreos');

        $celadorView = $this->actingAs($this->celador)->get('/celador/dashboard');
        $celadorView->assertDontSee('Lagos y Muestreos');
    }

    /**
     * Verifica que en los paneles de Admin y Jefe Mayor se visualicen métricas biológicas completas y alertas comerciales.
     */
    public function test_admin_and_jefe_see_live_fish_population_biomass_and_commercial_readiness_alert(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/lagos');
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('5.800'); // Peces vivos formateado
        $responseAdmin->assertSee('2.784,0 kg'); // Biomasa kg formateada
        $responseAdmin->assertSee('480,0 g'); // Peso promedio
        $responseAdmin->assertSee('Listos para Pesca Comercial'); // Alerta >= 450g

        $responseJefe = $this->actingAs($this->jefe)->get('/jefe/dashboard');
        $responseJefe->assertOk();
        $responseJefe->assertSee('Lago Principal 1');
        $responseJefe->assertSee('Listos para Pesca Comercial');
    }

    /**
     * Verifica que el dashboard del Trabajador oculte población total de peces, biomasa y días de cultivo,
     * pero conserve ración sugerida y botones de apetito y bajas.
     */
    public function test_worker_dashboard_hides_sensitive_macro_metrics_and_keeps_operational_ration(): void
    {
        $response = $this->actingAs($this->trabajador)->get('/trabajador/dashboard');
        $response->assertOk();

        // Ve la ración sugerida (2784 kg * 0.025 = 69.6 kg)
        $response->assertSee('69.6 kg');
        $response->assertSee('Lago Principal 1');
        $response->assertSee('Registrar Alimentación');
        $response->assertSee('Reportar Bajas');

        // No debe ver la biomasa total ni el conteo de población total en sus tarjetas operativas
        $response->assertDontSee('Biomasa Total');
        $response->assertDontSee('Población Viva:');
        $response->assertDontSee('Muestreos Sabatinos');
    }

    /**
     * Verifica que el Celador solo vea controles de aireadores y botón de pánico de boqueo,
     * sin exponer datos biológicos.
     */
    public function test_celador_dashboard_shows_aerators_and_panic_button_without_biological_data(): void
    {
        $response = $this->actingAs($this->celador)->get('/celador/dashboard');
        $response->assertOk();

        $response->assertSee('Lago Principal 1');
        $response->assertSee('Control de Aireadores');
        $response->assertSee('BOQUEO / FALTA DE OXÍGENO');

        $response->assertDontSee('Peces Vivos:');
        $response->assertDontSee('Biomasa Total:');
        $response->assertDontSee('Historial de Muestreos');
    }

    /**
     * Verifica que el registro de un muestreo sabatino actualice el peso promedio y biomasa del estanque.
     */
    public function test_admin_can_record_saturday_sampling_and_updates_pond_average_weight_and_biomass(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/muestreos', [
            'pond_id' => $this->estanque->id,
            'sampling_date' => Carbon::now()->toDateString(),
            'sampled_fish_count' => 50,
            'sample_total_weight_kg' => 26.0, // 26 kg / 50 pcs = 0.52 kg = 520 g por pez
            'notes' => 'Excelente crecimiento semanal y buena coloración',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->estanque->refresh();
        $this->assertEquals(520.0, (float) $this->estanque->average_weight);
        // 5800 peces * 520.0g / 1000 = 3016.0 kg
        $this->assertEquals(3016.0, (float) $this->estanque->biomass);

        $this->assertDatabaseHas('pond_samplings', [
            'pond_id' => $this->estanque->id,
            'average_weight_g' => 520.0,
            'sampled_fish_count' => 50,
        ]);
    }
}
