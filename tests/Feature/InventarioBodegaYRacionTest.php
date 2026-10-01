<?php

namespace Tests\Feature;

use App\Models\AgendaTurno;
use App\Models\AlimentoBodega;
use App\Models\Estanque;
use App\Models\FeedingLog;
use App\Models\Finca;
use App\Models\InventarioAlimento;
use App\Models\MovimientoBodega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioBodegaYRacionTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca1;

    private Finca $finca2;

    private User $propietario;

    private User $operario;

    private User $tecnico;

    private User $celador;

    private Estanque $lago1;

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
        ]);

        $this->operario = User::factory()->create([
            'name' => 'Juan Operario Alimentador',
            'email' => 'juan.alimentador@esmeralda.com',
            'role' => User::ROLE_OPERARIO_ALIMENTADOR,
            'finca_id' => $this->finca1->id,
        ]);

        $this->tecnico = User::factory()->create([
            'name' => 'Laura Técnica Acuícola',
            'email' => 'laura.tecnica@esmeralda.com',
            'role' => User::ROLE_TECNICO_ACUICOLA,
            'finca_id' => $this->finca1->id,
        ]);

        $this->celador = User::factory()->create([
            'name' => 'Faustino Celador Nocturno',
            'email' => 'faustino.celador@esmeralda.com',
            'role' => User::ROLE_CELADOR,
            'finca_id' => $this->finca1->id,
        ]);

        $this->lago1 = Estanque::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago 1 Levante',
            'code' => 'L-01',
            'area' => 1500,
            'average_depth' => 1.6,
            'fish_population' => 5000,
            'average_weight' => 220.0,
            'biomass' => 1100.0,
            'status' => 'Sembrado',
        ]);
    }

    /**
     * REQUISITO 1: Recepción de alimento exclusiva para Propietario.
     * Campos estrictamente físicos, cálculo automático de kilos (bultos × peso_bulto) y sin costos/precios.
     */
    public function test_propietario_puede_registrar_llegada_de_alimento_sin_costos_calculando_kilos_automaticamente(): void
    {
        $response = $this->actingAs($this->propietario)->post(route('admin.inventario-alimento.ingresar'), [
            'fecha_recepcion' => now()->toDateString(),
            'proveedor' => 'Italcol Acuicultura',
            'tipo_concentrado' => 'Levante 34%',
            'bultos_recibidos' => 25.0,
            'peso_bulto_kg' => 40.0,
            'lote_fabrica' => 'LOT-ITAL-7788',
        ]);

        $response->assertRedirect(route('admin.inventario-alimento.index'));
        $response->assertSessionHas('success');

        // Verificar que sumó al stock en alimentos_bodega: 25 bultos * 40 kg = 1000 kg
        $alimento = AlimentoBodega::where('finca_id', $this->finca1->id)
            ->where('nombre_concentrado', 'Levante 34%')
            ->first();

        $this->assertNotNull($alimento);
        $this->assertEquals(25.0, (float) $alimento->stock_bultos);
        $this->assertEquals(1000.0, (float) $alimento->stock_kilos_actual);

        // Verificar registro histórico de auditoría en movimientos_bodega
        $this->assertDatabaseHas('movimientos_bodega', [
            'finca_id' => $this->finca1->id,
            'alimento_id' => $alimento->id,
            'user_id' => $this->propietario->id,
            'tipo_movimiento' => MovimientoBodega::TIPO_ENTRADA_COMPRA,
            'cantidad_bultos' => 25.0,
            'cantidad_kilos' => 1000.0,
            'proveedor' => 'Italcol Acuicultura',
        ]);
    }

    /**
     * REQUISITO 1 & 3: Roles no propietarios reciben 403 al intentar registrar entrada de bodega.
     */
    public function test_roles_no_propietarios_son_bloqueados_al_intentar_registrar_llegada_de_alimento(): void
    {
        // 1. Operario alimentador bloqueado
        $responseOperario = $this->actingAs($this->operario)->post(route('admin.inventario-alimento.ingresar'), [
            'fecha_recepcion' => now()->toDateString(),
            'proveedor' => 'Solla S.A.',
            'tipo_concentrado' => 'Engorde 30%',
            'bultos_recibidos' => 10.0,
            'peso_bulto_kg' => 40.0,
        ]);
        $responseOperario->assertForbidden();

        // 2. Celador bloqueado
        $responseCelador = $this->actingAs($this->celador)->post(route('admin.inventario-alimento.ingresar'), [
            'fecha_recepcion' => now()->toDateString(),
            'proveedor' => 'Solla S.A.',
            'tipo_concentrado' => 'Engorde 30%',
            'bultos_recibidos' => 10.0,
            'peso_bulto_kg' => 40.0,
        ]);
        $responseCelador->assertForbidden();
    }

    /**
     * REQUISITO 2: Salida y descuento automático por ración dentro de DB::transaction por operario.
     */
    public function test_operario_puede_registrar_racion_y_se_descuenta_automaticamente_en_bodega_transaccional(): void
    {
        $alimento = AlimentoBodega::create([
            'finca_id' => $this->finca1->id,
            'nombre_concentrado' => 'Levante 34%',
            'proteina_porcentaje' => 34,
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 20.0,
            'stock_kilos_actual' => 800.0,
            'umbral_alerta_bultos' => 5.0,
        ]);

        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => now()->isWeekend() ? 'sabado' : 'semana',
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        $response = $this->actingAs($this->operario)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'alimento_id' => $alimento->id,
            'amount_kg' => 45.0,
            'appetite_level' => 'bueno',
            'observations' => 'Ración completa de la mañana',
        ]);

        $response->assertStatus(201);

        // Stock debe haber bajado exactamente 45 kg: 800 - 45 = 755 kg
        $alimento->refresh();
        $this->assertEquals(755.0, (float) $alimento->stock_kilos_actual);
        $this->assertEquals(round(755.0 / 40.0, 2), round((float) $alimento->stock_bultos, 2));

        // Registro histórico en movimientos_bodega
        $this->assertDatabaseHas('movimientos_bodega', [
            'finca_id' => $this->finca1->id,
            'alimento_id' => $alimento->id,
            'tipo_movimiento' => MovimientoBodega::TIPO_SALIDA_ALIMENTACION,
            'cantidad_kilos' => 45.0,
        ]);

        // Registro en feeding_logs
        $this->assertDatabaseHas('feeding_logs', [
            'finca_id' => $this->finca1->id,
            'pond_id' => $this->lago1->id,
            'amount_kg' => 45.0,
            'appetite_level' => 'bueno',
        ]);
    }

    /**
     * REQUISITO 2: Bloqueo cuando la ración es mayor al stock disponible en bodega.
     * Debe rechazar con error de validación 422 y el mensaje exacto exigido.
     */
    public function test_bloqueo_por_stock_insuficiente_con_error_422_y_mensaje_exacto(): void
    {
        $alimento = AlimentoBodega::create([
            'finca_id' => $this->finca1->id,
            'nombre_concentrado' => 'Iniciación 45%',
            'proteina_porcentaje' => 45,
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 0.75,
            'stock_kilos_actual' => 30.0, // Solo hay 30 kg en bodega
            'umbral_alerta_bultos' => 2.0,
        ]);

        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => now()->isWeekend() ? 'sabado' : 'semana',
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        // Intento de suministrar 45 kg cuando solo hay 30 kg
        $response = $this->actingAs($this->operario)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'alimento_id' => $alimento->id,
            'amount_kg' => 45.0,
            'appetite_level' => 'bueno',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount_kg']);
        $this->assertStringContainsString(
            'Stock insuficiente en bodega para suministrar esa cantidad.',
            $response->json('message') ?? $response->json('errors.amount_kg.0')
        );

        // El stock no debe haber cambiado
        $alimento->refresh();
        $this->assertEquals(30.0, (float) $alimento->stock_kilos_actual);

        // No debe haberse creado movimiento de salida
        $this->assertDatabaseMissing('movimientos_bodega', [
            'alimento_id' => $alimento->id,
            'tipo_movimiento' => MovimientoBodega::TIPO_SALIDA_ALIMENTACION,
        ]);
    }

    /**
     * REQUISITO 2: Descuento en AlimentacionController rechaza con 422 y mensaje exacto.
     */
    public function test_descuento_por_racion_en_alimentacion_controller_rechaza_con_mensaje_exacto(): void
    {
        $alimento = AlimentoBodega::create([
            'finca_id' => $this->finca1->id,
            'nombre_concentrado' => 'Engorde 32%',
            'proteina_porcentaje' => 32,
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 0.5,
            'stock_kilos_actual' => 20.0,
        ]);

        $response = $this->actingAs($this->tecnico)->postJson(route('api.v1.alimentacion.store'), [
            'pond_id' => $this->lago1->id,
            'alimento_id' => $alimento->id,
            'tipo_concentrado' => 'Engorde 32%',
            'cantidad_kg' => 50.0, // 50 kg requeridos vs 20 kg disponibles
        ]);

        $response->assertStatus(422);
        $this->assertEquals(
            'Stock insuficiente en bodega para suministrar esa cantidad.',
            $response->json('message')
        );
    }

    /**
     * REQUISITO 3: Técnico Acuícola puede registrar ración de alimentación sin restricciones de turno.
     */
    public function test_tecnico_acuicola_puede_registrar_racion_de_alimentacion(): void
    {
        $alimento = AlimentoBodega::create([
            'finca_id' => $this->finca1->id,
            'nombre_concentrado' => 'Engorde 30%',
            'proteina_porcentaje' => 30,
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 10.0,
            'stock_kilos_actual' => 400.0,
        ]);

        $response = $this->actingAs($this->tecnico)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'alimento_id' => $alimento->id,
            'amount_kg' => 20.0,
            'appetite_level' => 'regular',
        ]);

        $response->assertStatus(201);
        $alimento->refresh();
        $this->assertEquals(380.0, (float) $alimento->stock_kilos_actual);
    }

    /**
     * REQUISITO 3: Celador nocturno no puede registrar alimentación.
     */
    public function test_celador_no_puede_registrar_alimentacion(): void
    {
        $alimento = AlimentoBodega::create([
            'finca_id' => $this->finca1->id,
            'nombre_concentrado' => 'Engorde 30%',
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 10.0,
            'stock_kilos_actual' => 400.0,
        ]);

        $response = $this->actingAs($this->celador)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'alimento_id' => $alimento->id,
            'amount_kg' => 15.0,
            'appetite_level' => 'bueno',
        ]);

        $response->assertForbidden();
    }

    /**
     * REQUISITO 3: Aislamiento estricto multi-tenant por finca_id.
     * Los stocks de Finca 1 y Finca 2 no interfieren entre sí.
     */
    public function test_aislamiento_estricto_multi_tenant_de_bodega_y_raciones(): void
    {
        // Alimento en Finca 1: 500 kg
        $alimentoFinca1 = AlimentoBodega::create([
            'finca_id' => $this->finca1->id,
            'nombre_concentrado' => 'Levante 34%',
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 12.5,
            'stock_kilos_actual' => 500.0,
        ]);

        // Alimento en Finca 2: 200 kg
        $alimentoFinca2 = AlimentoBodega::create([
            'finca_id' => $this->finca2->id,
            'nombre_concentrado' => 'Levante 34%',
            'peso_bulto_kg' => 40.0,
            'stock_bultos' => 5.0,
            'stock_kilos_actual' => 200.0,
        ]);

        AgendaTurno::create([
            'finca_id' => $this->finca1->id,
            'user_id' => $this->operario->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => 'semana',
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        // Operario de Finca 1 alimenta Lago 1 con 50 kg
        $response = $this->actingAs($this->operario)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $this->lago1->id,
            'alimento_id' => $alimentoFinca1->id,
            'amount_kg' => 50.0,
            'appetite_level' => 'bueno',
        ]);

        $response->assertStatus(201);

        // Finca 1 debe reducirse a 450 kg
        $alimentoFinca1->refresh();
        $this->assertEquals(450.0, (float) $alimentoFinca1->stock_kilos_actual);

        // Finca 2 debe mantenerse intacta en 200 kg
        $alimentoFinca2->refresh();
        $this->assertEquals(200.0, (float) $alimentoFinca2->stock_kilos_actual);
        $this->assertEquals(5.0, (float) $alimentoFinca2->stock_bultos);
    }
}
