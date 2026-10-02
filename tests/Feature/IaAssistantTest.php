<?php

namespace Tests\Feature;

use App\Models\AlimentoBodega;
use App\Models\Especie;
use App\Models\Estanque;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaAssistantTest extends TestCase
{
    use RefreshDatabase;

    private Finca $finca;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar configuración de API key por defecto en pruebas para testear motor local
        Config::set('services.gemini.api_key', null);
        putenv('GEMINI_API_KEY=');

        $this->finca = Finca::create([
            'id' => 1,
            'nombre' => 'El SAS Piscícola',
            'codigo' => 'SAS-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Carlos Administrador',
            'email' => 'admin@piscicola.com',
            'role' => User::ROLE_ADMIN,
            'finca_id' => 1,
        ]);
    }

    public function test_chat_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/asistente-ia/chat', [
            'message' => '¿Cómo está la biomasa de la granja?',
        ]);

        $response->assertStatus(401);
    }

    public function test_chat_validates_message(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_chat_returns_oxigeno_protocol_on_low_oxigeno_query(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Qué hago si el oxígeno baja de 3.0 mg/L?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => true,
            ]);

        $reply = $response->json('reply');
        $this->assertStringContainsString('OXÍGENO DISUELTO', $reply);
        $this->assertStringContainsString('Aireadores', $reply);
        $this->assertStringContainsString('No alimentar', $reply);
        $this->assertStringContainsString('madrugada', $reply);
    }

    public function test_chat_returns_oxigeno_3_5_chip_query_with_zootecnic_parameters(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Qué hacer si el oxígeno baja de 3.5 mg/L?',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');
        $this->assertStringContainsString('3.5 mg/L', $reply);
        $this->assertStringContainsString('Aireadores', $reply);
        $this->assertStringContainsString('No alimentar', $reply);
        $this->assertStringContainsString('26°C y 30°C', $reply);
        $this->assertStringContainsString('6.5 y 8.5', $reply);
    }

    public function test_chat_returns_racion_calculation_with_real_biomass(): void
    {
        // Crear estanques con biomasa conocida
        Estanque::create([
            'finca_id' => $this->finca->id,
            'code' => 'EST-01',
            'name' => 'Lago Principal',
            'fish_population' => 5000,
            'biomass' => 1250.50,
            'average_weight' => 250.10,
            'tipo_estanque' => 'Tierra',
            'status' => 'En Crecimiento',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Cómo calcular la ración de hoy?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => true,
            ]);

        $reply = $response->json('reply');
        $this->assertStringContainsString('1250.5', $reply);
        $this->assertStringContainsString('% PV', $reply);
        $this->assertStringContainsString('Ración Diaria', $reply);
        $this->assertStringContainsString('Iniciación', $reply);
        $this->assertStringContainsString('Engorde', $reply);
    }

    public function test_chat_returns_lagos_listos_para_cosecha_with_actual_ponds(): void
    {
        $especie = Especie::firstOrCreate(
            ['nombre_cientifico' => 'Oreochromis sp.'],
            ['nombre_comun' => 'Mojarra Roja', 'activo' => true]
        );

        // Lago listo (> 450g)
        Estanque::create([
            'finca_id' => $this->finca->id,
            'especie_id' => $especie->id,
            'code' => 'EST-05',
            'name' => 'Lago Engorde Final',
            'fish_population' => 4000,
            'average_weight' => 520.00,
            'biomass' => 2080.00,
            'tipo_estanque' => 'Geomembrana',
            'status' => 'En Cosecha',
        ]);

        // Lago en crecimiento (< 450g)
        Estanque::create([
            'finca_id' => $this->finca->id,
            'especie_id' => $especie->id,
            'code' => 'EST-01',
            'name' => 'Lago Alevinaje',
            'fish_population' => 10000,
            'average_weight' => 45.00,
            'biomass' => 450.00,
            'tipo_estanque' => 'Tierra',
            'status' => 'En Crecimiento',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => 'Resumen de lagos listos para cosecha',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        $this->assertStringContainsString('EST-05', $reply);
        $this->assertStringContainsString('520', $reply);
        $this->assertStringContainsString('2080', $reply);
    }

    public function test_chat_returns_censo_total_and_specific_pond_details(): void
    {
        $especie = Especie::firstOrCreate(
            ['nombre_cientifico' => 'Piaractus brachypomus'],
            ['nombre_comun' => 'Cachama Blanca', 'activo' => true]
        );

        Estanque::create([
            'finca_id' => $this->finca->id,
            'especie_id' => $especie->id,
            'code' => 'EST-01',
            'name' => 'Lago Principal',
            'fish_population' => 5000,
            'average_weight' => 200.00,
            'biomass' => 1000.00,
            'tipo_estanque' => 'Tierra',
            'status' => 'En Crecimiento',
        ]);

        Estanque::create([
            'finca_id' => $this->finca->id,
            'especie_id' => $especie->id,
            'code' => 'EST-02',
            'name' => 'Precria 1',
            'fish_population' => 8000,
            'average_weight' => 25.00,
            'biomass' => 200.00,
            'tipo_estanque' => 'Geomembrana',
            'status' => 'En Crecimiento',
        ]);

        // Consulta censo general
        $respCenso = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Cuántos peces tenemos en total?',
        ]);

        $respCenso->assertStatus(200);
        $replyCenso = $respCenso->json('reply');
        $this->assertStringContainsString('13000', $replyCenso);
        $this->assertStringContainsString('EST-01', $replyCenso);
        $this->assertStringContainsString('EST-02', $replyCenso);

        // Consulta estanque específico
        $respEstanque = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Cómo está el estanque precria 1?',
        ]);

        $respEstanque->assertStatus(200);
        $replyEstanque = $respEstanque->json('reply');
        $this->assertStringContainsString('Precria 1', $replyEstanque);
        $this->assertStringContainsString('8000', $replyEstanque);
        $this->assertStringContainsString('25 g', $replyEstanque);
    }

    public function test_chat_returns_bodega_inventory_with_real_stock(): void
    {
        AlimentoBodega::create([
            'finca_id' => $this->finca->id,
            'nombre_concentrado' => 'Iniciación 45% PB',
            'proteina_porcentaje' => 45,
            'peso_bulto_kg' => 40.00,
            'stock_bultos' => 15.00,
            'stock_kilos_actual' => 600.00,
            'umbral_alerta_bultos' => 5.00,
            'costo_unitario_bulto' => 120000.00,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => 'Inventario disponible en bodega',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        $this->assertStringContainsString('Iniciación 45% PB', $reply);
        $this->assertStringContainsString('600', $reply);
        $this->assertStringContainsString('15 bultos', $reply);
    }

    public function test_chat_calls_google_gemini_api_when_key_is_present(): void
    {
        Config::set('services.gemini.api_key', 'AIzaFakeKeyTesting123');
        putenv('GEMINI_API_KEY=AIzaFakeKeyTesting123');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Respuesta técnica desde Google Gemini: El oxígeno en la finca debe mantenerse sobre 4.0 mg/L.',
                                ],
                            ],
                            'role' => 'model',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Cuál es el rango recomendado de oxígeno?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => false,
                'reply' => 'Respuesta técnica desde Google Gemini: El oxígeno en la finca debe mantenerse sobre 4.0 mg/L.',
            ]);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && str_contains($request['system_instruction']['parts'][0]['text'], 'El SAS Piscícola')
                && $request['contents'][0]['parts'][0]['text'] === '¿Cuál es el rango recomendado de oxígeno?';
        });
    }

    public function test_chat_handles_gemini_api_failure_without_500_error(): void
    {
        Config::set('services.gemini.api_key', 'AIzaFakeKeyTesting123');
        putenv('GEMINI_API_KEY=AIzaFakeKeyTesting123');

        // Simular falla 503 de Google Gemini API
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('Service Unavailable', 503),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/asistente-ia/chat', [
            'message' => '¿Qué hago si el oxígeno baja de 3.0 mg/L?',
        ]);

        // NUNCA debe dar 500: responde con motor local
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => true,
            ]);

        $this->assertStringContainsString('OXÍGENO DISUELTO', $response->json('reply'));
    }

    public function test_chat_web_route_alias_works_with_csrf_session(): void
    {
        $response = $this->actingAs($this->user)->postJson('/asistente-ia/chat', [
            'message' => 'Hola, resumen técnico de la granja',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }
}

