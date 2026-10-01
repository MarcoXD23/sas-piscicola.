<?php

namespace Tests\Feature;

use App\Models\AgendaTurno;
use App\Models\Especie;
use App\Models\FeedingLog;
use App\Models\Finca;
use App\Models\Pond;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoPiscicolaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptMaestroModulesTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'El SAS Piscícola',
            'codigo' => 'SAS-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);
    }

    /**
     * Módulo 1: DatabaseSeeder limpio para producción vs DemoPiscicolaSeeder.
     */
    public function test_database_seeder_and_demo_piscicola_seeder_work_cleanly(): void
    {
        // 1. Ejecutar Seeder Limpio de Producción
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'propietario@finca.com',
            'role' => User::ROLE_PROPIETARIO,
        ]);

        $this->assertGreaterThan(0, Especie::count());

        // 2. Ejecutar Seeder de Demostración
        $this->seed(DemoPiscicolaSeeder::class);

        $this->assertEquals(8, Pond::count());
        $this->assertEquals(81050, Pond::sum('fish_population'));
        $this->assertGreaterThanOrEqual(6, AgendaTurno::count());

        $this->assertDatabaseHas('users', [
            'email' => 'admin@finca.com',
            'role' => User::ROLE_TECNICO_ACUICOLA,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'trabajador@finca.com',
            'role' => User::ROLE_OPERARIO_CAMPO,
        ]);
    }

    /**
     * Módulo 2: Registro de Nuevos Lagos y Siembras con cálculo automático de biomasa.
     */
    public function test_can_register_new_pond_and_stocking_with_auto_biomass(): void
    {
        $especie = Especie::create([
            'finca_id' => 1,
            'nombre_comun' => 'Mojarra Roja',
            'nombre_cientifico' => 'Oreochromis sp.',
            'tipo_ambiente' => 'calido',
        ]);

        $jefe = User::factory()->create([
            'role' => User::ROLE_JEFE_MAYOR,
            'finca_id' => 1,
        ]);

        $response = $this->actingAs($jefe)->post('/admin/lagos', [
            'name' => 'Estanque 9 - Pre-Engorde',
            'tipo_estanque' => 'Geomembrana',
            'especie_id' => $especie->id,
            'stocking_date' => now()->subDays(30)->toDateString(),
            'fingerlings_stocked' => 10000,
            'average_weight' => 25.5, // 25.5 gramos
            'notes' => 'Siembra de prueba de alevinos de alta calidad',
        ]);

        $response->assertRedirect('/admin/lagos');
        $response->assertSessionHas('status');

        // Biomasa esperada = 10000 * 25.5 / 1000 = 255.0 kg
        $this->assertDatabaseHas('ponds', [
            'name' => 'Estanque 9 - Pre-Engorde',
            'tipo_estanque' => 'Geomembrana',
            'especie_id' => $especie->id,
            'fingerlings_stocked' => 10000,
            'fish_population' => 10000,
            'average_weight' => 25.5,
            'biomass' => 255.0,
        ]);
    }

    public function test_lagos_view_renders_new_pond_button_and_modal(): void
    {
        $jefe = User::factory()->create(['role' => User::ROLE_JEFE_MAYOR, 'finca_id' => 1]);

        $response = $this->actingAs($jefe)->get('/admin/lagos');

        $response->assertOk()
            ->assertSee('+ Registrar Nuevo Lago')
            ->assertSee('modal-nuevo-lago')
            ->assertSee("document.getElementById('modal-nuevo-lago').classList.remove('hidden')", false);
    }

    /**
     * Módulo 3 & 6: Estructura Definitiva de Roles (Ventas, Báscula y Nómina exclusivos de Jefe Mayor).
     */
    public function test_sales_harvests_and_payroll_exclusive_to_jefe_mayor(): void
    {
        $jefe = User::factory()->create(['role' => User::ROLE_JEFE_MAYOR, 'finca_id' => 1]);
        $tecnico = User::factory()->create(['role' => User::ROLE_TECNICO_ACUICOLA, 'finca_id' => 1]);
        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO, 'finca_id' => 1]);

        // Jefe Mayor puede entrar a los 3 módulos
        $this->actingAs($jefe)->get(route('cosechas.index'))->assertOk();
        $this->actingAs($jefe)->get(route('ventas.index'))->assertOk();
        $this->actingAs($jefe)->get(route('nomina.index'))->assertOk();

        // Técnico Acuícola es bloqueado
        $this->actingAs($tecnico)->get(route('cosechas.index'))->assertStatus(403);
        $this->actingAs($tecnico)->get(route('ventas.index'))->assertStatus(403);
        $this->actingAs($tecnico)->get(route('nomina.index'))->assertStatus(403);

        // Operario de Campo es bloqueado
        $this->actingAs($operario)->get(route('cosechas.index'))->assertStatus(403);
        $this->actingAs($operario)->get(route('ventas.index'))->assertStatus(403);
        $this->actingAs($operario)->get(route('nomina.index'))->assertStatus(403);
    }

    /**
     * Módulo 4: Calendario de Turnos Rotativos con encadenamiento automático de domingo nocturno.
     */
    public function test_programar_turno_semanal_chains_previous_sunday_night_guard(): void
    {
        $jefe = User::factory()->create(['role' => User::ROLE_JEFE_MAYOR, 'finca_id' => 1]);
        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO, 'finca_id' => 1]);

        $proximoLunes = Carbon::now()->addWeek()->startOfWeek();
        $domingoAnterior = $proximoLunes->copy()->subDay();

        $response = $this->actingAs($jefe)->postJson('/agenda/turnos/semanal', [
            'user_id' => $operario->id,
            'fecha_lunes' => $proximoLunes->toDateString(),
            'observaciones' => 'Turno semanal rotativo',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'turnos_creados' => 6, // 1 domingo previo + 5 días (lun-vie)
        ]);

        // 1. Debe existir turno de Celador Nocturno para el domingo anterior
        $this->assertTrue(
            AgendaTurno::where('user_id', $operario->id)
                ->whereDate('fecha', $domingoAnterior->toDateString())
                ->where('tipo_dia', AgendaTurno::TIPO_DOMINGO)
                ->where('rol_asignado', AgendaTurno::ROL_SEGURIDAD_NOCHE)
                ->whereIn('estado', [AgendaTurno::ESTADO_ACTIVO, AgendaTurno::ESTADO_PROGRAMADO])
                ->exists(),
            'No se encontró el turno de seguridad nocturna para el domingo inmediatamente anterior.'
        );

        // 2. Debe existir turno de Alimentador de Lunes a Viernes
        for ($i = 0; $i < 5; $i++) {
            $dia = $proximoLunes->copy()->addDays($i);
            $this->assertTrue(
                AgendaTurno::where('user_id', $operario->id)
                    ->whereDate('fecha', $dia->toDateString())
                    ->where('tipo_dia', AgendaTurno::TIPO_SEMANA)
                    ->where('rol_asignado', AgendaTurno::ROL_ALIMENTADOR)
                    ->whereIn('estado', [AgendaTurno::ESTADO_ACTIVO, AgendaTurno::ESTADO_PROGRAMADO])
                    ->exists(),
                "No se encontró el turno de alimentador para el día {$dia->toDateString()}."
            );
        }
    }

    /**
     * Módulo 5: Middleware VerificarTurnoDiarioAlimentador bloquea a operarios sin turno y permite a asignados.
     */
    public function test_middleware_verificar_turno_diario_alimentador(): void
    {
        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO, 'finca_id' => 1]);
        $estanque = Pond::factory()->create(['finca_id' => 1]);

        // 1. Sin turno asignado -> Bloqueado con 403
        $responseSinTurno = $this->actingAs($operario)->postJson('/trabajador/alimentacion', [
            'pond_id' => $estanque->id,
            'amount_kg' => 20.0,
            'appetite_level' => FeedingLog::APPETITE_BUENO,
        ]);

        $responseSinTurno->assertStatus(403);
        $responseSinTurno->assertJson(['error' => 'SIN_TURNO_ALIMENTACION']);

        // 2. Con turno asignado para hoy -> Acceso concedido (201)
        AgendaTurno::create([
            'finca_id' => 1,
            'user_id' => $operario->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => now()->isWeekend() ? 'sabado' : 'semana',
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        $responseConTurno = $this->actingAs($operario)->postJson('/trabajador/alimentacion', [
            'pond_id' => $estanque->id,
            'amount_kg' => 20.0,
            'appetite_level' => FeedingLog::APPETITE_BUENO,
        ]);

        $responseConTurno->assertStatus(201);
    }

    /**
     * Módulo 5: Middleware VerificarTurnoSeguridadNoche protege el módulo de seguridad nocturna.
     */
    public function test_middleware_verificar_turno_seguridad_noche(): void
    {
        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO, 'finca_id' => 1]);

        // Sin turno de guardia nocturna hoy -> Bloqueado con 403
        $responseSinTurno = $this->actingAs($operario)->getJson('/celador');
        $responseSinTurno->assertStatus(403);
        $responseSinTurno->assertJson(['error' => 'SIN_TURNO_SEGURIDAD']);

        // Asignar turno de seguridad nocturna para hoy
        AgendaTurno::create([
            'finca_id' => 1,
            'user_id' => $operario->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => now()->isSunday() ? 'domingo' : 'semana',
            'rol_asignado' => AgendaTurno::ROL_SEGURIDAD_NOCHE,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        // Con turno de seguridad nocturna activo -> Acceso concedido
        $responseConTurno = $this->actingAs($operario)->get('/celador');
        $responseConTurno->assertOk();
    }

    /**
     * Badge dinámico del rol y turno según la fecha.
     */
    public function test_user_badge_rol_hoy_returns_accurate_state(): void
    {
        $jefe = User::factory()->create(['role' => User::ROLE_JEFE_MAYOR]);
        $badgeJefe = $jefe->badgeRolHoy();
        $this->assertEquals('Propietario / Gerente General', $badgeJefe['label']);

        $tecnico = User::factory()->create(['role' => User::ROLE_TECNICO_ACUICOLA]);
        $badgeTecnico = $tecnico->badgeRolHoy();
        $this->assertEquals('Técnico Acuícola', $badgeTecnico['label']);

        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO]);
        $badgeSinTurno = $operario->badgeRolHoy();
        $this->assertEquals('Operario de Campo', $badgeSinTurno['label']);
        $this->assertEquals('Sin Turno Programado Hoy', $badgeSinTurno['sublabel']);

        // Asignar turno de alimentador para hoy
        AgendaTurno::create([
            'finca_id' => 1,
            'user_id' => $operario->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => 'semana',
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        $badgeAlimentador = $operario->badgeRolHoy();
        $this->assertEquals('Turno Hoy: Alimentador', $badgeAlimentador['label']);
    }
}
