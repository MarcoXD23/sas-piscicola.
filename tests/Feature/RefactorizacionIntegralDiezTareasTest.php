<?php

namespace Tests\Feature;

use App\Models\ActividadTrabajador;
use App\Models\AgendaTurno;
use App\Models\Especie;
use App\Models\Pond;
use App\Models\Role;
use App\Models\TrasladoPeces;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoPiscicolaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefactorizacionIntegralDiezTareasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TAREA 1: Base de Datos Limpia y Configuración de Seeders.
     * DatabaseSeeder solo siembra roles del sistema, catálogo de especies y 1 usuario propietario.
     * Cero estanques y cero peces de prueba.
     */
    public function test_tarea_1_database_seeder_produccion_limpia(): void
    {
        $this->seed(DatabaseSeeder::class);

        // No debe haber estanques ficticios
        $this->assertEquals(0, Pond::count());

        // Debe haber roles del sistema
        $this->assertDatabaseHas('roles', ['slug' => 'propietario']);
        $this->assertDatabaseHas('roles', ['slug' => 'tecnico_acuicola']);
        $this->assertDatabaseHas('roles', ['slug' => 'operario_campo']);

        // Debe haber catálogo de especies
        $this->assertGreaterThan(0, Especie::count());

        // Debe existir el usuario propietario administrador inicial
        $admin = User::where('email', 'propietario@finca.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isPropietario());
    }

    /**
     * TAREA 1 (Demo): DemoPiscicolaSeeder siembra 8 estanques, 81.050 peces y turnos.
     */
    public function test_tarea_1_demo_piscicola_seeder(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoPiscicolaSeeder::class);

        $this->assertEquals(8, Pond::count());
        $this->assertEquals(81050, Pond::sum('fish_population'));
    }

    /**
     * TAREA 2: Módulo de Registro de Nuevos Lagos y Siembras.
     * Verifica creación con cálculo reactivo de días y biomasa inicial atómica.
     */
    public function test_tarea_2_registro_nuevo_lago_y_siembra(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $especie = Especie::first();

        $fechaSiembra = Carbon::now()->subDays(20)->toDateString();

        $response = $this->actingAs($propietario)->post('/admin/lagos', [
            'name' => 'Estanque 9',
            'codigo_estanque' => 'EST-09',
            'tipo_estanque' => 'Tierra',
            'especie_id' => $especie->id,
            'fecha_siembra' => $fechaSiembra,
            'cantidad_sembrada' => 5000,
            'peso_promedio_inicial' => 12.5,
        ]);

        $response->assertRedirect('/admin/lagos');

        $pond = Pond::where('code', 'EST-09')->first();
        $this->assertNotNull($pond);
        $this->assertEquals('EST-09', $pond->code);
        $this->assertEquals('Tierra', $pond->tipo_estanque);
        $this->assertEquals(5000, $pond->fish_population);
        $this->assertEquals(12.5, (float) $pond->average_weight);
        // Biomasa = (5000 * 12.5) / 1000 = 62.5 kg
        $this->assertEquals(62.5, (float) $pond->biomass);
    }

    /**
     * TAREA 3: Reestructuración de Roles y Exclusividad de Ventas y Finanzas.
     * Solo 'propietario' puede acceder a /cosechas, /ventas, /nomina.
     */
    public function test_tarea_3_exclusividad_ventas_y_nomina_solo_propietario(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();

        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO]);
        $tecnico = User::factory()->create(['role' => User::ROLE_TECNICO_ACUICOLA]);

        // Propietario tiene acceso concedido
        $this->actingAs($propietario)->get(route('ventas.index'))->assertOk();
        $this->actingAs($propietario)->get(route('cosechas.index'))->assertOk();
        $this->actingAs($propietario)->get(route('nomina.index'))->assertOk();

        // Operario recibe 403 Forbidden
        $this->actingAs($operario)->get(route('ventas.index'))->assertStatus(403);
        $this->actingAs($operario)->get(route('cosechas.index'))->assertStatus(403);
        $this->actingAs($operario)->get(route('nomina.index'))->assertStatus(403);

        // Técnico recibe 403 Forbidden
        $this->actingAs($tecnico)->get(route('ventas.index'))->assertStatus(403);
        $this->actingAs($tecnico)->get(route('cosechas.index'))->assertStatus(403);
        $this->actingAs($tecnico)->get(route('nomina.index'))->assertStatus(403);
    }

    /**
     * TAREA 4: Gestión de Personal y Asignación de Roles Múltiples.
     * PersonalController no expone 'propietario' ni 'admin_general', y permite multi-rol sync.
     */
    public function test_tarea_4_gestion_personal_multi_roles_sin_propietario(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();

        $nuevoEmpleado = User::factory()->create([
            'role' => User::ROLE_PENDIENTE,
            'finca_id' => $propietario->finca_id ?? 1,
            'tenant_id' => $propietario->tenant_id ?? 1,
        ]);

        $rolOperario = Role::where('slug', 'operario_campo')->first();
        $rolTecnico = Role::where('slug', 'tecnico_acuicola')->first();

        // Asignación múltiple de roles
        $response = $this->actingAs($propietario)->post("/admin/personal/{$nuevoEmpleado->id}/aprobar", [
            'roles' => [$rolOperario->id, $rolTecnico->id],
        ]);

        $response->assertRedirect('/admin/personal');
        $nuevoEmpleado->refresh();
        $this->assertFalse($nuevoEmpleado->isPendiente());
        $this->assertTrue($nuevoEmpleado->roles->contains('slug', 'operario_campo'));
        $this->assertTrue($nuevoEmpleado->roles->contains('slug', 'tecnico_acuicola'));
        $this->assertFalse($nuevoEmpleado->roles->contains('slug', 'propietario'));
    }

    /**
     * TAREA 5: Agenda Operativa y Encadenamiento de Turnos.
     * Asignar Lunes a Viernes programa automáticamente el domingo anterior como celador noche.
     */
    public function test_tarea_5_agenda_operativa_encadenamiento_turnos(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO, 'finca_id' => 1]);

        $lunesProximo = Carbon::now()->addWeek()->startOfWeek();
        $domingoAnterior = $lunesProximo->copy()->subDay();

        $response = $this->actingAs($propietario)->postJson('/agenda/turnos/semanal', [
            'user_id' => $operario->id,
            'fecha_lunes' => $lunesProximo->toDateString(),
            'observaciones' => 'Turno semanal rotativo',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true, 'turnos_creados' => 6]);

        // Verificar domingo anterior
        $this->assertTrue(
            AgendaTurno::where('user_id', $operario->id)
                ->whereDate('fecha', $domingoAnterior->toDateString())
                ->where('tipo_dia', AgendaTurno::TIPO_DOMINGO)
                ->where('rol_asignado', AgendaTurno::ROL_SEGURIDAD_NOCHE)
                ->exists()
        );

        // Verificar 5 días de la semana de alimentación
        $countSemana = AgendaTurno::where('user_id', $operario->id)
            ->where('rol_asignado', AgendaTurno::ROL_ALIMENTADOR)
            ->count();

        $this->assertEquals(5, $countSemana);
    }

    /**
     * TAREA 6: Middlewares de Validación de Turnos.
     * Bloquea operarios sin turno y permite bypass a propietario/tecnico.
     */
    public function test_tarea_6_middlewares_validacion_turnos(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $operarioSinTurno = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO]);

        // Propietario tiene bypass en /operaciones/alimentacion
        $this->actingAs($propietario)->get('/operaciones/alimentacion')->assertOk();

        // Operario sin turno es bloqueado con 403 en petición JSON o API
        $responseBloqueadoJson = $this->actingAs($operarioSinTurno)->getJson('/operaciones/alimentacion');
        $responseBloqueadoJson->assertStatus(403);
        $responseBloqueadoJson->assertJson(['error' => 'SIN_TURNO_ALIMENTACION']);

        // Operario sin turno es redirigido en navegación web con mensaje de advertencia
        $responseBloqueadoWeb = $this->actingAs($operarioSinTurno)->get('/operaciones/alimentacion');
        $responseBloqueadoWeb->assertRedirect(route('trabajador.dashboard'));
    }

    /**
     * TAREA 7: Control de Alimento sin Costos Unitarios.
     * La tabla ingresos_alimento no tiene precios ni costos, y suma stock físico.
     */
    public function test_tarea_7_control_alimento_sin_costos_unitarios(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();

        // Verificar que la estructura no tiene columnas monetarias
        $this->assertFalse(Schema::hasColumn('ingresos_alimento', 'precio_unitario'));
        $this->assertFalse(Schema::hasColumn('ingresos_alimento', 'costo_total'));
        $this->assertFalse(Schema::hasColumn('ingresos_alimento', 'valor_bulto'));

        // Registrar ingreso logístico
        $response = $this->actingAs($propietario)->post('/admin/inventario-alimento/ingresar', [
            'fecha_recepcion' => now()->toDateString(),
            'proveedor' => 'Italcol Acuicultura',
            'tipo_concentrado' => 'Iniciación 45%',
            'bultos_recibidos' => 20,
            'peso_bulto_kg' => 40,
            'lote_fabrica' => 'LOT-ITAL-9988',
        ]);

        $response->assertRedirect(route('admin.inventario-alimento.index'));

        $this->assertDatabaseHas('ingresos_alimento', [
            'proveedor' => 'Italcol Acuicultura',
            'tipo_concentrado' => 'Iniciación 45%',
            'bultos_recibidos' => 20,
            'kilos_totales' => 800,
        ]);
    }

    /**
     * TAREA 8: Traslados de Peces entre Lagos (Desdobles).
     * Descuenta origen y suma destino con recálculo atómico de biomasas.
     */
    public function test_tarea_8_desdoble_traslado_peces(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $especie = Especie::first();

        $origen = Pond::create([
            'finca_id' => 1,
            'species_id' => $especie->id,
            'name' => 'Estanque Origen',
            'code' => 'EST-ORG',
            'tipo_estanque' => 'Tierra',
            'current_population' => 10000,
            'fish_population' => 10000,
            'current_average_weight_g' => 50.0,
            'average_weight' => 50.0,
            'current_biomass_kg' => 500.0,
            'biomass' => 500.0,
            'status' => 'active',
        ]);

        $destino = Pond::create([
            'finca_id' => 1,
            'species_id' => $especie->id,
            'name' => 'Estanque Destino',
            'code' => 'EST-DST',
            'tipo_estanque' => 'Geomembrana',
            'current_population' => 0,
            'fish_population' => 0,
            'current_average_weight_g' => 0.0,
            'average_weight' => 0.0,
            'current_biomass_kg' => 0.0,
            'biomass' => 0.0,
            'status' => 'active',
        ]);

        $response = $this->actingAs($propietario)->post('/admin/desdobles', [
            'estanque_origen_id' => $origen->id,
            'estanque_destino_id' => $destino->id,
            'cantidad_peces_trasladados' => 4000,
            'peso_promedio_gramos' => 50.0,
            'motivo' => TrasladoPeces::MOTIVO_DESDOBLE_DENSIDAD,
        ]);

        $response->assertRedirect(route('traslados.index'));

        $origen->refresh();
        $destino->refresh();

        // Origen pasa de 10000 a 6000 peces. Biomasa = (6000 * 50) / 1000 = 300 kg
        $this->assertEquals(6000, $origen->fish_population);
        $this->assertEquals(300.0, (float) $origen->biomass);

        // Destino pasa de 0 a 4000 peces. Biomasa = (4000 * 50) / 1000 = 200 kg
        $this->assertEquals(4000, $destino->fish_population);
        $this->assertEquals(200.0, (float) $destino->biomass);
    }

    /**
     * TAREA 9: Bitácora de Actividades de Trabajadores.
     * Registra automáticamente acciones de alimentación, traslados, etc.
     */
    public function test_tarea_9_bitacora_actividades_registro_automatico(): void
    {
        $this->seed(DatabaseSeeder::class);
        $propietario = User::where('email', 'propietario@finca.com')->first();
        $operario = User::factory()->create(['role' => User::ROLE_OPERARIO_CAMPO]);

        // Registrar actividad explícita a través del helper o al alimentar
        ActividadTrabajador::registrar(
            $operario,
            'alimentacion',
            'Alimentación matutina de 45kg concentrado en estanque principal',
            null,
            $operario->role
        );

        $this->assertDatabaseHas('actividades_trabajadores', [
            'user_id' => $operario->id,
            'tipo_accion' => 'alimentacion',
        ]);

        // Propietario puede ver la bitácora
        $response = $this->actingAs($propietario)->get('/admin/actividades');
        $response->assertOk();
        $response->assertSee('Alimentación matutina');
    }
}
