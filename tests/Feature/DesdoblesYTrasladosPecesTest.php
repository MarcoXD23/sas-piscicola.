<?php

namespace Tests\Feature;

use App\Models\ActividadTrabajador;
use App\Models\Finca;
use App\Models\Pond;
use App\Models\TrasladoPeces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesdoblesYTrasladosPecesTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca1;

    private Finca $finca2;

    private User $propietario;

    private User $tecnico;

    private User $operarioCampo;

    private Pond $estanqueOrigen;

    private Pond $estanqueDestinoConPeces;

    private Pond $estanqueDestinoVacio;

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

        $this->tecnico = User::factory()->create([
            'name' => 'Ing. Acuícola Fernando',
            'email' => 'tecnico@esmeralda.com',
            'role' => User::ROLE_TECNICO_ACUICOLA,
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

        // Estanque Origen: 5,000 peces de 100g cada uno (biomasa = 500.0 kg)
        $this->estanqueOrigen = Pond::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago Origen #1',
            'code' => 'L-ORIG-01',
            'area' => 1000,
            'average_depth' => 1.5,
            'fish_population' => 5000,
            'average_weight' => 100.0,
            'biomass' => 500.0,
            'status' => 'Sembrado',
        ]);

        // Estanque Destino con peces: 2,000 peces de 200g cada uno (biomasa = 400.0 kg)
        $this->estanqueDestinoConPeces = Pond::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago Destino Poblado #2',
            'code' => 'L-DEST-02',
            'area' => 1500,
            'average_depth' => 1.5,
            'fish_population' => 2000,
            'average_weight' => 200.0,
            'biomass' => 400.0,
            'status' => 'Sembrado',
        ]);

        // Estanque Destino vacío: 0 peces
        $this->estanqueDestinoVacio = Pond::create([
            'finca_id' => $this->finca1->id,
            'name' => 'Lago Destino Vacio #3',
            'code' => 'L-DEST-03',
            'area' => 1200,
            'average_depth' => 1.5,
            'fish_population' => 0,
            'average_weight' => 0.0,
            'biomass' => 0.0,
            'status' => 'Vacio',
        ]);
    }

    public function test_traslado_exitoso_actualiza_poblaciones_peso_ponderado_y_biomasas_transaccionalmente(): void
    {
        $this->actingAs($this->tecnico);

        // Trasladar 1,000 peces a 100g desde estanqueOrigen hacia estanqueDestinoConPeces
        $response = $this->postJson(route('traslados.store'), [
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueDestinoConPeces->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 1000,
            'peso_promedio_gramos' => 100.0,
            'merma_traslado_peces' => 0,
            'motivo' => 'Desdoble por densidad/crecimiento',
            'observaciones' => 'Desdoble preventivo por saturación de carga',
        ]);

        $response->assertStatus(201);

        // Verificar Origen: 5000 - 1000 = 4000 peces. Biomasa = (4000 * 100) / 1000 = 400.0 kg
        $this->estanqueOrigen->refresh();
        $this->assertEquals(4000, $this->estanqueOrigen->fish_population);
        $this->assertEquals(400.0, (float) $this->estanqueOrigen->biomass);
        $this->assertEquals('Sembrado', $this->estanqueOrigen->status);

        // Verificar Destino: 2000 + 1000 = 3000 peces.
        // Peso ponderado = ((2000 * 200) + (1000 * 100)) / 3000 = 500,000 / 3000 = 166.67 g
        // Biomasa = (3000 * 166.67) / 1000 = 500.01 kg
        $this->estanqueDestinoConPeces->refresh();
        $this->assertEquals(3000, $this->estanqueDestinoConPeces->fish_population);
        $this->assertEquals(166.67, round((float) $this->estanqueDestinoConPeces->average_weight, 2));
        $this->assertEquals(500.01, round((float) $this->estanqueDestinoConPeces->biomass, 2));

        // Registro de auditoría en traslados_peces
        $this->assertDatabaseHas('traslados_peces', [
            'finca_id' => $this->finca1->id,
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueDestinoConPeces->id,
            'cantidad_peces_trasladados' => 1000,
            'peso_promedio_gramos' => 100.0,
            'motivo' => 'Desdoble por densidad/crecimiento',
        ]);

        // Registro en ActividadTrabajador
        $this->assertDatabaseHas('actividades_trabajadores', [
            'user_id' => $this->tecnico->id,
            'tipo_accion' => ActividadTrabajador::ACCION_TRASLADO,
        ]);
    }

    public function test_traslado_total_vacia_el_estanque_origen_y_activa_el_estanque_destino_vacio(): void
    {
        $this->actingAs($this->propietario);

        // Trasladar todos los 5,000 peces del origen al estanque vacío
        $response = $this->post(route('traslados.store'), [
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueDestinoVacio->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 5000,
            'peso_promedio_gramos' => 100.0,
            'merma_traslado_peces' => 0,
            'motivo' => 'Mantenimiento/secado de estanque',
            'observaciones' => 'Vaciado total para desinfección y encalado',
        ]);

        $response->assertRedirect(route('traslados.index'));

        // Origen debe quedar en 0 peces, biomasa 0 y estado Vacio
        $this->estanqueOrigen->refresh();
        $this->assertEquals(0, $this->estanqueOrigen->fish_population);
        $this->assertEquals(0.0, (float) $this->estanqueOrigen->biomass);
        $this->assertEquals('Vacio', $this->estanqueOrigen->status);

        // Destino debe quedar con 5,000 peces, peso 100g, biomasa 500kg y estado Sembrado
        $this->estanqueDestinoVacio->refresh();
        $this->assertEquals(5000, $this->estanqueDestinoVacio->fish_population);
        $this->assertEquals(100.0, (float) $this->estanqueDestinoVacio->average_weight);
        $this->assertEquals(500.0, (float) $this->estanqueDestinoVacio->biomass);
        $this->assertEquals('Sembrado', $this->estanqueDestinoVacio->status);
    }

    public function test_rechaza_con_422_y_mensaje_exacto_si_la_cantidad_supera_los_peces_vivos_del_origen(): void
    {
        $this->actingAs($this->operarioCampo);

        // Origen tiene 5,000 peces. Intentamos trasladar 5,001 peces.
        $response = $this->postJson(route('traslados.store'), [
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueDestinoVacio->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 5001,
            'peso_promedio_gramos' => 100.0,
            'merma_traslado_peces' => 0,
            'motivo' => 'Clasificación por tallas',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'La cantidad a trasladar no puede superar los peces vivos actuales del estanque de origen.',
        ]);

        // Verificar que no hubo cambios en la base de datos
        $this->estanqueOrigen->refresh();
        $this->assertEquals(5000, $this->estanqueOrigen->fish_population);
        $this->assertEquals(500.0, (float) $this->estanqueOrigen->biomass);
    }

    public function test_rechaza_con_422_si_el_estanque_origen_es_igual_al_estanque_destino(): void
    {
        $this->actingAs($this->tecnico);

        $response = $this->postJson(route('traslados.store'), [
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueOrigen->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 500,
            'peso_promedio_gramos' => 100.0,
            'motivo' => 'Sanitario',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['estanque_origen_id']);
    }

    public function test_rechaza_si_los_estanques_pertenecen_a_fincas_diferentes_multi_tenancy(): void
    {
        // Crear estanque en Finca 2
        $estanqueFinca2 = Pond::create([
            'finca_id' => $this->finca2->id,
            'name' => 'Lago Finca 2',
            'code' => 'L-F2-01',
            'area' => 1000,
            'average_depth' => 1.5,
            'fish_population' => 1000,
            'average_weight' => 150.0,
            'biomass' => 150.0,
            'status' => 'Sembrado',
        ]);

        $this->actingAs($this->propietario);

        $response = $this->postJson(route('traslados.store'), [
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $estanqueFinca2->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 500,
            'peso_promedio_gramos' => 100.0,
            'motivo' => 'Desdoble por densidad/crecimiento',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Los estanques seleccionados deben pertenecer a la misma finca.',
        ]);
    }

    public function test_traslado_con_merma_de_manejo_descuenta_merma_en_destino(): void
    {
        $this->actingAs($this->tecnico);

        // Trasladar 1,000 peces con merma de 50 peces durante la captura
        $response = $this->postJson(route('traslados.store'), [
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueDestinoVacio->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 1000,
            'peso_promedio_gramos' => 120.0,
            'merma_traslado_peces' => 50,
            'motivo' => 'Desdoble por densidad/crecimiento',
        ]);

        $response->assertStatus(201);

        // Origen pierde 1,000 peces: 5,000 - 1,000 = 4,000
        $this->estanqueOrigen->refresh();
        $this->assertEquals(4000, $this->estanqueOrigen->fish_population);

        // Destino recibe 1,000 - 50 = 950 peces vivos
        $this->estanqueDestinoVacio->refresh();
        $this->assertEquals(950, $this->estanqueDestinoVacio->fish_population);
        $this->assertEquals(120.0, (float) $this->estanqueDestinoVacio->average_weight);
        $this->assertEquals(114.0, (float) $this->estanqueDestinoVacio->biomass); // (950 * 120) / 1000 = 114.0 kg

        // Registrar merma en histórico
        $this->assertDatabaseHas('traslados_peces', [
            'cantidad_peces_trasladados' => 1000,
            'merma_traslado_peces' => 50,
        ]);
    }

    public function test_vista_traslados_muestra_historial_y_estanques(): void
    {
        $this->actingAs($this->propietario);

        // Crear un registro previo
        TrasladoPeces::create([
            'finca_id' => $this->finca1->id,
            'estanque_origen_id' => $this->estanqueOrigen->id,
            'estanque_destino_id' => $this->estanqueDestinoConPeces->id,
            'user_id' => $this->propietario->id,
            'fecha' => now()->toDateString(),
            'cantidad_peces_trasladados' => 800,
            'peso_promedio_gramos' => 100.0,
            'merma_traslado_peces' => 10,
            'motivo' => 'Desdoble por densidad/crecimiento',
        ]);

        $response = $this->get(route('traslados.index'));

        $response->assertStatus(200);
        $response->assertViewIs('traslados.index');
        $response->assertSee('Desdobles');
        $response->assertSee('Lago Origen #1');
        $response->assertSee('Lago Destino Poblado #2');
    }
}
