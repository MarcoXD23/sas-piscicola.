<?php

namespace Tests\Feature;

use App\Models\AdminTask;
use App\Models\ControlAireador;
use App\Models\Estanque;
use App\Models\FeedingLog;
use App\Models\Finca;
use App\Models\FishCredit;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrabajadorAndCeladorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $trabajador;

    private User $celador;

    private Finca $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'Piscícola El Remanso',
            'codigo' => 'REM-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->trabajador = User::factory()->create([
            'name' => 'Jaime Operario',
            'email' => 'operario@finca.com',
            'role' => User::ROLE_TRABAJADOR,
            'employment_type' => User::TYPE_DESTAJO_SEMANAL,
            'finca_id' => 1,
        ]);

        $this->celador = User::factory()->create([
            'name' => 'Don Faustino Guardia',
            'email' => 'faustino@finca.com',
            'role' => User::ROLE_CELADOR_NOCTURNO,
            'employment_type' => User::TYPE_FIJO,
            'finca_id' => 1,
        ]);
    }

    /**
     * Verifica que el dashboard del trabajador consulte y liste DINÁMICAMENTE todos los estanques creados.
     */
    public function test_trabajador_dashboard_lists_all_farm_ponds_dynamically_with_species_and_rations(): void
    {
        // Crear 10 estanques dinámicos en la finca
        for ($i = 1; $i <= 10; $i++) {
            Pond::create([
                'finca_id' => 1,
                'name' => "Estanque Granja {$i}",
                'code' => "PND-{$i}",
                'fingerlings_stocked' => 5000,
                'fish_population' => 4900,
                'average_weight' => 250.0,
                'biomass' => 1225.0,
                'status' => 'Sembrado',
                'stocked_at' => now()->subDays(30),
            ]);
        }

        $response = $this->actingAs($this->trabajador)->get('/trabajador/dashboard');

        $response->assertOk();
        $response->assertSee('Mi Portal Operativo de Granja');
        $response->assertSee('Estanques en Monitoreo');

        // Todos los 10 estanques deben aparecer en la respuesta
        for ($i = 1; $i <= 10; $i++) {
            $response->assertSee("Estanque Granja {$i}");
            $response->assertSee("PND-{$i}");
        }

        // Ración sugerida visible (1225 kg * 0.025 = 30.6 kg)
        $response->assertSee('30.6 kg');
        $response->assertSee('Registrar Alimentación');
        $response->assertSee('Reportar Bajas');
    }

    /**
     * Verifica que el reporte de bajas matutinas descuente la población real y recalcule la biomasa.
     */
    public function test_trabajador_can_report_morning_mortality_and_updates_pond_population_and_biomass(): void
    {
        $estanque = Estanque::create([
            'finca_id' => 1,
            'name' => 'Estanque Bajas Test',
            'code' => 'EST-MORT',
            'fingerlings_stocked' => 10000,
            'fish_population' => 10000,
            'average_weight' => 300.0,
            'biomass' => 3000.0,
        ]);

        $response = $this->actingAs($this->trabajador)->postJson('/trabajador/mortalidad', [
            'estanque_id' => $estanque->id,
            'cantidad_peces' => 45,
            'causa_probable' => 'Falta de Oxígeno / Boqueo',
            'metodo_disposicion' => 'compostaje',
            'observaciones' => 'Peces recogidos a las 6:30 AM en la orilla norte',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'poblacion_actual' => 9955,
            'biomasa_actual_kg' => 2986.5, // 9955 * 300 / 1000
        ]);

        $this->assertDatabaseHas('registros_mortalidad', [
            'estanque_id' => $estanque->id,
            'user_id' => $this->trabajador->id,
            'cantidad_peces' => 45,
            'causa_probable' => 'Falta de Oxígeno / Boqueo',
        ]);

        $estanque->refresh();
        $this->assertEquals(9955, $estanque->fish_population);
        $this->assertEquals(2986.5, (float) $estanque->biomass);
    }

    /**
     * Verifica que el registro rápido de alimentación guarde el apetito y la ración suministrada.
     */
    public function test_trabajador_can_record_feeding_with_appetite_level(): void
    {
        $estanque = Estanque::create([
            'finca_id' => 1,
            'name' => 'Estanque Alimentar Test',
            'code' => 'EST-ALIM',
            'fish_population' => 8000,
            'average_weight' => 200.0,
            'biomass' => 1600.0,
        ]);

        // Programar el turno de alimentador para hoy en la Agenda Operativa
        \App\Models\AgendaTurno::create([
            'finca_id' => 1,
            'user_id' => $this->trabajador->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => now()->isWeekend() ? (now()->isSaturday() ? 'sabado' : 'domingo') : 'semana',
            'rol_asignado' => \App\Models\AgendaTurno::ROL_ALIMENTADOR,
            'estado' => \App\Models\AgendaTurno::ESTADO_ACTIVO,
        ]);

        $response = $this->actingAs($this->trabajador)->postJson('/trabajador/alimentacion', [
            'pond_id' => $estanque->id,
            'amount_kg' => 40.0,
            'appetite_level' => FeedingLog::APPETITE_BUENO,
            'observations' => 'Comieron con voracidad en 15 minutos',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('feeding_logs', [
            'pond_id' => $estanque->id,
            'user_id' => $this->trabajador->id,
            'amount_kg' => 40.0,
            'appetite_level' => 'bueno',
        ]);
    }

    /**
     * Verifica que el dashboard del celador liste dinámicamente TODOS los estanques en modo noche (#0B1120).
     */
    public function test_celador_dashboard_lists_all_farm_ponds_dynamically_in_night_mode(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            Pond::create([
                'finca_id' => 1,
                'name' => "Estanque Nocturno {$i}",
                'code' => "NOC-0{$i}",
                'fish_population' => 5000,
                'average_weight' => 300.0,
                'biomass' => 1500.0,
            ]);
        }

        $response = $this->actingAs($this->celador)->get('/celador/dashboard');

        $response->assertOk();
        $response->assertSee('#0B1120');
        $response->assertSee('ALERTA CRÍTICA: BOQUEO / FALTA DE OXÍGENO');
        $response->assertSee('Control de Aireadores por Estanque');

        for ($i = 1; $i <= 6; $i++) {
            $response->assertSee("Estanque Nocturno {$i}");
            $response->assertSee("NOC-0{$i}");
        }
    }

    /**
     * Verifica que cuando un aireador opera con planta diésel se contabilice el tiempo y consumo estimado.
     */
    public function test_celador_diesel_plant_aerator_tracks_operation_time_and_fuel_consumption(): void
    {
        $estanque = Pond::create([
            'finca_id' => 1,
            'name' => 'Estanque Planta Diésel',
            'code' => 'PND-DSL',
        ]);

        // Crear registro de aireador con planta de emergencia encendido hace 90 minutos
        $aireador = ControlAireador::create([
            'finca_id' => 1,
            'estanque_id' => $estanque->id,
            'user_id' => $this->celador->id,
            'fecha' => now()->toDateString(),
            'hora_encendido' => now()->subMinutes(90),
            'fuente_energia' => ControlAireador::FUENTE_PLANTA_EMERGENCIA,
            'corte_luz' => true,
        ]);

        $this->assertGreaterThanOrEqual(89, $aireador->minutos_activo);
        $this->assertStringContainsString('1h', $aireador->tiempo_operacion_texto);
        $this->assertGreaterThan(0.5, $aireador->consumo_diesel_estimado_galones);

        $response = $this->actingAs($this->celador)->get('/celador/dashboard');
        $response->assertOk();
        $response->assertSee('Planta Diésel');
        $response->assertSee('gal diésel');
    }

    /**
     * Verifica la vista y registro de bajas matutinas del trabajador.
     */
    public function test_trabajador_can_view_mortalidad_page_and_submit_form(): void
    {
        $estanque = Estanque::create([
            'finca_id' => 1,
            'name' => 'Lago Sanidad Test',
            'code' => 'EST-SAN',
            'fingerlings_stocked' => 5000,
            'fish_population' => 5000,
            'average_weight' => 200.0,
            'biomass' => 1000.0,
        ]);

        $response = $this->actingAs($this->trabajador)->get(route('trabajador.mortalidad.index'));
        $response->assertOk()
            ->assertSee('Reporte de Bajas Matutinas')
            ->assertSee('Lago Sanidad Test')
            ->assertSee('Volver al Dashboard');

        $submitResponse = $this->actingAs($this->trabajador)->post(route('trabajador.mortalidad'), [
            'estanque_id' => $estanque->id,
            'cantidad_peces' => 20,
            'causa' => 'Asfixia matutina',
            'metodo_disposicion' => 'compostaje',
        ]);

        $submitResponse->assertRedirect();
        $this->assertDatabaseHas('registros_mortalidad', [
            'estanque_id' => $estanque->id,
            'cantidad_peces' => 20,
            'causa_probable' => 'Asfixia matutina',
        ]);

        $estanque->refresh();
        $this->assertEquals(4980, $estanque->fish_population);
    }

    /**
     * Verifica la vista de tareas asignadas y completar labor.
     */
    public function test_trabajador_can_view_tareas_page_and_complete_task(): void
    {
        $task = AdminTask::create([
            'finca_id' => 1,
            'title' => 'Limpieza de monjes lago 1',
            'description' => 'Retirar sedimentos y revisar mallas',
            'assigned_to_user_id' => $this->trabajador->id,
            'created_by_user_id' => $this->celador->id,
            'status' => AdminTask::STATUS_PENDIENTE,
            'priority' => AdminTask::PRIORITY_ALTA,
            'due_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->trabajador)->get(route('trabajador.tareas.index'));
        $response->assertOk()
            ->assertSee('Limpieza de monjes lago 1')
            ->assertSee('Marcar como Realizada')
            ->assertSee('Volver al Dashboard');

        $completeResponse = $this->actingAs($this->trabajador)->post(route('trabajador.tareas.completar', $task->id));
        $completeResponse->assertRedirect();

        $task->refresh();
        $this->assertEquals(AdminTask::STATUS_COMPLETADA, $task->status);
    }

    /**
     * Verifica la vista y registro de horas extras del trabajador.
     */
    public function test_trabajador_can_view_horas_extras_and_store_overtime(): void
    {
        $response = $this->actingAs($this->trabajador)->get(route('trabajador.horas-extras.index'));
        $response->assertOk()
            ->assertSee('Registro y Control de Horas Extras')
            ->assertSee('Volver al Dashboard');

        $submitResponse = $this->actingAs($this->trabajador)->post(route('trabajador.horas-extras.store'), [
            'fecha' => now()->toDateString(),
            'horas' => 3.5,
            'motivo' => 'Pesca nocturna urgente',
            'justificacion' => 'Apoyo en báscula por cosecha mayorista',
        ]);

        $submitResponse->assertRedirect();
        $this->assertDatabaseHas('overtime_records', [
            'user_id' => $this->trabajador->id,
            'hours' => 3.5,
            'occasion' => 'Pesca nocturna urgente',
            'status' => 'pendiente',
        ]);
    }

    /**
     * Verifica la vista y registro de solicitudes de permisos.
     */
    public function test_trabajador_can_view_permisos_and_store_request(): void
    {
        $response = $this->actingAs($this->trabajador)->get(route('trabajador.permisos.index'));
        $response->assertOk()
            ->assertSee('Solicitud de Permisos Laborales')
            ->assertSee('Volver al Dashboard');

        $submitResponse = $this->actingAs($this->trabajador)->post(route('trabajador.permisos.store'), [
            'fecha_inicio' => now()->addDays(3)->toDateString(),
            'fecha_fin' => now()->addDays(4)->toDateString(),
            'motivo' => 'Cita odontológica en cabecera municipal',
        ]);

        $submitResponse->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $this->trabajador->id,
            'reason' => 'Cita odontológica en cabecera municipal',
            'status' => 'pendiente',
        ]);
    }

    /**
     * Verifica la vista de saldo de pescado y cálculo de deuda descontable.
     */
    public function test_trabajador_can_view_saldo_pescado_with_calculations(): void
    {
        FishCredit::create([
            'finca_id' => 1,
            'user_id' => $this->trabajador->id,
            'credit_date' => now()->subDays(2),
            'kilos' => 10.0,
            'price_per_kg' => 7000.0,
            'total_amount' => 70000.0,
            'status' => 'pendiente',
            'registered_by_user_id' => $this->celador->id,
        ]);

        $response = $this->actingAs($this->trabajador)->get(route('trabajador.saldo-pescado.index'));
        $response->assertOk()
            ->assertSee('Saldo de Pescado Llevado / Fiado')
            ->assertSee('10.00 kg')
            ->assertSee('70.000 COP')
            ->assertSee('Este valor será descontado en su nómina del sábado')
            ->assertSee('Volver al Dashboard');
    }
}
