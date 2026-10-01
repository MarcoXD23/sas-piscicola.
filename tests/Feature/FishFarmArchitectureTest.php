<?php

namespace Tests\Feature;

use App\Models\AdminTask;
use App\Models\DailyLabor;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\FishCredit;
use App\Models\FishingAttendance;
use App\Models\FishSale;
use App\Models\HarvestOrder;
use App\Models\LeaveRequest;
use App\Models\Pond;
use App\Models\RotativeSchedule;
use App\Models\User;
use App\Models\WarehouseMovement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FishFarmArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private User $jefeMayor;

    private User $administrador;

    private User $trabajadorDestajo;

    private User $trabajadorFijo;

    private Pond $lago1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jefeMayor = User::factory()->create([
            'name' => 'Don Fernando (Jefe Mayor)',
            'email' => 'jefe@sas-piscicola.com',
            'role' => User::ROLE_JEFE_MAYOR,
            'finca_id' => 1,
        ]);

        $this->administrador = User::factory()->create([
            'name' => 'Carlos Gomez (Administrador)',
            'email' => 'admin@sas-piscicola.com',
            'role' => User::ROLE_ADMINISTRADOR,
            'finca_id' => 1,
        ]);

        $this->trabajadorDestajo = User::factory()->create([
            'name' => 'Pedro Operario',
            'username' => 'pedro.destajo',
            'document_number' => '1001234567',
            'role' => User::ROLE_TRABAJADOR,
            'employment_type' => User::TYPE_DESTAJO_SEMANAL,
            'finca_id' => 1,
        ]);

        $this->trabajadorFijo = User::factory()->create([
            'name' => 'Manuel Fijo',
            'username' => 'manuel.fijo',
            'document_number' => '1007654321',
            'role' => User::ROLE_TRABAJADOR,
            'employment_type' => User::TYPE_FIJO,
            'accumulated_fish_credit' => 0.0,
            'finca_id' => 1,
        ]);

        $this->lago1 = Pond::create([
            'finca_id' => 1,
            'name' => 'Lago Tilapia Roja 1',
            'code' => 'LAG-01',
            'fingerlings_stocked' => 10000,
            'stocked_at' => now()->subDays(60)->toDateString(),
            'fish_population' => 10000,
            'average_weight' => 250.0,
            'biomass' => 2500.0,
            'status' => 'activo',
        ]);
    }

    // =========================================================================
    // 1. AUTENTICACIÓN Y ROLES
    // =========================================================================

    public function test_roles_and_access_restrictions_to_dedicated_dashboards(): void
    {
        // Jefe Mayor accede a su panel macro y a los demás paneles por rol gerencial
        $responseJefe = $this->actingAs($this->jefeMayor)->get(route('dashboard.jefe'));
        $responseJefe->assertOk()->assertSee('Panel Macro - Jefe Mayor');

        // Administrador accede a su panel de operaciones
        $responseAdmin = $this->actingAs($this->administrador)->get(route('dashboard.admin'));
        $responseAdmin->assertOk()->assertSee('Panel de Control - Administrador');

        // Trabajador accede a su portal operativo
        $responseTrabajador = $this->actingAs($this->trabajadorDestajo)->get(route('dashboard.trabajador'));
        $responseTrabajador->assertOk()->assertSee('Mi Portal Operativo de Granja');

        // Trabajador NO puede ingresar al panel del administrador ni del jefe mayor
        $forbiddenAdmin = $this->actingAs($this->trabajadorDestajo)->get(route('dashboard.admin'));
        $forbiddenAdmin->assertForbidden();

        $forbiddenJefe = $this->actingAs($this->trabajadorDestajo)->get(route('dashboard.jefe'));
        $forbiddenJefe->assertForbidden();
    }

    public function test_central_dashboard_route_loads_for_authenticated_users(): void
    {
        $this->actingAs($this->jefeMayor)
            ->get(route('dashboard.index'))
            ->assertOk();

        $this->actingAs($this->administrador)
            ->get(route('dashboard.index'))
            ->assertOk();

        $this->actingAs($this->trabajadorDestajo)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    // =========================================================================
    // 2. TRABAJADORES, ASISTENCIA Y NÓMINA SABATINA
    // =========================================================================

    public function test_fishing_attendance_and_saturday_payroll_settlement_with_fish_discount(): void
    {
        $cutoffDate = now()->isSaturday() ? now() : now()->next(Carbon::SATURDAY);
        $monday = $cutoffDate->copy()->startOfWeek()->toDateString();
        $saturday = $cutoffDate->toDateString();

        // 1. Asistencia a faena de pesca el lunes para el trabajador a destajo ($60.000)
        FishingAttendance::create([
            'finca_id' => 1,
            'user_id' => $this->trabajadorDestajo->id,
            'attendance_date' => $monday,
            'attended' => true,
            'day_of_week' => 'lunes',
            'daily_wage' => 60000.0,
            'role_in_harvest' => 'Atarraya y selección',
            'registered_by_user_id' => $this->administrador->id,
        ]);

        // Jornal regular adicional para el trabajador a destajo
        DailyLabor::create([
            'finca_id' => 1,
            'user_id' => $this->trabajadorDestajo->id,
            'worker_name' => $this->trabajadorDestajo->name,
            'worker_id_card' => $this->trabajadorDestajo->document_number,
            'employment_type' => 'temporal',
            'work_date' => $monday,
            'labor_type' => 'rayadores',
            'daily_wage' => 60000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $this->administrador->id,
        ]);

        // Asistencia para el trabajador fijo (control interno, excluido del pago de sábado)
        FishingAttendance::create([
            'finca_id' => 1,
            'user_id' => $this->trabajadorFijo->id,
            'attendance_date' => $monday,
            'attended' => true,
            'day_of_week' => 'lunes',
            'daily_wage' => 0.0,
            'role_in_harvest' => 'Supervisión en orilla',
            'registered_by_user_id' => $this->administrador->id,
        ]);

        // 2. Descuento de pescado fiado a tarifa $7.000/kg
        // Trabajador destajo retira 5 kg de pescado fiado (5 * 7000 = $35.000)
        FishCredit::create([
            'finca_id' => 1,
            'user_id' => $this->trabajadorDestajo->id,
            'credit_date' => $monday,
            'kilos' => 5.0,
            'price_per_kg' => 7000.0,
            'status' => FishCredit::STATUS_PENDIENTE,
            'registered_by_user_id' => $this->administrador->id,
        ]);

        // Trabajador fijo retira 4 kg de pescado fiado (4 * 7000 = $28.000)
        FishCredit::create([
            'finca_id' => 1,
            'user_id' => $this->trabajadorFijo->id,
            'credit_date' => $monday,
            'kilos' => 4.0,
            'price_per_kg' => 7000.0,
            'status' => FishCredit::STATUS_PENDIENTE,
            'registered_by_user_id' => $this->administrador->id,
        ]);

        // 3. Simular cálculo de nómina sabatina
        $calcResponse = $this->actingAs($this->administrador)
            ->getJson("/api/weekly-payroll/calculate?cutoff_date={$saturday}");

        $calcResponse->assertOk();
        $calcData = $calcResponse->json();

        // Destajo: Devengado = 60.000 + 60.000 = 120.000. Deducción pescado = 35.000. Neto = 85.000
        $tempWorkerData = collect($calcData['nomina_personal_temporal'])->firstWhere('user_id', $this->trabajadorDestajo->id);
        $this->assertNotNull($tempWorkerData);
        $this->assertEquals(120000.0, (float) $tempWorkerData['acumulado_jornales']);
        $this->assertEquals(35000.0, (float) $tempWorkerData['descuento_pescado_fiado']);
        $this->assertEquals(85000.0, (float) $tempWorkerData['total_neto_a_pagar']);

        // Fijo: Excluido de liquidación sabatina
        $fijoWorkerData = collect($calcData['trabajadores_fijos_excluidos'])->firstWhere('user_id', $this->trabajadorFijo->id);
        $this->assertNotNull($fijoWorkerData);
        $this->assertTrue($fijoWorkerData['excluido_de_liquidacion']);
        $this->assertEquals(0.0, (float) $fijoWorkerData['paga_acumulada_sabado']);
        $this->assertEquals(28000.0, (float) $fijoWorkerData['descuento_pescado_fiado']);

        // 4. Liquidar y cerrar la nómina sabatina
        $settleResponse = $this->actingAs($this->administrador)
            ->postJson('/api/weekly-payroll/settle', ['cutoff_date' => $saturday]);

        $settleResponse->assertStatus(201);

        // Validar que el saldo del trabajador fijo se acumuló en su cuenta mensual
        $this->trabajadorFijo->refresh();
        $this->assertEquals(28000.0, (float) $this->trabajadorFijo->accumulated_fish_credit);

        // 5. Exportar reporte CSV descargable
        $csvResponse = $this->actingAs($this->administrador)
            ->get("/api/weekly-payroll/export-csv?cutoff_date={$saturday}");

        $csvResponse->assertOk();
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    // =========================================================================
    // 3. LAGOS Y MUESTREO SABATINO
    // =========================================================================

    public function test_pond_lifecycle_metrics_and_saturday_sampling_calculation(): void
    {
        // 1. Validar cálculo automático de tiempo transcurrido en cultivo
        $this->assertEquals(60, $this->lago1->days_in_culture);
        $this->assertEquals(2.0, $this->lago1->months_in_culture);

        // 2. Registrar muestreo sabatino semanal
        // Muestra de 50 peces, peso total 25.0 kg -> promedio = (25.0 * 1000) / 50 = 500g
        // Conteo tallas: Pequeña: 5, Mediana: 15, Grande: 20, Comercial: 10 (Total 50)
        // Porcentajes: Pequeña 10%, Mediana 30%, Grande 40%, Comercial 20%
        $samplingResponse = $this->actingAs($this->administrador)->postJson('/api/pond-samplings', [
            'pond_id' => $this->lago1->id,
            'sampling_date' => now()->toDateString(),
            'sampled_fish_count' => 50,
            'sample_total_weight_kg' => 25.0,
            'small_count' => 5,
            'medium_count' => 15,
            'large_count' => 20,
            'commercial_count' => 10,
            'notes' => 'Muestreo sabatino rutinario estanque 1',
        ]);

        $samplingResponse->assertStatus(201);
        $samplingData = $samplingResponse->json();

        $this->assertEquals(500.0, (float) $samplingData['peso_promedio_g']);
        $this->assertEquals('10%', $samplingData['distribucion_tallas']['pequena']['porcentaje']);
        $this->assertEquals('30%', $samplingData['distribucion_tallas']['mediana']['porcentaje']);
        $this->assertEquals('40%', $samplingData['distribucion_tallas']['grande']['porcentaje']);
        $this->assertEquals('20%', $samplingData['distribucion_tallas']['comercial']['porcentaje']);

        // El lago debe actualizar automáticamente su peso promedio y su biomasa estimada (10.000 peces * 500g = 5.000 kg)
        $this->lago1->refresh();
        $this->assertEquals(500.0, (float) $this->lago1->average_weight);
        $this->assertEquals(5000.0, (float) $this->lago1->biomass);
    }

    // =========================================================================
    // 4. ALIMENTACIÓN, BODEGA Y TURNOS ROTATIVOS
    // =========================================================================

    public function test_feed_inventory_movements_days_remaining_and_rotating_shifts(): void
    {
        // 1. Crear insumo de alimento en bodega
        $alimento = FeedInventory::create([
            'finca_id' => 1,
            'name' => 'Mojarra Engorde 32%',
            'category' => 'Alimento Terminado',
            'bag_weight_kg' => 40.0,
            'quantity_kg' => 1000.0,
            'min_stock_alert_kg' => 300.0,
        ]);

        // Registrar consumo diario previo para establecer promedio (ej: 50 kg/día)
        FeedingLog::create([
            'finca_id' => 1,
            'pond_id' => $this->lago1->id,
            'feed_inventory_id' => $alimento->id,
            'feeding_date' => now()->subDay()->toDateString(),
            'amount_kg' => 500.0,
            'appetite_level' => 'bueno',
        ]);

        // Días restantes = 1000 / (500 / 14 días ~ 35.71 kg/día)
        $this->assertGreaterThan(0, $alimento->daysOfFeedRemaining());
        $this->assertFalse($alimento->isLowStock());

        // 2. Registro de alimentación con nivel de apetito ('bueno', 'regular', 'malo')
        $feedResponse = $this->actingAs($this->trabajadorDestajo)->postJson('/api/warehouse/feeding-logs', [
            'pond_id' => $this->lago1->id,
            'feed_inventory_id' => $alimento->id,
            'amount_kg' => 800.0,
            'appetite_level' => 'bueno',
            'observations' => 'Consumo vigoroso en horas de la mañana',
        ]);

        $feedResponse->assertStatus(201);
        $alimento->refresh();

        // 1000 kg - 800 kg = 200 kg. Al ser menor a min_stock_alert_kg (300 kg), activa la alerta
        $this->assertEquals(200.0, (float) $alimento->quantity_kg);
        $this->assertTrue($alimento->isLowStock());

        // 3. Movimiento de entrada en bodega para reabastecer
        $movResponse = $this->actingAs($this->administrador)->postJson('/api/warehouse/movements', [
            'feed_inventory_id' => $alimento->id,
            'movement_type' => WarehouseMovement::TYPE_ENTRADA,
            'quantity_kg' => 2000.0,
            'bags_count' => 50.0,
            'unit_cost' => 95000.0,
            'reference' => 'Factura ITALCOL #4892',
        ]);

        $movResponse->assertStatus(201);
        $alimento->refresh();
        $this->assertEquals(2200.0, (float) $alimento->quantity_kg);
        $this->assertFalse($alimento->isLowStock());

        // 4. Asignación de turnos rotativos para 4 trabajadores
        $shiftResponse = $this->actingAs($this->administrador)->postJson('/api/warehouse/rotative-schedules', [
            'user_id' => $this->trabajadorDestajo->id,
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'week_end_date' => now()->endOfWeek()->toDateString(),
            'shift_type' => RotativeSchedule::SHIFT_GUARDIA_NOCTURNA,
            'notes' => 'Guardia nocturna y monitoreo de aireadores',
        ]);

        $shiftResponse->assertStatus(201);
        $this->assertDatabaseHas('rotative_schedules', [
            'user_id' => $this->trabajadorDestajo->id,
            'shift_type' => RotativeSchedule::SHIFT_GUARDIA_NOCTURNA,
        ]);
    }

    // =========================================================================
    // 5. PESCA, PESAJE CON TARA Y DESPACHO
    // =========================================================================

    public function test_harvest_agenda_gross_weight_tare_deduction_and_dispatch(): void
    {
        // 1. Jefe Mayor programa fecha de cosecha y proyecta kilos
        $order = HarvestOrder::create([
            'finca_id' => 1,
            'pond_id' => $this->lago1->id,
            'scheduled_by_user_id' => $this->jefeMayor->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'estimated_kg' => 3500.0,
            'status' => HarvestOrder::STATUS_PROGRAMADA,
        ]);

        // 2. Administrador registra pesaje bruto en báscula con tara de canastillas
        // Peso Bruto: 3600 kg, 100 Canastillas, Tara 2.0 kg/canastilla
        // Peso Neto = 3600 - (100 * 2.0) = 3400 kg
        $weighResponse = $this->actingAs($this->administrador)->postJson("/api/harvest-orders/{$order->id}/gross-weight", [
            'gross_weight_kg' => 3600.0,
            'baskets_count' => 100,
            'basket_tare_kg' => 2.0,
            'observations' => 'Pesaje en orilla de lago',
        ]);

        $weighResponse->assertOk();
        $this->assertEquals(3400.0, (float) $weighResponse->json('peso_neto_kg'));

        // 3. Despacho de pescado limpio con conductor, comprador y destino
        $dispatchResponse = $this->actingAs($this->administrador)->postJson("/api/harvest-orders/{$order->id}/dispatch", [
            'clean_weight_kg' => 3060.0,
            'baskets_count' => 85,
            'driver_name' => 'Alfonso Ramirez',
            'driver_id_card' => '79456123',
            'driver_vehicle_plate' => 'TOL982',
            'destination' => 'Corabastos - Bogota',
            'buyer_name' => 'Pescadería El Carmen',
        ]);

        $dispatchResponse->assertOk();
        $this->assertEquals('Pescadería El Carmen', $dispatchResponse->json('resumen_despacho.comprador'));
        $this->assertEquals(3400.0, (float) $dispatchResponse->json('resumen_despacho.peso_neto_kg'));
        $this->assertEquals(540.0, (float) $dispatchResponse->json('resumen_despacho.merma_kg')); // 3600 - 3060 = 540 kg
    }

    // =========================================================================
    // 6. VENTAS DIRECTAS EN EFECTIVO A VISITANTES ($9.000 / KG)
    // =========================================================================

    public function test_visitor_direct_cash_sales_with_automatic_kilos_conversion(): void
    {
        // Se reciben $45.000 en efectivo de un visitante en portería
        // Kilos = 45000 / 9000 = 5.0 kg
        $saleResponse = $this->actingAs($this->administrador)->postJson('/api/fish-sales/visitor-cash', [
            'cash_amount' => 45000.0,
            'customer_name' => 'Familia Martinez (Visitantes)',
        ]);

        $saleResponse->assertStatus(201);
        $this->assertEquals(5.0, (float) $saleResponse->json('kilos_vendidos'));
        $this->assertEquals(45000.0, (float) $saleResponse->json('dinero_recaudado'));

        // Venta por $180.000 -> 180000 / 9000 = 20.0 kg
        $sale2 = $this->actingAs($this->administrador)->postJson('/api/fish-sales', [
            'customer_type' => FishSale::TYPE_VISITOR,
            'cash_received' => 180000.0,
            'payment_method' => 'efectivo',
        ]);

        $sale2->assertStatus(201);
        $this->assertEquals(20.0, (float) $sale2->json('data.kilos_sold'));
    }

    // =========================================================================
    // 7. COMUNICACIÓN Y NOVEDADES (TAREAS, PERMISOS, HORAS EXTRAS)
    // =========================================================================

    public function test_communication_admin_tasks_leave_requests_and_overtime(): void
    {
        // 1. Administrador asigna tarea para su ausencia
        $taskResponse = $this->actingAs($this->administrador)->postJson('/api/communication/tasks', [
            'title' => 'Monitorear niveles de oxígeno en estanque 1 a las 3:00 AM',
            'assigned_to_user_id' => $this->trabajadorDestajo->id,
            'priority' => AdminTask::PRIORITY_ALTA,
            'due_date' => now()->toDateString(),
        ]);

        $taskResponse->assertStatus(201);
        $taskId = $taskResponse->json('data.id');

        // Trabajador completa la tarea
        $completeTaskResponse = $this->actingAs($this->trabajadorDestajo)
            ->postJson("/api/communication/tasks/{$taskId}/complete", [
                'notes' => 'Oxígeno verificado en 6.2 mg/L, aireadores funcionando correctamente',
            ]);

        $completeTaskResponse->assertOk();
        $this->assertEquals(AdminTask::STATUS_COMPLETADA, $completeTaskResponse->json('data.status'));

        // 2. Trabajador solicita permiso laboral
        $leaveResponse = $this->actingAs($this->trabajadorDestajo)->postJson('/api/communication/leave-requests', [
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reason' => 'Cita médica especialista en Neiva',
        ]);

        $leaveResponse->assertStatus(201);
        $leaveId = $leaveResponse->json('data.id');

        // Jefe Mayor aprueba el permiso
        $reviewLeave = $this->actingAs($this->jefeMayor)
            ->postJson("/api/communication/leave-requests/{$leaveId}/review", [
                'decision' => 'aprobar',
                'response_notes' => 'Aprobado, favor coordinar relevo con Carlos.',
            ]);

        $reviewLeave->assertOk();
        $this->assertEquals(LeaveRequest::STATUS_APROBADO, $reviewLeave->json('data.status'));

        // 3. Registro de horas extras con justificación de la ocasión
        $overtimeResponse = $this->actingAs($this->trabajadorDestajo)->postJson('/api/communication/overtime-records', [
            'hours' => 3.5,
            'occasion' => 'Reparación de emergencia motobomba diésel de recambio',
            'justification' => 'Fallo en la válvula de succión requirió purga y cambio de empaque para evitar desabastecimiento.',
        ]);

        $overtimeResponse->assertStatus(201);
        $this->assertDatabaseHas('overtime_records', [
            'user_id' => $this->trabajadorDestajo->id,
            'hours' => 3.5,
            'occasion' => 'Reparación de emergencia motobomba diésel de recambio',
        ]);
    }
}
