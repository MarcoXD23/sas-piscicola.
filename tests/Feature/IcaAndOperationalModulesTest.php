<?php

namespace Tests\Feature;

use App\Models\FeedInventory;
use App\Models\Finca;
use App\Models\FishSale;
use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\RegistroCalidadAgua;
use App\Models\RegistroMortalidad;
use App\Models\TratamientoSanitario;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IcaAndOperationalModulesTest extends TestCase
{
    use RefreshDatabase;

    protected Finca $finca;

    protected User $jefe;

    protected User $admin;

    protected User $trabajador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'Piscícola San Jerónimo SAS',
            'codigo' => 'FINCA-01',
            'ubicacion' => 'Espinal, Tolima',
            'nit' => '901.884.210-5',
            'departamento' => 'Tolima',
            'municipio' => 'El Espinal',
            'responsable_tecnico' => 'Dr. Carlos Mendoza - Zootecnista Mat. 8941',
            'registro_ica' => 'ICA-AQ-73268-2024',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->jefe = User::factory()->create([
            'finca_id' => $this->finca->id,
            'role' => User::ROLE_JEFE_MAYOR,
            'email' => 'jefe@el-sas.com',
        ]);

        $this->admin = User::factory()->admin()->create([
            'finca_id' => $this->finca->id,
            'email' => 'admin@el-sas.com',
        ]);

        $this->trabajador = User::factory()->trabajador()->create([
            'finca_id' => $this->finca->id,
            'username' => 'operario1',
        ]);
    }

    /**
     * Test 1: Desdoble y Traslado de peces actualiza automáticamente origen y destino.
     */
    public function test_fish_transfer_recalculates_population_and_biomass(): void
    {
        $origen = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Lago 1 - Alevinaje',
            'code' => 'L-01',
            'status' => 'Sembrado',
            'fish_population' => 5000,
            'average_weight' => 150.0,
            'biomass' => 750.0,
            'numero_lote' => 'LOT-2026-01',
            'alevinera_origen' => 'Acuícola del Valle',
        ]);

        $destino = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Lago 2 - Levante',
            'code' => 'L-02',
            'status' => 'Vacio',
            'fish_population' => 0,
            'average_weight' => 0.0,
            'biomass' => 0.0,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('traslados.store'), [
            'finca_id' => $this->finca->id,
            'estanque_origen_id' => $origen->id,
            'estanque_destino_id' => $destino->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 2000,
            'peso_promedio_gramos' => 150.0,
            'merma_traslado_peces' => 25,
            'motivo' => 'desdoble_densidad',
            'observaciones' => 'Desdoble por densidad hacia Levante',
        ]);

        $response->assertRedirect(route('traslados.index'));
        $response->assertSessionHas('success');

        $origen->refresh();
        $destino->refresh();

        // Origen: 5000 - 2000 traslados = 3000 peces
        $this->assertEquals(3000, $origen->fish_population);
        $this->assertEqualsWithDelta(450.0, $origen->biomass, 0.1);

        // Destino: 2000 traslados - 25 merma = 1975 peces a 150g = 296.25kg, y pasa a Sembrado
        $this->assertEquals(1975, $destino->fish_population);
        $this->assertEquals(150.0, $destino->average_weight);
        $this->assertEqualsWithDelta(296.25, $destino->biomass, 0.1);
        $this->assertEquals('Sembrado', $destino->status);

        $this->assertDatabaseHas('traslados_peces', [
            'estanque_origen_id' => $origen->id,
            'estanque_destino_id' => $destino->id,
            'cantidad_peces_trasladados' => 2000,
            'merma_traslado_peces' => 25,
        ]);
    }

    /**
     * Test 2: Período de carencia / retiro bloquea la programación de cosecha según normativa ICA.
     */
    public function test_sanitary_treatment_withdrawal_period_blocks_harvest_order(): void
    {
        $estanque = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Lago 3 - Engorde',
            'code' => 'L-03',
            'status' => 'Sembrado',
            'fish_population' => 4000,
            'average_weight' => 450.0,
            'biomass' => 1800.0,
        ]);

        // Registrar tratamiento con 15 días de retiro iniciado ayer
        $fechaAplicacion = now()->subDay()->toDateString();
        TratamientoSanitario::create([
            'finca_id' => $this->finca->id,
            'estanque_id' => $estanque->id,
            'user_id' => $this->admin->id,
            'fecha_aplicacion' => $fechaAplicacion,
            'tipo_tratamiento' => 'medicamento_veterinario',
            'producto' => 'Oxitetraciclina 20%',
            'dosis_aplicada' => '50 mg/kg biomasa en alimento',
            'dias_tiempo_retiro' => 15,
            'fecha_fin_retiro' => Carbon::parse($fechaAplicacion)->addDays(15)->toDateString(),
            'observaciones' => 'Tratamiento bacteriano preventivo',
        ]);

        $this->assertTrue($estanque->estaEnTiempoRetiro());

        // Intentar programar cosecha dentro del tiempo de retiro (en 5 días)
        $this->actingAs($this->jefe);

        $response = $this->postJson('/api/harvest-orders', [
            'pond_id' => $estanque->id,
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'estimated_kg' => 1200.0,
            'observations' => 'Cosecha programada para mayorista',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'error', 'fecha_fin_retiro', 'pond_id']);
        $response->assertJson([
            'error' => 'estanque_en_tiempo_retiro',
            'pond_id' => $estanque->id,
        ]);
        $this->assertStringContainsString('EN TIEMPO DE RETIRO', $response->json('message'));
        $this->assertStringContainsString('normativa ICA', $response->json('message'));

        // Intentar programar cosecha posterior al vencimiento del retiro (en 20 días)
        $responseSuccess = $this->postJson('/api/harvest-orders', [
            'pond_id' => $estanque->id,
            'scheduled_date' => now()->addDays(20)->toDateString(),
            'estimated_kg' => 1200.0,
            'observations' => 'Cosecha post-carencia',
        ]);

        $responseSuccess->assertStatus(201);
        $this->assertDatabaseHas('harvest_orders', [
            'pond_id' => $estanque->id,
            'status' => HarvestOrder::STATUS_PROGRAMADA,
            'estimated_kg' => 1200.0,
        ]);
    }

    /**
     * Test 3: Servicio de notificaciones WhatsApp opera con éxito en modo simulación de log.
     */
    public function test_whatsapp_notification_service_handles_alerts(): void
    {
        $estanque = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Lago 1 - Alevinaje',
            'code' => 'L-01',
            'status' => 'Sembrado',
            'fish_population' => 5000,
            'average_weight' => 150.0,
            'biomass' => 750.0,
        ]);

        $feed = FeedInventory::create([
            'finca_id' => $this->finca->id,
            'name' => 'Mojarra Iniciación 38%',
            'category' => 'alimento',
            'brand' => 'Italcol',
            'feed_type' => 'extruido',
            'protein_percentage' => 38.0,
            'bag_weight_kg' => 40.0,
            'quantity_kg' => 120.0,
            'min_stock_alert_kg' => 200.0,
        ]);

        $service = app(WhatsAppNotificationService::class);

        // 1. Alerta crítica de boqueo / oxígeno
        $boqueoRes = $service->sendCriticalBoqueoAlert($estanque, 2.3, 'Carlos Guarda');
        $this->assertTrue($boqueoRes['success']);
        $this->assertEquals('log_simulation', $boqueoRes['deliveries'][0]['driver']);

        // 2. Alerta de bodega de concentrado
        $feedRes = $service->sendFeedInventoryAlert($feed, 2.2);
        $this->assertTrue($feedRes['success']);
        $this->assertEquals('log_simulation', $feedRes['deliveries'][0]['driver']);

        // 3. Resumen de nómina sabatina
        $payrollRes = $service->sendSaturdayPayrollAlert([
            'fecha' => now()->toDateString(),
            'total_liquidado' => 650000,
            'trabajadores_count' => 4,
            'total_horas' => 96,
            'total_descuento_pescado' => 28000,
        ]);
        $this->assertTrue($payrollRes['success']);
        $this->assertEquals('log_simulation', $payrollRes['deliveries'][0]['driver']);
    }

    /**
     * Test 4: Libro de Campo Oficial del ICA (BPAP) carga con éxito y formato legal.
     */
    public function test_ica_field_book_report_loads_successfully(): void
    {
        $estanque = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Lago Principal',
            'code' => 'L-PRIN',
            'status' => 'Sembrado',
            'fish_population' => 3000,
            'average_weight' => 280.0,
            'biomass' => 840.0,
            'numero_lote' => 'LOT-ICA-2026',
            'alevinera_origen' => 'Piscícola La Esmeralda SAS',
        ]);

        RegistroCalidadAgua::create([
            'finca_id' => $this->finca->id,
            'estanque_id' => $estanque->id,
            'user_id' => $this->admin->id,
            'fecha' => now()->toDateString(),
            'hora' => '05:30 AM',
            'oxigeno_mg_l' => 5.2,
            'temperatura_c' => 28.5,
            'ph' => 7.4,
            'disco_secchi_cm' => 35,
            'observaciones' => 'Parámetros óptimos matutinos',
        ]);

        RegistroMortalidad::create([
            'finca_id' => $this->finca->id,
            'estanque_id' => $estanque->id,
            'user_id' => $this->admin->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces' => 6,
            'causa_probable' => 'manipulacion',
            'metodo_disposicion' => 'compostaje',
            'observaciones' => 'Disposición en fosa biológica compostada',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('ica.libro-campo', ['pond_id' => $estanque->id]));

        $response->assertStatus(200);
        $response->assertSee('Libro de Campo Oficial');
        $response->assertSee('ICA-AQ-73268-2024');
        $response->assertSee('901.884.210-5');
        $response->assertSee('Piscícola La Esmeralda SAS');
        $response->assertSee('COMPOSTAJE');
    }

    /**
     * Test 5: Informe Ejecutivo Mensual de Rentabilidad (Para el Dueño) calcula margen y FCR.
     */
    public function test_monthly_financial_report_loads_and_calculates_profitability(): void
    {
        // Registrar venta de pescado para el mes
        FishSale::create([
            'finca_id' => $this->finca->id,
            'customer_type' => FishSale::TYPE_VISITOR,
            'customer_name' => 'Familia Morales',
            'kilos_sold' => 50.0,
            'price_per_kg' => 9000.0,
            'total_amount' => 450000.0,
            'registered_by_user_id' => $this->admin->id,
            'sale_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->jefe);

        $response = $this->get(route('jefe.reporte-mensual', ['year' => now()->year, 'month' => now()->month]));

        $response->assertStatus(200);
        $response->assertSee('Cierre Financiero');
        $response->assertSee('FCA Global');
        $response->assertSee('450.000');
        $response->assertSee('Utilidad Neta Real');
        $response->assertSee('Costo x Kg Producido');
    }
}
