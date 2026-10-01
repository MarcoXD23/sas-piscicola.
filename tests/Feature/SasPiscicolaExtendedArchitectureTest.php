<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\HarvestOrder;
use App\Models\InventarioAlimento;
use App\Models\PersonalTemporal;
use App\Models\PlanSuscripcion;
use App\Models\Pond;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SasPiscicolaExtendedArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected Finca $finca;

    protected User $adminUser;

    protected Pond $pond;

    protected PlanSuscripcion $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'nombre' => 'Piscícola San Jerónimo',
            'codigo' => 'FINCA-01',
            'nit' => '900.123.456-7',
            'departamento' => 'Tolima',
            'municipio' => 'Espinal',
            'registro_ica' => 'ICA-AQU-2026-789',
            'responsable_tecnico' => 'Dr. Carlos Mendoza',
            'ubicacion' => 'Espinal, Tolima',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Administrador Finca',
            'role' => User::ROLE_ADMINISTRADOR,
            'finca_id' => $this->finca->id,
        ]);

        $this->pond = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Estanque 01 - Tilapia Roja',
            'code' => 'EST-01',
            'numero_lote' => 'LOTE-TR-2026',
            'surface_area' => 1000.00,
            'depth' => 1.50,
            'fish_population' => 5000,
            'fingerlings_stocked' => 5000,
            'average_weight' => 200.00, // 200 gramos
            'biomass' => 1000.00, // 1000 kg
            'status' => 'activo',
        ]);

        $this->plan = PlanSuscripcion::create([
            'nombre' => 'Profesional Acuícola',
            'slug' => 'profesional',
            'max_estanques' => 20,
            'max_usuarios' => 10,
            'precio_mensual' => 250000.00,
            'soporte_offline_pwa' => true,
            'telemetria_iot' => true,
            'activo' => true,
        ]);

        Suscripcion::create([
            'finca_id' => $this->finca->id,
            'plan_id' => $this->plan->id,
            'estado' => Suscripcion::ESTADO_ACTIVA,
            'fecha_inicio' => now()->subDays(5)->toDateString(),
            'fecha_vencimiento' => now()->addDays(25)->toDateString(),
            'monto_ultimo_pago' => 250000.00,
        ]);
    }

    /**
     * 1. Test Sanidad y Retiro ICA: Bloqueo estricto de Cosecha y Venta.
     */
    public function test_sanidad_ica_calculates_withdrawal_and_blocks_harvest_and_sales(): void
    {
        $this->actingAs($this->adminUser);

        // A. Registrar tratamiento sanitario con 15 días de retiro
        $fechaAplicacion = now()->toDateString();
        $response = $this->postJson(route('api.v1.sanidad.store'), [
            'estanque_id' => $this->pond->id,
            'fecha_aplicacion' => $fechaAplicacion,
            'producto' => 'Oxitetraciclina 20%',
            'principio_activo' => 'Oxitetraciclina Clorhidrato',
            'dosis' => '50 mg/kg biomasa',
            'tiempo_retiro_dias' => 15,
            'responsable' => 'Dr. Carlos Mendoza',
            'observaciones' => 'Tratamiento por sospecha bacteriana aeromonas',
        ]);

        $response->assertStatus(201);
        $expectedHabil = now()->addDays(15)->toDateString();
        $response->assertJsonPath('bloqueo_cosecha_hasta', $expectedHabil);

        // B. Verificar endpoint de verificación de retiro
        $checkResponse = $this->getJson(route('api.v1.sanidad.verificar_retiro', ['estanque' => $this->pond->id]));
        $checkResponse->assertStatus(422);
        $checkResponse->assertJsonPath('bloqueado', true);
        $checkResponse->assertJsonPath('autorizado_cosecha', false);

        // C. Verificar que HarvestOrderController bloquea la cosecha
        $harvestResponse = $this->postJson(route('api.harvest_orders.store'), [
            'pond_id' => $this->pond->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'estimated_kg' => 500,
        ]);
        $harvestResponse->assertStatus(422);
        $harvestResponse->assertJsonFragment(['error' => 'estanque_en_tiempo_retiro']);

        // D. Verificar que VentaController bloquea la venta del estanque en retiro
        $ventaResponse = $this->postJson(route('api.v1.ventas.store'), [
            'estanque_id' => $this->pond->id,
            'cliente' => 'Pescadería Central',
            'kg_vendidos' => 300,
            'precio_por_kg' => 9500,
            'forma_pago' => 'contado',
            'fecha' => now()->toDateString(),
        ]);
        $ventaResponse->assertStatus(422);
        $ventaResponse->assertJsonFragment(['error' => 'RETIRO_ICA_ACTIVO']);
    }

    /**
     * 2. Test Inventario Alimento (Bodega): Deducción transaccional y alerta por días de consumo.
     */
    public function test_alimentacion_deducts_stock_and_alerts_when_below_projected_days(): void
    {
        $this->actingAs($this->adminUser);

        // Crear alimento con stock bajo para detonar alerta (ej: 40 kg con consumo de 25 kg/día = 1.6 días)
        $alimento = InventarioAlimento::create([
            'finca_id' => $this->finca->id,
            'tipo_concentrado' => 'Engorde 28%',
            'proteina_porcentaje' => 28.00,
            'stock_actual_kg' => 45.00,
            'stock_minimo_alerta_kg' => 20.00,
            'costo_unitario' => 2900.00,
        ]);

        $response = $this->postJson(route('api.v1.alimentacion.store'), [
            'pond_id' => $this->pond->id,
            'inventario_alimento_id' => $alimento->id,
            'cantidad_kg' => 15.00,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Ración de la tarde',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inventario_alimento', [
            'id' => $alimento->id,
            'stock_actual_kg' => 30.00,
        ]);

        $response->assertJsonPath('alerta_inventario.alerta_critica', true);

        // Intentar sobregirar inventario (pedir 100 kg cuando solo quedan 30)
        $failResponse = $this->postJson(route('api.v1.alimentacion.store'), [
            'pond_id' => $this->pond->id,
            'inventario_alimento_id' => $alimento->id,
            'cantidad_kg' => 100.00,
        ]);
        $failResponse->assertStatus(422);
        $failResponse->assertJsonFragment(['error' => 'INSUFFICIENT_STOCK']);
    }

    /**
     * 3. Test Módulo de Ventas / Comercialización con cliente y liquidación.
     */
    public function test_ventas_records_sale_and_liquidates_harvest_order(): void
    {
        $this->actingAs($this->adminUser);

        // Crear estanque habilitado (sin tratamiento activo)
        $pondLibre = Pond::create([
            'finca_id' => $this->finca->id,
            'name' => 'Estanque 02 - Cachama',
            'code' => 'EST-02',
            'fish_population' => 3000,
            'average_weight' => 500.00,
            'biomass' => 1500.00,
            'status' => 'activo',
        ]);

        $harvestOrder = HarvestOrder::create([
            'pond_id' => $pondLibre->id,
            'scheduled_by_user_id' => $this->adminUser->id,
            'scheduled_date' => now()->toDateString(),
            'estimated_kg' => 800,
            'status' => HarvestOrder::STATUS_PROGRAMADA,
        ]);

        $response = $this->postJson(route('api.v1.ventas.store'), [
            'estanque_id' => $pondLibre->id,
            'harvest_order_id' => $harvestOrder->id,
            'cliente' => 'Distribuidora del Huila S.A.S.',
            'kg_vendidos' => 820.50,
            'precio_por_kg' => 9800.00,
            'forma_pago' => 'transferencia',
            'fecha' => now()->toDateString(),
            'notas' => 'Cosecha completa de estanque',
        ]);

        $response->assertStatus(201);
        $expectedTotal = round(820.50 * 9800.00, 2);
        $this->assertDatabaseHas('ventas', [
            'cliente' => 'Distribuidora del Huila S.A.S.',
            'total_venta' => $expectedTotal,
            'harvest_order_id' => $harvestOrder->id,
        ]);

        // Verificar que la orden de cosecha se actualizó con la venta
        $harvestOrder->refresh();
        $this->assertEquals('Distribuidora del Huila S.A.S.', $harvestOrder->customer_name);
        $this->assertEquals(9800.00, (float) $harvestOrder->price_per_kg);
    }

    /**
     * 4. Test Módulo de Personal y Nómina (Destajo, Jornal y Corte Sabatino).
     */
    public function test_nomina_settles_weekly_payroll_with_destajo_and_jornal(): void
    {
        $this->actingAs($this->adminUser);

        // Crear personal temporal a destajo
        $destajista = PersonalTemporal::create([
            'finca_id' => $this->finca->id,
            'nombre' => 'José Morales',
            'tipo_pago' => 'destajo',
            'tarifa' => 150.00, // $150 COP por kg eviscerado
            'deducciones' => 20000.00, // Anticipo
            'estado' => 'activo',
        ]);

        // Liquidar semana de destajo (ej. 1.200 kg procesados)
        $response = $this->postJson(route('api.v1.nomina.liquidar'), [
            'personal_temporal_id' => $destajista->id,
            'corte_sabado' => now()->endOfWeek()->toDateString(),
            'tipo_pago' => 'destajo',
            'unidades_trabajadas' => 1200,
            'tarifa' => 150.00,
            'deducciones' => 20000.00,
            'observaciones' => 'Pago por destajo de faena de cosecha',
        ]);

        $response->assertStatus(201);
        // Bruto = 1200 * 150 = 180.000. Neto = 180.000 - 20.000 = 160.000
        $this->assertDatabaseHas('liquidaciones_semanales', [
            'personal_temporal_id' => $destajista->id,
            'total_bruto' => 180000.00,
            'deducciones' => 20000.00,
            'total_neto' => 160000.00,
        ]);
    }

    /**
     * 5. Test MortalidadController: Deduce peces del estanque y recalcula biomasa.
     */
    public function test_mortalidad_decrements_fish_population_and_recalculates_biomass(): void
    {
        $this->actingAs($this->adminUser);

        // Estanque inicial: 5000 peces, 200g c/u, biomasa = 1000 kg
        $this->assertEquals(5000, $this->pond->fish_population);
        $this->assertEquals(1000.00, (float) $this->pond->biomass);

        // Reportar 200 peces muertos
        $response = $this->postJson(route('api.v1.mortalidades.store'), [
            'estanque_id' => $this->pond->id,
            'cantidad_peces' => 200,
            'causa_probable' => 'asfixia',
            'metodo_disposicion' => 'compostaje',
            'observaciones' => 'Bajas matutinas en orilla sur',
        ]);

        $response->assertStatus(201);

        $this->pond->refresh();
        // Población restante: 5000 - 200 = 4800
        $this->assertEquals(4800, $this->pond->fish_population);
        // Biomasa nueva: (4800 * 200g) / 1000 = 960 kg
        $this->assertEquals(960.00, (float) $this->pond->biomass);

        $this->assertDatabaseHas('registros_mortalidad', [
            'estanque_id' => $this->pond->id,
            'cantidad_peces' => 200,
            'causa_probable' => 'asfixia',
        ]);
    }

    /**
     * 6. Test Capa de Negocio SaaS: SuperAdmin, Onboarding y suspensión/activación.
     */
    public function test_saas_superadmin_onboarding_and_suspension_flow(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'finca_id' => $this->finca->id,
        ]);

        $this->actingAs($superAdmin);

        // A. Consultar panel Super-Admin
        $panelResponse = $this->getJson(route('api.v1.saas.fincas'));
        $panelResponse->assertStatus(200);
        $panelResponse->assertJsonPath('total_fincas', 1);

        // B. Suspender acceso a finca
        $suspendResponse = $this->postJson(route('api.v1.saas.fincas.suspender', ['finca' => $this->finca->id]));
        $suspendResponse->assertStatus(200);
        $this->assertDatabaseHas('suscripciones', [
            'finca_id' => $this->finca->id,
            'estado' => Suscripcion::ESTADO_SUSPENDIDA,
        ]);

        // C. Activar acceso a finca
        $activateResponse = $this->postJson(route('api.v1.saas.fincas.activar', ['finca' => $this->finca->id]));
        $activateResponse->assertStatus(200);
        $this->assertDatabaseHas('suscripciones', [
            'finca_id' => $this->finca->id,
            'estado' => Suscripcion::ESTADO_ACTIVA,
        ]);

        // D. Onboarding de nueva finca
        $onboardingResponse = $this->postJson(route('api.v1.saas.onboarding'), [
            'nombre' => 'Acuícola La Esmeralda',
            'nit' => '901.888.777-1',
            'departamento' => 'Huila',
            'municipio' => 'Betania',
            'registro_ica' => 'ICA-BET-9901',
            'responsable_tecnico' => 'Ing. Marta Rivera',
            'plan_id' => $this->plan->id,
            'admin_nombre' => 'Marta Rivera',
            'admin_email' => 'marta.rivera@laesmeralda.com',
            'admin_password' => 'Password123!',
            'cantidad_estanques_inicial' => 4,
        ]);

        $onboardingResponse->assertStatus(201);
        $this->assertDatabaseHas('fincas', [
            'nombre' => 'Acuícola La Esmeralda',
            'nit' => '901.888.777-1',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'marta.rivera@laesmeralda.com',
            'role' => User::ROLE_ADMINISTRADOR,
        ]);
    }

    /**
     * 7. Test Modo Offline para Campo: Sincronización por lotes con Idempotencia y Last-Write-Wins.
     */
    public function test_offline_sync_processes_batch_with_idempotency_and_lww(): void
    {
        $this->actingAs($this->adminUser);

        $clientUuid1 = (string) Str::uuid();
        $clientUuid2 = (string) Str::uuid();

        // Lote de sincronización offline con una alimentación y una mortalidad
        $payload = [
            'records' => [
                [
                    'client_uuid' => $clientUuid1,
                    'table_name' => 'mortalidad',
                    'action' => 'create',
                    'device_timestamp' => now()->toIso8601String(),
                    'payload' => [
                        'estanque_id' => $this->pond->id,
                        'cantidad_peces' => 50,
                        'causa_probable' => 'depredador',
                        'metodo_disposicion' => 'fosa',
                        'observaciones' => 'Garzas en la madrugada',
                    ],
                ],
                [
                    'client_uuid' => $clientUuid2,
                    'table_name' => 'calidad_agua',
                    'action' => 'create',
                    'device_timestamp' => now()->toIso8601String(),
                    'payload' => [
                        'estanque_id' => $this->pond->id,
                        'oxigeno_mg_l' => 6.2,
                        'temperatura_c' => 28.0,
                        'ph' => 7.4,
                    ],
                ],
            ],
        ];

        // 1. Primer envío
        $syncResponse = $this->postJson(route('api.v1.sync'), $payload);
        $syncResponse->assertStatus(200);
        $syncResponse->assertJsonPath('total_procesados', 2);

        $this->assertDatabaseHas('offline_sync_logs', [
            'client_uuid' => $clientUuid1,
            'status' => 'synced',
        ]);

        // 2. Re-envío (Idempotencia: no debe duplicar la resta de mortalidad)
        $poblacionAntes = $this->pond->fresh()->fish_population;

        $syncDuplicateResponse = $this->postJson(route('api.v1.sync'), $payload);
        $syncDuplicateResponse->assertStatus(200);

        // La población debe mantenerse idéntica porque el UUID fue detectado como procesado
        $this->assertEquals($poblacionAntes, $this->pond->fresh()->fish_population);
    }
}
