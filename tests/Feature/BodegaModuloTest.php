<?php

namespace Tests\Feature;

use App\Models\AgendaTurno;
use App\Models\AlimentoBodega;
use App\Models\Estanque;
use App\Models\Finca;
use App\Models\MovimientoBodega;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodegaModuloTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca;

    private User $admin;

    private User $jefe;

    private User $trabajador;

    private AlimentoBodega $alimento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'Piscícola San Jerónimo',
            'codigo' => 'SJN-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Carlos Administrador',
            'email' => 'admin@piscicola.com',
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);

        $this->jefe = User::factory()->create([
            'name' => 'Fernando Jefe Mayor',
            'email' => 'jefe@piscicola.com',
            'role' => User::ROLE_OWNER,
            'finca_id' => 1,
        ]);

        $this->trabajador = User::factory()->create([
            'name' => 'Pedro Trabajador',
            'email' => 'pedro@piscicola.com',
            'role' => User::ROLE_TRABAJADOR,
            'finca_id' => 1,
        ]);

        $this->alimento = AlimentoBodega::create([
            'finca_id' => 1,
            'nombre_concentrado' => 'Levante 34%',
            'proteina_porcentaje' => 34,
            'peso_bulto_kg' => 40.00,
            'stock_bultos' => 50.00,
            'stock_kilos_actual' => 2000.00,
            'umbral_alerta_bultos' => 15.00,
            'costo_unitario_bulto' => 125000.00,
        ]);
    }

    public function test_administrador_y_jefe_mayor_pueden_ver_la_pantalla_de_bodega(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.bodega.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Inventario Físico de Concentrados en Bodega');
        $responseAdmin->assertSee('Levante 34%');

        $responseJefe = $this->actingAs($this->jefe)->get(route('admin.bodega.index'));
        $responseJefe->assertOk();
    }

    public function test_usuario_no_autorizado_no_puede_acceder_a_bodega(): void
    {
        $response = $this->actingAs($this->trabajador)->get(route('admin.bodega.index'));
        $response->assertForbidden();
    }

    public function test_recepcion_de_camion_suma_al_stock_y_registra_movimiento_de_entrada(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bodega.entrada'), [
            'alimento_id' => $this->alimento->id,
            'cantidad_bultos' => 20,
            'costo_unitario_bulto' => 126000,
            'proveedor' => 'Italcol Alimentos',
            'fecha' => now()->toDateString(),
            'observaciones' => 'Camión de reparto #485',
        ]);

        $response->assertRedirect(route('admin.bodega.index'));
        $response->assertSessionHas('success');

        $this->alimento->refresh();
        $this->assertEquals(70.00, (float) $this->alimento->stock_bultos);
        $this->assertEquals(2800.00, (float) $this->alimento->stock_kilos_actual);

        $this->assertDatabaseHas('movimientos_bodega', [
            'alimento_id' => $this->alimento->id,
            'tipo_movimiento' => MovimientoBodega::TIPO_ENTRADA_COMPRA,
            'cantidad_bultos' => 20.00,
            'cantidad_kilos' => 800.00,
            'proveedor' => 'Italcol Alimentos',
        ]);
    }

    public function test_ajuste_de_stock_modifica_inventario_y_registra_movimiento_de_merma(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bodega.ajuste', $this->alimento->id), [
            'stock_bultos' => 45,
            'motivo' => 'Conteo de fin de mes y 5 bultos dañados por humedad',
        ]);

        $response->assertRedirect(route('admin.bodega.index'));
        $response->assertSessionHas('success');

        $this->alimento->refresh();
        $this->assertEquals(45.00, (float) $this->alimento->stock_bultos);
        $this->assertEquals(1800.00, (float) $this->alimento->stock_kilos_actual);

        $this->assertDatabaseHas('movimientos_bodega', [
            'alimento_id' => $this->alimento->id,
            'tipo_movimiento' => MovimientoBodega::TIPO_AJUSTE_MERMA,
            'cantidad_bultos' => 5.00,
            'cantidad_kilos' => 200.00,
        ]);
    }

    public function test_descuento_automatico_al_alimentar_lago_desde_panel_de_trabajador(): void
    {
        $estanque = Estanque::create([
            'finca_id' => 1,
            'name' => 'Lago 1 Ceba',
            'code' => 'L-01',
            'area' => 1200,
            'average_depth' => 1.5,
            'fish_population' => 4000,
            'average_weight' => 250.00,
            'biomass' => 1000.00,
            'status' => 'Activo',
        ]);

        $stockInicialKg = (float) $this->alimento->stock_kilos_actual;
        $stockInicialBultos = (float) $this->alimento->stock_bultos;

        // Asignar turno activo de alimentador para hoy en la Agenda Operativa
        AgendaTurno::create([
            'finca_id' => 1,
            'user_id' => $this->trabajador->id,
            'fecha' => now()->toDateString(),
            'tipo_dia' => now()->isWeekend() ? 'sabado' : 'semana',
            'rol_asignado' => AgendaTurno::ROL_ALIMENTADOR,
            'estado' => AgendaTurno::ESTADO_ACTIVO,
        ]);

        $response = $this->actingAs($this->trabajador)->postJson(route('trabajador.alimentacion'), [
            'pond_id' => $estanque->id,
            'alimento_id' => $this->alimento->id,
            'amount_kg' => 25.0,
            'appetite_level' => 'bueno',
            'observations' => 'Ración completa mañana',
        ]);

        $response->assertCreated();

        $this->alimento->refresh();
        $this->assertEquals($stockInicialKg - 25.0, (float) $this->alimento->stock_kilos_actual);
        $this->assertEquals(round($stockInicialBultos - (25.0 / 40.0), 2), round((float) $this->alimento->stock_bultos, 2));

        $this->assertDatabaseHas('movimientos_bodega', [
            'alimento_id' => $this->alimento->id,
            'tipo_movimiento' => MovimientoBodega::TIPO_SALIDA_ALIMENTACION,
            'cantidad_kilos' => 25.00,
        ]);
    }

    public function test_tarjeta_de_inventario_en_dashboard_vincula_a_bodega_con_metricas_reales(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee(route('admin.bodega.index'));
        $responseAdmin->assertSee('Inventario de Alimento & Días Restantes');
        $responseAdmin->assertSee('50.0'); // Bultos en bodega

        $responseJefe = $this->actingAs($this->jefe)->get(route('jefe.dashboard'));
        $responseJefe->assertOk();
        $responseJefe->assertSee(route('admin.bodega.index'));
        $responseJefe->assertSee('Autonomía de Bodega');
    }
}
