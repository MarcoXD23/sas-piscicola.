<?php

namespace Tests\Feature;

use App\Models\DailyLabor;
use App\Models\FishSale;
use App\Models\Pond;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registers_support_staff_daily_labor(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1]);

        $response = $this->actingAs($admin)->postJson(route('api.daily_labors.store'), [
            'worker_name' => 'Albeiro Morales (Apoyo)',
            'worker_id_card' => '79888999',
            'pond_id' => $pond->id,
            'work_date' => '2026-10-06',
            'labor_type' => 'lavado_estanques',
            'daily_wage' => 60000.0,
            'hours_worked' => 8.0,
            'observations' => 'Limpieza y desinfección de paredes del estanque',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Jornal de apoyo registrado exitosamente en la libreta.',
                'data' => [
                    'worker_name' => 'Albeiro Morales (Apoyo)',
                    'labor_type' => 'lavado_estanques',
                    'daily_wage' => 60000.0,
                    'payment_status' => 'pendiente',
                ],
            ]);

        $this->assertDatabaseHas('daily_labors', [
            'finca_id' => 1,
            'worker_name' => 'Albeiro Morales (Apoyo)',
            'labor_type' => 'lavado_estanques',
            'daily_wage' => 60000.0,
            'payment_status' => 'pendiente',
        ]);
    }

    public function test_calculates_and_settles_weekly_payroll_on_saturday(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        // Sábado de corte: 10 de Octubre de 2026
        $saturday = Carbon::parse('2026-10-10');

        // Trabajador 1: 3 jornales en la semana (Lavado, Pesca, Empaque) = $180.000
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Jairo Apoyo',
            'work_date' => '2026-10-06', // Martes
            'labor_type' => 'lavado_estanques',
            'daily_wage' => 60000.0,
            'payment_status' => 'pendiente',
            'registered_by_user_id' => $admin->id,
        ]);
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Jairo Apoyo',
            'work_date' => '2026-10-08', // Jueves
            'labor_type' => 'pesca',
            'daily_wage' => 60000.0,
            'payment_status' => 'pendiente',
            'registered_by_user_id' => $admin->id,
        ]);
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Jairo Apoyo',
            'work_date' => '2026-10-09', // Viernes
            'labor_type' => 'empaque',
            'daily_wage' => 60000.0,
            'payment_status' => 'pendiente',
            'registered_by_user_id' => $admin->id,
        ]);

        // Trabajador 2: 2 jornales = $120.000
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Camilo Apoyo',
            'work_date' => '2026-10-08',
            'labor_type' => 'pesca',
            'daily_wage' => 60000.0,
            'payment_status' => 'pendiente',
            'registered_by_user_id' => $admin->id,
        ]);
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Camilo Apoyo',
            'work_date' => '2026-10-09',
            'labor_type' => 'empaque',
            'daily_wage' => 60000.0,
            'payment_status' => 'pendiente',
            'registered_by_user_id' => $admin->id,
        ]);

        // 1. Probar cálculo previo de nómina semanal con corte de sábado
        $responseCalc = $this->actingAs($admin)->getJson(route('api.weekly_payroll.calculate', [
            'cutoff_date' => '2026-10-10',
        ]));

        $responseCalc->assertStatus(200)
            ->assertJson([
                'periodo_nomina' => [
                    'fecha_corte_sabado' => '2026-10-10',
                    'es_dia_sabado' => true,
                ],
                'totales_nomina' => [
                    'total_trabajadores' => 2,
                    'total_jornales_acumulados' => 5,
                    'total_a_pagar' => 300000.0,
                ],
            ]);

        // 2. Liquidar nómina semanal del sábado
        $responseSettle = $this->actingAs($admin)->postJson(route('api.weekly_payroll.settle'), [
            'cutoff_date' => '2026-10-10',
            'notes' => 'Liquidación semanal de apoyo para cosecha y lavado estanque 1',
        ]);

        $responseSettle->assertStatus(201)
            ->assertJson([
                'message' => 'Nómina semanal liquidada y cerrada exitosamente para el día sábado.',
                'data' => [
                    'total_jornales_count' => 5,
                    'total_workers_count' => 2,
                    'total_amount' => 300000.0,
                    'status' => 'liquidada',
                ],
            ]);

        // Todos los jornales deben estar ahora como 'liquidado'
        $this->assertEquals(0, DailyLabor::where('payment_status', 'pendiente')->count());
        $this->assertEquals(5, DailyLabor::where('payment_status', 'liquidado')->count());
    }

    public function test_harvest_roles_and_automatic_exclusion_of_fixed_workers(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1]);

        $saturday = Carbon::parse('2026-10-17');

        // 1. Empleado Fijo (participa como rayador en cosecha, pero percibe sueldo fijo mensual)
        $fixedWorker = User::factory()->create([
            'name' => 'Carlos Fijo',
            'finca_id' => 1,
            'role' => User::ROLE_WORKER,
            'employment_type' => User::TYPE_FIJO,
        ]);

        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => $fixedWorker->name,
            'user_id' => $fixedWorker->id,
            'employment_type' => DailyLabor::TYPE_FIJO,
            'pond_id' => $pond->id,
            'work_date' => '2026-10-14',
            'labor_type' => DailyLabor::LABOR_RAYADORES,
            'daily_wage' => 0.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
            'observations' => 'Labor de rayado de cosecha por personal de planta',
        ]);

        // 2. Personal Temporal / Jornaleros (pesca y lavado)
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Marcos Jornalero',
            'employment_type' => DailyLabor::TYPE_TEMPORAL,
            'pond_id' => $pond->id,
            'work_date' => '2026-10-14',
            'labor_type' => DailyLabor::LABOR_PESCA,
            'daily_wage' => 70000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
        ]);

        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Marcos Jornalero',
            'employment_type' => DailyLabor::TYPE_TEMPORAL,
            'pond_id' => $pond->id,
            'work_date' => '2026-10-15',
            'labor_type' => DailyLabor::LABOR_LAVADO,
            'daily_wage' => 60000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
        ]);

        // Calcular nómina semanal
        $response = $this->actingAs($admin)->getJson(route('api.weekly_payroll.calculate', [
            'cutoff_date' => '2026-10-17',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'totales_nomina' => [
                    'total_trabajadores_temporales' => 1,
                    'total_trabajadores_fijos_excluidos' => 1,
                    'total_jornales_temporales' => 2,
                    'total_jornales_fijos' => 1,
                    'total_bruto_jornales' => 130000.0,
                    'total_neto_a_pagar' => 130000.0,
                ],
                'trabajadores_fijos_excluidos' => [
                    [
                        'worker_name' => 'Carlos Fijo',
                        'tipo_empleado' => 'fijo',
                        'excluido_de_liquidacion' => true,
                        'paga_acumulada_sabado' => 0.0,
                    ],
                ],
                'nomina_personal_temporal' => [
                    [
                        'worker_name' => 'Marcos Jornalero',
                        'tipo_empleado' => 'temporal',
                        'dias_trabajados_count' => 2,
                        'acumulado_jornales' => 130000.0,
                        'total_neto_a_pagar' => 130000.0,
                    ],
                ],
            ]);
    }

    public function test_saturday_liquidation_with_automatic_fish_credit_deductions_at_internal_rate(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $pond = Pond::factory()->create(['finca_id' => 1]);

        // Sábado de corte: 24 de Octubre de 2026
        // Temporal: 3 días a $60.000 = $180.000 bruto
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Pedro Jornalero',
            'employment_type' => DailyLabor::TYPE_TEMPORAL,
            'pond_id' => $pond->id,
            'work_date' => '2026-10-20',
            'labor_type' => DailyLabor::LABOR_PESCA,
            'daily_wage' => 60000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
        ]);
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Pedro Jornalero',
            'employment_type' => DailyLabor::TYPE_TEMPORAL,
            'pond_id' => $pond->id,
            'work_date' => '2026-10-21',
            'labor_type' => DailyLabor::LABOR_RAYADORES,
            'daily_wage' => 60000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
        ]);
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => 'Pedro Jornalero',
            'employment_type' => DailyLabor::TYPE_TEMPORAL,
            'pond_id' => $pond->id,
            'work_date' => '2026-10-22',
            'labor_type' => DailyLabor::LABOR_EMPAQUE,
            'daily_wage' => 60000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
        ]);

        // Durante la semana llevó 5 kilos de pescado fiado con cargo a nómina (5 kg * $7.000 = $35.000)
        FishSale::create([
            'finca_id' => 1,
            'sale_date' => '2026-10-21',
            'customer_type' => FishSale::TYPE_WORKER,
            'customer_name' => 'Pedro Jornalero',
            'kilos_sold' => 5.0,
            'price_per_kg' => 7000.0,
            'total_amount' => 35000.0,
            'payment_method' => 'descuento_nomina',
            'registered_by_user_id' => $admin->id,
        ]);

        // 1. Verificar cálculo previo
        $calcResponse = $this->actingAs($admin)->getJson(route('api.weekly_payroll.calculate', [
            'cutoff_date' => '2026-10-24',
        ]));

        $calcResponse->assertStatus(200)
            ->assertJson([
                'totales_nomina' => [
                    'total_bruto_jornales' => 180000.0,
                    'total_descuento_pescado_fiado' => 35000.0,
                    'total_neto_a_pagar' => 145000.0,
                ],
                'nomina_personal_temporal' => [
                    [
                        'worker_name' => 'Pedro Jornalero',
                        'acumulado_jornales' => 180000.0,
                        'kilos_pescado_fiado' => 5.0,
                        'tarifa_pescado_kilo' => 7000.0,
                        'descuento_pescado_fiado' => 35000.0,
                        'total_neto_a_pagar' => 145000.0,
                    ],
                ],
            ]);

        // 2. Liquidar el sábado
        $settleResponse = $this->actingAs($admin)->postJson(route('api.weekly_payroll.settle'), [
            'cutoff_date' => '2026-10-24',
            'notes' => 'Cierre con deducción por pescado fiado',
        ]);

        $settleResponse->assertStatus(201)
            ->assertJson([
                'message' => 'Nómina semanal liquidada y cerrada exitosamente para el día sábado.',
                'data' => [
                    'total_gross_amount' => 180000.0,
                    'total_deductions_amount' => 35000.0,
                    'total_net_amount' => 145000.0,
                    'status' => 'liquidada',
                ],
            ]);

        $this->assertDatabaseHas('payroll_settlements', [
            'finca_id' => 1,
            'cutoff_date' => '2026-10-24',
            'total_gross_amount' => 180000.0,
            'total_deductions_amount' => 35000.0,
            'total_net_amount' => 145000.0,
        ]);

        // La venta fiada se actualiza a descuento_nomina_liquidado
        $this->assertDatabaseHas('fish_sales', [
            'customer_name' => 'Pedro Jornalero',
            'payment_method' => 'descuento_nomina_liquidado',
        ]);
    }
}
