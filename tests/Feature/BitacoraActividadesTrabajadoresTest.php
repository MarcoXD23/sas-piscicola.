<?php

namespace Tests\Feature;

use App\Models\ActividadTrabajador;
use App\Models\AgendaTurno;
use App\Models\AlimentoBodega;
use App\Models\Finca;
use App\Models\MovimientoBodega;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BitacoraActividadesTrabajadoresTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca1;

    private Finca $finca2;

    private User $propietario;

    private User $administrador;

    private User $operarioAlimentador;

    private User $operarioCampo;

    private User $celador;

    private User $trabajadorFinca2;

    private Pond $estanque1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca1 = Finca::firstOrCreate(['id' => 1], [
            'nombre' => 'Piscícola La Esmeralda',
            'codigo' => 'ESM-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->finca2 = Finca::firstOrCreate(['id' => 2], [
            'nombre' => 'Piscícola San Rafael',
            'codigo' => 'RAF-02',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->propietario = User::factory()->create([
            'name' => 'Don Roberto Propietario',
            'email' => 'propietario@esmeralda.com',
            'role' => User::ROLE_PROPIETARIO,
            'finca_id' => $this->finca1->id,
            'aprobado' => true,
        ]);

        $this->administrador = User::factory()->create([
            'name' => 'Carlos Administrador Finca',
            'email' => 'admin@esmeralda.com',
            'role' => User::ROLE_ADMINISTRADOR,
            'finca_id' => $this->finca1->id,
            'aprobado' => true,
        ]);

        $this->operarioAlimentador = User::factory()->create([
            'name' => 'Juan Operario Alimentador',
            'email' => 'juan.alimentador@esmeralda.com',
            'role' => User::ROLE_OPERARIO_ALIMENTADOR,
            'finca_id' => $this->finca1->id,
            'aprobado' => true,
        ]);

        $this->operarioCampo = User::factory()->create([
            'name' => 'Pedro Operario Campo',
            'email' => 'pedro.campo@esmeralda.com',
            'role' => 'operario_campo',
            'finca_id' => $this->finca1->id,
            'aprobado' => true,
        ]);

        $this->celador = User::factory()->create([
            'name' => 'Faustino Celador',
            'email' => 'faustino.celador@esmeralda.com',
            'role' => User::ROLE_CELADOR,
            'finca_id' => $this->finca1->id,
            'aprobado' => true,
        ]);

        $this->trabajadorFinca2 = User::factory()->create([
            'name' => 'Gonzalo Trabajador Finca 2',
            'email' => 'gonzalo@sanrafael.com',
            'role' => User::ROLE_OPERARIO_ALIMENTADOR,
            'finca_id' => $this->finca2->id,
            'aprobado' => true,
        ]);

        $this->estanque1 = Pond::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago 1 Alevinaje',
            'code' => 'L-01',
            'area' => 1000,
            'average_depth' => 1.5,
            'fish_population' => 5000,
            'average_weight' => 50.0,
            'biomass' => 250.0,
            'status' => 'Sembrado',
        ]);
    }

    public function test_propietario_y_administrador_pueden_ver_bitacora_con_aislamiento_por_finca(): void
    {
        // Actividad en Finca 1
        ActividadTrabajador::create([
            'user_id' => $this->operarioAlimentador->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => ActividadTrabajador::ACCION_ALIMENTACION,
            'descripcion' => 'Suministró 25.0 kg de concentrado 38% en Lago 1 Alevinaje',
            'estanque_id' => $this->estanque1->id,
        ]);

        // Actividad en Finca 2
        ActividadTrabajador::create([
            'user_id' => $this->trabajadorFinca2->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => ActividadTrabajador::ACCION_ALIMENTACION,
            'descripcion' => 'Actividad confidencial exclusiva de Finca 2',
            'estanque_id' => null,
        ]);

        // 1. Propietario de Finca 1
        $responsePropietario = $this->actingAs($this->propietario)->get(route('admin.actividades.index'));
        $responsePropietario->assertOk();
        $responsePropietario->assertViewIs('admin.actividades.index');
        $responsePropietario->assertSee('Suministró 25.0 kg de concentrado 38% en Lago 1 Alevinaje');
        $responsePropietario->assertDontSee('Actividad confidencial exclusiva de Finca 2');

        // 2. Administrador de Finca 1
        $responseAdmin = $this->actingAs($this->administrador)->get(route('admin.actividades.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Suministró 25.0 kg de concentrado 38% en Lago 1 Alevinaje');
        $responseAdmin->assertDontSee('Actividad confidencial exclusiva de Finca 2');
    }

    public function test_sidebar_muestra_enlace_registro_actividades_solo_a_propietario_y_administrador(): void
    {
        $responsePropietario = $this->actingAs($this->propietario)->get(route('admin.actividades.index'));
        $responsePropietario->assertSee('Registro de Actividades');

        $responseAdmin = $this->actingAs($this->administrador)->get(route('admin.actividades.index'));
        $responseAdmin->assertSee('Registro de Actividades');

        $responseOperario = $this->actingAs($this->operarioCampo)->get(route('trabajador.mortalidad.index'));
        $responseOperario->assertDontSee('Registro de Actividades');
    }

    public function test_operarios_y_celadores_reciben_error_403_al_intentar_acceder(): void
    {
        // 1. Operario Alimentador bloqueado
        $responseAlimentador = $this->actingAs($this->operarioAlimentador)->get(route('admin.actividades.index'));
        $responseAlimentador->assertForbidden();

        // 2. Operario de Campo bloqueado
        $responseCampo = $this->actingAs($this->operarioCampo)->get(route('admin.actividades.index'));
        $responseCampo->assertForbidden();

        // 3. Celador bloqueado
        $responseCelador = $this->actingAs($this->celador)->get(route('admin.actividades.index'));
        $responseCelador->assertForbidden();
    }

    public function test_filtrado_por_trabajador(): void
    {
        $this->actingAs($this->propietario);

        $actividadJuan = ActividadTrabajador::create([
            'user_id' => $this->operarioAlimentador->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => ActividadTrabajador::ACCION_ALIMENTACION,
            'descripcion' => 'Ración de mañana aplicada por Juan',
            'estanque_id' => $this->estanque1->id,
        ]);

        $actividadPedro = ActividadTrabajador::create([
            'user_id' => $this->operarioCampo->id,
            'rol_momento' => 'operario_campo',
            'tipo_accion' => ActividadTrabajador::ACCION_MORTALIDAD,
            'descripcion' => 'Retiro de peces muertos por Pedro',
            'estanque_id' => $this->estanque1->id,
        ]);

        $response = $this->get(route('admin.actividades.index', ['user_id' => $this->operarioAlimentador->id]));

        $response->assertOk();
        $response->assertSee('Ración de mañana aplicada por Juan');
        $response->assertDontSee('Retiro de peces muertos por Pedro');
    }

    public function test_filtrado_por_rol_desempenado(): void
    {
        $this->actingAs($this->propietario);

        ActividadTrabajador::create([
            'user_id' => $this->operarioAlimentador->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => ActividadTrabajador::ACCION_ALIMENTACION,
            'descripcion' => 'Labor realizada en rol de alimentador',
            'estanque_id' => $this->estanque1->id,
        ]);

        ActividadTrabajador::create([
            'user_id' => $this->celador->id,
            'rol_momento' => 'seguridad_noche',
            'tipo_accion' => ActividadTrabajador::ACCION_RONDA_NOCTURNA,
            'descripcion' => 'Labor realizada en rol de seguridad nocturna',
            'estanque_id' => null,
        ]);

        $response = $this->get(route('admin.actividades.index', ['rol' => 'seguridad_noche']));

        $response->assertOk();
        $response->assertSee('Labor realizada en rol de seguridad nocturna');
        $response->assertDontSee('Labor realizada en rol de alimentador');
    }

    public function test_filtrado_por_tipo_de_accion(): void
    {
        $this->actingAs($this->propietario);

        ActividadTrabajador::create([
            'user_id' => $this->operarioAlimentador->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => 'alimentacion',
            'descripcion' => 'Evento de alimentación de tilapias',
            'estanque_id' => $this->estanque1->id,
        ]);

        ActividadTrabajador::create([
            'user_id' => $this->operarioCampo->id,
            'rol_momento' => 'operario_campo',
            'tipo_accion' => 'traslado_peces',
            'descripcion' => 'Evento de traslado entre estanques',
            'estanque_id' => $this->estanque1->id,
        ]);

        // Filtrar usando alias 'suministro_alimento'
        $response = $this->get(route('admin.actividades.index', ['tipo_accion' => 'suministro_alimento']));

        $response->assertOk();
        $response->assertSee('Evento de alimentación de tilapias');
        $response->assertDontSee('Evento de traslado entre estanques');
    }

    public function test_filtrado_por_rango_de_fechas(): void
    {
        $this->actingAs($this->propietario);

        $hoy = now()->format('Y-m-d');

        $actividadAyer = ActividadTrabajador::create([
            'user_id' => $this->operarioAlimentador->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => 'alimentacion',
            'descripcion' => 'Actividad antigua de hace dos días',
            'estanque_id' => $this->estanque1->id,
        ]);
        $actividadAyer->created_at = now()->subDays(2);
        $actividadAyer->saveQuietly();

        $actividadHoy = ActividadTrabajador::create([
            'user_id' => $this->operarioAlimentador->id,
            'rol_momento' => 'alimentador',
            'tipo_accion' => 'alimentacion',
            'descripcion' => 'Actividad reciente de la fecha actual',
            'estanque_id' => $this->estanque1->id,
        ]);
        $actividadHoy->created_at = now();
        $actividadHoy->saveQuietly();

        // Filtrar exclusivamente para hoy
        $response = $this->get(route('admin.actividades.index', [
            'fecha_inicio' => $hoy,
            'fecha_fin' => $hoy,
        ]));

        $response->assertOk();
        $response->assertSee('Actividad reciente de la fecha actual');
        $response->assertDontSee('Actividad antigua de hace dos días');
    }

    public function test_estado_vacio_amigable_cuando_no_hay_coincidencias(): void
    {
        $this->actingAs($this->propietario);

        $response = $this->get(route('admin.actividades.index', ['rol' => 'rol_inexistente_xyz']));

        $response->assertOk();
        $response->assertSee('No se encontraron registros de actividades para los criterios seleccionados');
    }

    public function test_registro_automatico_de_actividades_al_realizar_acciones_operativas(): void
    {
        // 1. Simular acción de traslado en DesdobleController
        $estanqueDestino = Pond::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago 2 Levante',
            'code' => 'L-02',
            'area' => 1200,
            'average_depth' => 1.5,
            'fish_population' => 0,
            'average_weight' => 0.0,
            'biomass' => 0.0,
            'status' => 'Vacio',
        ]);

        $this->actingAs($this->propietario)->post(route('traslados.store'), [
            'estanque_origen_id' => $this->estanque1->id,
            'estanque_destino_id' => $estanqueDestino->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 500,
            'peso_promedio_gramos' => 50.0,
            'motivo' => 'Desdoble por densidad/crecimiento',
            'observaciones' => 'Prueba de auditoría automática',
        ]);

        // Verificar que quedó registrado automáticamente en actividades_trabajadores
        $this->assertDatabaseHas('actividades_trabajadores', [
            'user_id' => $this->propietario->id,
            'tipo_accion' => ActividadTrabajador::ACCION_TRASLADO,
        ]);

        // 2. Simular reporte de mortalidad por operario de campo
        $this->actingAs($this->operarioCampo)->post(route('trabajador.mortalidad'), [
            'estanque_id' => $this->estanque1->id,
            'cantidad_peces' => 5,
            'causa_probable' => 'Mortalidad matutina rutinaria',
        ]);

        $this->assertDatabaseHas('actividades_trabajadores', [
            'user_id' => $this->operarioCampo->id,
            'tipo_accion' => ActividadTrabajador::ACCION_MORTALIDAD,
        ]);
    }
}
