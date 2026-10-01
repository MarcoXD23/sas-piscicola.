<?php

namespace Tests\Feature;

use App\Models\AgendaTurno;
use App\Models\AlimentoBodega;
use App\Models\Estanque;
use App\Models\FeedingLog;
use App\Models\Finca;
use App\Models\MovimientoBodega;
use App\Models\Pond;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlujoAlimentacionYBodegaTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca1;

    private Finca $finca2;

    private User $propietario;

    private User $administrador;

    private User $operario;

    private User $celador;

    private Pond $lago1;

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

        $this->operario = User::factory()->create([
            'name' => 'Juan Operario Alimentador',
            'email' => 'juan.alimentador@esmeralda.com',
            'role' => User::ROLE_OPERARIO_ALIMENTADOR,
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

        $this->lago1 = Estanque::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago 1 Levante',
            'code' => 'L-01',
            'area' => 1200,
            'average_depth' => 1.5,
            'fish_population' => 4000,
            'average_weight' => 200.0,
            'biomass' => 800.0,
            'status' => 'Sembrado',
        ]);

        // Asignar turno activo de hoy para el operario
        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'rol_asignado' => 'alimentador',
            'tipo_turno' => 'diurno',
            'estado' => 'programado',
        ]);
    }

    /**
     * REQUISITO 1: Menú Lateral (sidebar.blade.php).
     * Propietario y Administrador ven "Control de Alimentación" (/admin/alimentacion/historial).
     * Operario ve "Suministrar Alimento" (/operaciones/alimentacion).
     */
    public function test_sidebar_muestra_control_de_alimentacion_para_propietario_y_administrador(): void
    {
        // 1. Propietario ve Control de Alimentación
        $responsePropietario = $this->actingAs($this->propietario)->get(route('admin.bodega.index'));
        $responsePropietario->assertOk();
        $responsePropietario->assertSee('Control de Alimentación');
        $responsePropietario->assertSee(route('admin.alimentacion.historial'));

        // 2. Administrador ve Control de Alimentación
        $responseAdmin = $this->actingAs($this->administrador)->get(route('admin.bodega.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Control de Alimentación');
        $responseAdmin->assertSee(route('admin.alimentacion.historial'));
    }

    public function test_sidebar_muestra_suministrar_alimento_para_operario_y_no_el_control_admin(): void
    {
        $responseOperario = $this->actingAs($this->operario)->get(route('operaciones.alimentacion.index'));
        $responseOperario->assertOk();
        $responseOperario->assertSee('Suministrar Alimento');
        $responseOperario->assertSee(route('operaciones.alimentacion.index'));
        $responseOperario->assertDontSee('Control de Alimentación');
    }

    /**
     * REQUISITO 2: Flujo de Bodega en Dos Pasos.
     * Paso 1: Propietario registra despacho a finca -> Queda en tránsito y NO suma al stock.
     */
    public function test_propietario_registra_despacho_queda_en_transito_y_no_suma_stock_inmediato(): void
    {
        $response = $this->actingAs($this->propietario)->post(route('admin.bodega.despacho'), [
            'fecha_despacho' => now()->toDateString(),
            'proveedor' => 'Italcol Acuicultura',
            'tipo_concentrado' => 'Levante 34%',
            'cantidad_bultos' => 30.0,
            'peso_bulto_kg' => 40.0,
            'lote_fabrica' => 'LOT-ITAL-9911',
            'observaciones' => 'Despacho principal para la quincena',
        ]);

        $response->assertRedirect(route('admin.bodega.index'));
        $response->assertSessionHas('success');

        // Verificar que el concentrado existe pero su stock disponible sigue en 0
        $alimento = AlimentoBodega::where('finca_id', $this->finca1->id)
            ->where('nombre_concentrado', 'Levante 34%')
            ->first();

        $this->assertNotNull($alimento);
        $this->assertEquals(0.0, (float) $alimento->stock_bultos);
        $this->assertEquals(0.0, (float) $alimento->stock_kilos_actual);

        // Verificar que se creó el movimiento en estado despacho_en_transito
        $this->assertDatabaseHas('movimientos_bodega', [
            'finca_id' => $this->finca1->id,
            'alimento_id' => $alimento->id,
            'user_id' => $this->propietario->id,
            'tipo_movimiento' => 'despacho_en_transito',
            'cantidad_bultos' => 30.0,
            'cantidad_kilos' => 1200.0,
            'proveedor' => 'Italcol Acuicultura',
        ]);
    }

    /**
     * REQUISITO 2: El registro de despacho NO permite campos monetarios ni de costos.
     */
    public function test_despacho_rechaza_campos_monetarios(): void
    {
        $response = $this->actingAs($this->propietario)->postJson(route('admin.bodega.despacho'), [
            'fecha_despacho' => now()->toDateString(),
            'proveedor' => 'Solla S.A.',
            'tipo_concentrado' => 'Engorde 30%',
            'cantidad_bultos' => 20.0,
            'peso_bulto_kg' => 40.0,
            'costo_unitario' => 125000, // Campo prohibido
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'El control de despacho es 100% de inventario físico. No se permiten campos monetarios.',
        ]);
    }

    /**
     * REQUISITO 2: Mientras el despacho esté en tránsito, el operario NO puede alimentar
     * porque el stock disponible en bodega es insuficiente (0 kg) -> 422.
     */
    public function test_operario_es_rechazado_con_422_si_el_despacho_no_ha_sido_confirmado(): void
    {
        // 1. Propietario despacha 25 bultos (1000 kg)
        $this->actingAs($this->propietario)->post(route('admin.bodega.despacho'), [
            'fecha_despacho' => now()->toDateString(),
            'proveedor' => 'Italcol Acuicultura',
            'tipo_concentrado' => 'Levante 34%',
            'cantidad_bultos' => 25.0,
            'peso_bulto_kg' => 40.0,
        ]);

        // 2. Operario intenta suministrar 40 kg al lago
        $responseOperario = $this->actingAs($this->operario)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'amount_kg' => 40.0,
            'tipo_concentrado' => 'Levante 34%',
            'appetite_level' => 'bueno',
        ]);

        $responseOperario->assertStatus(422);
        $responseOperario->assertJsonValidationErrors(['amount_kg']);
        $this->assertStringContainsString(
            'Stock insuficiente en bodega para suministrar esa cantidad.',
            $responseOperario->json('errors.amount_kg.0')
        );
    }

    /**
     * REQUISITO 2: Paso 2: Administrador confirma la recepción física en bodega.
     * Solo en ese momento se suman los bultos y kilos al stock de bodega dentro de DB::transaction.
     */
    public function test_administrador_confirma_recepcion_fisica_y_suma_stock_dentro_de_transaccion(): void
    {
        // 1. Despacho del propietario
        $this->actingAs($this->propietario)->post(route('admin.bodega.despacho'), [
            'fecha_despacho' => now()->toDateString(),
            'proveedor' => 'Italcol Acuicultura',
            'tipo_concentrado' => 'Levante 34%',
            'cantidad_bultos' => 25.0,
            'peso_bulto_kg' => 40.0,
        ]);

        $movimiento = MovimientoBodega::where('finca_id', $this->finca1->id)
            ->where('tipo_movimiento', 'despacho_en_transito')
            ->firstOrFail();

        // 2. Administrador de finca confirma recepción física
        $responseConfirmacion = $this->actingAs($this->administrador)->post(
            route('admin.bodega.despachos.confirmar', $movimiento->id),
            ['bultos_confirmados' => 25.0]
        );

        $responseConfirmacion->assertRedirect(route('admin.bodega.index'));
        $responseConfirmacion->assertSessionHas('success');

        // 3. Verificar que ahora SÍ hay stock disponible en bodega: 25 bultos y 1000 kg
        $alimento = AlimentoBodega::where('finca_id', $this->finca1->id)
            ->where('nombre_concentrado', 'Levante 34%')
            ->first();

        $this->assertEquals(25.0, (float) $alimento->stock_bultos);
        $this->assertEquals(1000.0, (float) $alimento->stock_kilos_actual);

        // 4. El movimiento pasó a ser entrada_compra oficial
        $movimiento->refresh();
        $this->assertEquals(MovimientoBodega::TIPO_ENTRADA_COMPRA, $movimiento->tipo_movimiento);
    }

    /**
     * REQUISITO 2: Tras la confirmación del Administrador, el operario PUEDE alimentar
     * y el stock se descuenta de inmediato de bodega.
     */
    public function test_operario_puede_alimentar_exitosamente_tras_confirmacion_del_administrador(): void
    {
        // 1. Despacho
        $this->actingAs($this->propietario)->post(route('admin.bodega.despacho'), [
            'fecha_despacho' => now()->toDateString(),
            'proveedor' => 'Italcol',
            'tipo_concentrado' => 'Engorde 32%',
            'cantidad_bultos' => 10.0,
            'peso_bulto_kg' => 40.0, // 400 kg
        ]);

        $movimiento = MovimientoBodega::where('tipo_movimiento', 'despacho_en_transito')->firstOrFail();

        // 2. Confirmación Administrador
        $this->actingAs($this->administrador)->post(route('admin.bodega.despachos.confirmar', $movimiento->id));

        // 3. Operario suministra 45 kg
        $responseAlimentar = $this->actingAs($this->operario)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'amount_kg' => 45.0,
            'tipo_concentrado' => 'Engorde 32%',
            'appetite_level' => 'bueno',
        ]);

        $responseAlimentar->assertStatus(201);

        // 4. Stock en bodega descontado: 400 - 45 = 355 kg
        $alimento = AlimentoBodega::where('nombre_concentrado', 'Engorde 32%')->first();
        $this->assertEquals(355.0, (float) $alimento->stock_kilos_actual);

        // 5. Registro creado en feeding_logs
        $this->assertDatabaseHas('feeding_logs', [
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'pond_id' => $this->lago1->id,
            'amount_kg' => 45.0,
        ]);
    }

    /**
     * REQUISITO 3: Filtro de Personal (/admin/personal).
     * Excluye estrictamente el rol de propietario en la aprobación de personal (retorna 422).
     */
    public function test_aprobacion_personal_rechaza_estrictamente_asignar_rol_propietario(): void
    {
        $aspirante = User::factory()->create([
            'name' => 'Aspirante Juan',
            'email' => 'aspirante@esmeralda.com',
            'finca_id' => $this->finca1->id,
            'role' => User::ROLE_PENDIENTE,
            'aprobado' => false,
        ]);

        // Intento de asignar rol propietario al aspirante
        $response = $this->actingAs($this->propietario)->postJson(
            route('admin.personal.asignar-rol', $aspirante),
            [
                'roles' => ['propietario'],
                'employment_type' => 'destajo_semanal',
            ]
        );

        $response->assertStatus(422);

        // Verificar que el usuario no fue aprobado ni tiene rol de propietario
        $aspirante->refresh();
        $this->assertFalse((bool) $aspirante->aprobado);
        $this->assertNotEquals(User::ROLE_PROPIETARIO, $aspirante->role);
    }

    /**
     * REQUISITO 3: Permite selección multi-rol de campo válida (operario_alimentador, celador, tecnico_acuicola).
     */
    public function test_aprobacion_personal_permite_roles_de_campo_validos_con_seleccion_multiple(): void
    {
        $aspirante = User::factory()->create([
            'name' => 'Aspirante Multi-Rol',
            'email' => 'multi@esmeralda.com',
            'finca_id' => $this->finca1->id,
            'role' => User::ROLE_PENDIENTE,
            'aprobado' => false,
        ]);

        $response = $this->actingAs($this->propietario)->postJson(
            route('admin.personal.asignar-rol', $aspirante),
            [
                'roles' => ['operario_alimentador', 'celador'],
                'employment_type' => 'destajo_semanal',
            ]
        );

        $response->assertOk();

        $aspirante->refresh();
        $this->assertTrue((bool) $aspirante->aprobado);
        $this->assertContains('operario_alimentador', (array) $aspirante->roles_asignados);
        $this->assertContains('celador', (array) $aspirante->roles_asignados);
    }

    /**
     * REQUISITO 1: Vista de Auditoría de Historial (/admin/alimentacion/historial).
     * Muestra Fecha, Hora, Estanque, Kilos, Tipo de Concentrado y Trabajador Responsable.
     */
    public function test_historial_de_alimentacion_muestra_datos_completos_de_auditoria(): void
    {
        FeedingLog::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'pond_id' => $this->lago1->id,
            'feeding_date' => now()->toDateString(),
            'amount_kg' => 60.0,
            'feed_name' => 'Levante 34%',
            'appetite_level' => 'bueno',
        ]);

        $response = $this->actingAs($this->propietario)->get(route('admin.alimentacion.historial'));
        $response->assertOk();
        $response->assertSee('Lago 1 Levante');
        $response->assertSee('60,00 kg');
        $response->assertSee('Levante 34%');
        $response->assertSee('Juan Operario Alimentador');
    }

    /**
     * REQUISITO Multi-Tenant: Finca 2 no puede ver ni confirmar despachos de Finca 1.
     */
    public function test_aislamiento_multitenant_en_despacho_y_confirmacion(): void
    {
        $propietarioFinca2 = User::factory()->create([
            'name' => 'Dueño Finca 2',
            'email' => 'dueno2@sanrafael.com',
            'role' => User::ROLE_PROPIETARIO,
            'finca_id' => $this->finca2->id,
        ]);

        // Finca 1 crea despacho
        $this->actingAs($this->propietario)->post(route('admin.bodega.despacho'), [
            'fecha_despacho' => now()->toDateString(),
            'proveedor' => 'Italcol',
            'tipo_concentrado' => 'Iniciación 45%',
            'cantidad_bultos' => 15.0,
            'peso_bulto_kg' => 40.0,
        ]);

        $despachoFinca1 = MovimientoBodega::where('finca_id', $this->finca1->id)->firstOrFail();

        // Dueño de Finca 2 intenta confirmar despacho de Finca 1 -> 404 No encontrado en su tenant
        $responseFinca2 = $this->actingAs($propietarioFinca2)->post(
            route('admin.bodega.despachos.confirmar', $despachoFinca1->id)
        );

        $responseFinca2->assertNotFound();
    }
}
