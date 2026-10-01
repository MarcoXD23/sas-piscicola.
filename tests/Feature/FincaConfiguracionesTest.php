<?php

namespace Tests\Feature;

use App\Models\DailyLabor;
use App\Models\Finca;
use App\Models\FishSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FincaConfiguracionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear finca base 1
        Finca::create([
            'id' => 1,
            'nombre' => 'Finca Piscícola La Esperanza',
            'codigo' => 'FINCA-01',
            'ubicacion' => 'Espinal, Tolima',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);
    }

    public function test_finca_model_methods_work_correctly(): void
    {
        $finca = Finca::find(1);

        // obtenerConfig
        $this->assertEquals(7000, $finca->obtenerConfig('precios.pescado_empleado_kg'));
        $this->assertEquals(9000, $finca->obtenerConfig('precios.pescado_visitante_kg'));
        $this->assertEquals(2.0, $finca->obtenerConfig('operacion.peso_tara_canastilla_kg'));
        $this->assertEquals('default_value', $finca->obtenerConfig('inexistente.clave', 'default_value'));

        // tieneModulo
        $this->assertTrue($finca->tieneModulo('celador_nocturno'));
        $this->assertTrue($finca->tieneModulo('asistente_ia'));

        // actualizarConfig
        $finca->actualizarConfig('precios.pescado_empleado_kg', 8500);
        $finca->actualizarConfig('modulos_activos.celador_nocturno', false);
        $finca->save();

        $finca->refresh();
        $this->assertEquals(8500, $finca->obtenerConfig('precios.pescado_empleado_kg'));
        $this->assertFalse($finca->tieneModulo('celador_nocturno'));
    }

    public function test_blade_modulo_directive_evaluates_tenant_config(): void
    {
        $finca = Finca::find(1);
        $user = User::factory()->admin()->create(['finca_id' => $finca->id]);

        $this->actingAs($user);

        // celador_nocturno está activo
        $template = "@modulo('celador_nocturno') MODULO_NOCTURNO_ACTIVO @endmodulo";
        $rendered = Blade::render($template);
        $this->assertStringContainsString('MODULO_NOCTURNO_ACTIVO', $rendered);

        // Desactivar celador_nocturno
        $finca->actualizarConfig('modulos_activos.celador_nocturno', false);
        $finca->save();
        $user->refresh();

        $renderedDisabled = Blade::render($template);
        $this->assertStringNotContainsString('MODULO_NOCTURNO_ACTIVO', $renderedDisabled);
    }

    public function test_admin_can_view_and_update_finca_settings(): void
    {
        $admin = User::factory()->admin()->create(['finca_id' => 1]);

        // Ver pantalla de ajustes
        $response = $this->actingAs($admin)->get(route('admin.ajustes'));
        $response->assertStatus(200)
            ->assertSee('Parámetros Operativos')
            ->assertSee('Tarifas de Pescado')
            ->assertSee('Volver al Dashboard');

        // Actualizar parámetros
        $updateResponse = $this->actingAs($admin)->post(route('admin.ajustes.update'), [
            'pescado_visitante_kg' => 9500,
            'pescado_empleado_kg' => 7500,
            'peso_tara_canastilla_kg' => 2.5,
            'dia_pesca_habitual' => 'martes',
            'dia_pago_nomina' => 'viernes',
            'especies_habilitadas' => ['mojarra_roja', 'cachama'],
        ]);

        $updateResponse->assertRedirect(route('admin.ajustes'));
        $updateResponse->assertSessionHas('success');

        $finca = Finca::find(1);
        $this->assertEquals(9500, $finca->obtenerConfig('precios.pescado_visitante_kg'));
        $this->assertEquals(7500, $finca->obtenerConfig('precios.pescado_empleado_kg'));
        $this->assertEquals(2.5, $finca->obtenerConfig('operacion.peso_tara_canastilla_kg'));
        $this->assertEquals('martes', $finca->obtenerConfig('operacion.dia_pesca_habitual'));
        $this->assertEquals(['mojarra_roja', 'cachama'], $finca->obtenerConfig('especies_habilitadas'));
    }

    public function test_superadmin_can_manage_fincas_and_toggle_module_flags(): void
    {
        $jefe = User::factory()->jefeFinca()->create(['finca_id' => 1]);

        // Listado de fincas
        $listResponse = $this->actingAs($jefe)->get(route('superadmin.fincas.index'));
        $listResponse->assertStatus(200)
            ->assertSee('Gestión de Fincas y Feature Flags')
            ->assertSee('Finca Piscícola La Esperanza');

        // Formulario de edición de plan
        $editResponse = $this->actingAs($jefe)->get(route('superadmin.fincas.edit', 1));
        $editResponse->assertStatus(200)
            ->assertSee('Activar / Desactivar Módulos')
            ->assertSee('Módulo Celador Nocturno');

        // Desactivar módulos via PUT
        $updateResponse = $this->actingAs($jefe)->put(route('superadmin.fincas.update', 1), [
            'nombre' => 'Finca La Esperanza Renovada',
            'codigo' => 'ESP-01',
            'ubicacion' => 'Espinal, Tolima',
            'modulos' => [
                'asistente_ia' => '1',
                'ventas_visitantes' => '1',
                // celador_nocturno omitido => se apaga
            ],
        ]);

        $updateResponse->assertRedirect(route('superadmin.fincas.edit', 1));

        $finca = Finca::find(1);
        $this->assertEquals('Finca La Esperanza Renovada', $finca->nombre);
        $this->assertTrue($finca->tieneModulo('asistente_ia'));
        $this->assertTrue($finca->tieneModulo('ventas_visitantes'));
        $this->assertFalse($finca->tieneModulo('celador_nocturno'));
        $this->assertFalse($finca->tieneModulo('planta_procesamiento_merma'));
    }

    public function test_fish_sales_and_payroll_use_dynamic_finca_prices(): void
    {
        // Configurar finca 1 con precios personalizados
        $finca = Finca::find(1);
        $finca->actualizarConfig('precios.pescado_visitante_kg', 11000);
        $finca->actualizarConfig('precios.pescado_empleado_kg', 8000);
        $finca->save();

        $admin = User::factory()->admin()->create(['finca_id' => 1]);
        $worker = User::factory()->worker()->temporal()->create([
            'name' => 'Pedro Jornalero',
            'finca_id' => 1,
        ]);

        // Venta a visitante sin precio explícito => toma el precio de la finca ($11.000)
        $saleResponse = $this->actingAs($admin)->postJson('/api/fish-sales', [
            'finca_id' => 1,
            'customer_type' => 'visitante',
            'customer_name' => 'Comprador Mayorista',
            'kilos_sold' => 10.0,
            'payment_method' => 'efectivo',
        ]);

        $saleResponse->assertStatus(201);
        $this->assertEquals(11000, $saleResponse->json('data.price_per_kg'));
        $this->assertEquals(110000, $saleResponse->json('data.total_amount'));

        // Registrar 1 jornal y 2 kg de pescado fiado
        DailyLabor::create([
            'finca_id' => 1,
            'worker_name' => $worker->name,
            'user_id' => $worker->id,
            'employment_type' => DailyLabor::TYPE_TEMPORAL,
            'work_date' => '2026-10-21',
            'labor_type' => DailyLabor::LABOR_PESCA,
            'daily_wage' => 60000.0,
            'payment_status' => DailyLabor::STATUS_PENDIENTE,
            'registered_by_user_id' => $admin->id,
        ]);

        FishSale::create([
            'finca_id' => 1,
            'sale_date' => '2026-10-21',
            'customer_type' => FishSale::TYPE_WORKER,
            'customer_name' => $worker->name,
            'kilos_sold' => 2.0,
            'price_per_kg' => 8000.0,
            'total_amount' => 16000.0,
            'payment_method' => 'descuento_nomina',
            'registered_by_user_id' => $admin->id,
        ]);

        // Cálculo previo de nómina semanal del sábado
        $calcResponse = $this->actingAs($admin)->getJson(route('api.weekly_payroll.calculate', [
            'cutoff_date' => '2026-10-24',
        ]));

        $calcResponse->assertStatus(200);

        $temporalList = collect($calcResponse->json('nomina_personal_temporal'));
        $workerRow = $temporalList->firstWhere('user_id', $worker->id);
        $this->assertNotNull($workerRow);

        // Descuento debe ser 2 kg * $8.000 = $16.000
        $this->assertEquals(8000.0, (float) $workerRow['tarifa_pescado_kilo']);
        $this->assertEquals(16000.0, (float) $workerRow['descuento_pescado_fiado']);
        $this->assertEquals(44000.0, (float) $workerRow['total_neto_a_pagar']);
    }
}
