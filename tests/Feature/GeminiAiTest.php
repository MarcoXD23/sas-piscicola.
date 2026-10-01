<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GeminiAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_chat_validates_required_message(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_ai_chat_returns_intelligent_fallback_when_api_key_is_empty(): void
    {
        Config::set('services.gemini.api_key', null);

        $user = User::factory()->admin()->create();

        // 1. Pregunta sobre precios de pescado
        $responsePrecios = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => '¿A cuánto se vende el kilo de pescado para visitantes y trabajadores?',
        ]);

        $responsePrecios->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => true,
            ]);

        $this->assertStringContainsString('9.000', $responsePrecios->json('reply'));
        $this->assertStringContainsString('7.000', $responsePrecios->json('reply'));

        // 2. Pregunta sobre cálculo de báscula
        $responseBascula = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => '¿Cómo calculo la báscula y la tara de canastillas?',
        ]);

        $responseBascula->assertStatus(200);
        $this->assertStringContainsString('Tara estándar por canastilla', $responseBascula->json('reply'));
        $this->assertStringContainsString('2.0 kg', $responseBascula->json('reply'));
        $this->assertStringContainsString('Peso Limpio Real', $responseBascula->json('reply'));

        // 3. Pregunta sobre liquidación de nómina de los sábados
        $responseNomina = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => '¿Cómo funciona la liquidación del sábado y el fiado?',
        ]);

        $responseNomina->assertStatus(200);
        $this->assertStringContainsString('Personal Fijo', $responseNomina->json('reply'));
        $this->assertStringContainsString('excluido', $responseNomina->json('reply'));
        $this->assertStringContainsString('7.000', $responseNomina->json('reply'));

        // 4. Pregunta con números específicos de báscula
        $responseCalculoDirecto = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => 'Tengo 650 kg brutos y 15 canastillas. ¿Cuál es el peso limpio?',
        ]);

        $responseCalculoDirecto->assertStatus(200);
        $this->assertStringContainsString('650.00 kg', $responseCalculoDirecto->json('reply'));
        $this->assertStringContainsString('15 unidades', $responseCalculoDirecto->json('reply'));
        $this->assertStringContainsString('620.00 kg', $responseCalculoDirecto->json('reply'));
    }

    public function test_ai_chat_calls_google_gemini_api_when_key_is_present(): void
    {
        Config::set('services.gemini.api_key', 'AIzaSyFakeGeminiApiKey12345');
        Config::set('services.gemini.model', 'gemini-1.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Para 500 kg brutos con 10 canastillas de 2 kg de tara, el peso limpio real despachado es de 480 kg.',
                                ],
                            ],
                            'role' => 'model',
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => 'Tengo 500 kg brutos y 10 canastillas. ¿Cuál es el peso limpio?',
            'history' => [
                [
                    'role' => 'user',
                    'text' => 'Hola, estoy en la báscula.',
                ],
                [
                    'role' => 'model',
                    'text' => 'Hola Administrador, listo para asistirte con el pesaje.',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'reply' => 'Para 500 kg brutos con 10 canastillas de 2 kg de tara, el peso limpio real despachado es de 480 kg.',
                'model' => 'gemini-1.5-flash',
                'is_simulated' => false,
            ]);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && str_contains($request->url(), 'key=AIzaSyFakeGeminiApiKey12345')
                && isset($data['system_instruction']['parts'][0]['text'])
                && str_contains($data['system_instruction']['parts'][0]['text'], 'Asistente Experto de El SAS Piscícola')
                && count($data['contents']) === 3;
        });
    }

    public function test_ai_chat_handles_api_failure_gracefully_with_fallback(): void
    {
        Config::set('services.gemini.api_key', 'AIzaSyFakeGeminiApiKey12345');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded',
                    'code' => 429,
                ],
            ], 429),
        ]);

        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->postJson(route('api.ai.chat'), [
            'message' => '¿Cuál es el precio del pescado a clientes externos?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => true,
            ]);

        $this->assertStringContainsString('9.000', $response->json('reply'));
    }

    public function test_web_route_allows_chat_from_blade_views(): void
    {
        Config::set('services.gemini.api_key', null);

        $response = $this->postJson(route('web.ai.chat'), [
            'message' => '¿Cuáles son los precios oficiales?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_simulated' => true,
            ]);

        $this->assertStringContainsString('9.000', $response->json('reply'));
    }

    public function test_gemini_service_system_prompt_contains_piscicola_rules(): void
    {
        $service = app(GeminiAiService::class);
        $prompt = $service->getSystemPrompt();

        $this->assertStringContainsString('El SAS Piscícola', $prompt);
        $this->assertStringContainsString('9.000', $prompt);
        $this->assertStringContainsString('7.000', $prompt);
        $this->assertStringContainsString('2.0 kg', $prompt);
        $this->assertStringContainsString('SÁBADO', $prompt);
        $this->assertStringContainsString('Trabajadores Fijos', $prompt);
        $this->assertStringContainsString('Personal Temporal / Jornaleros', $prompt);
        $this->assertStringContainsString('America/Bogota', $prompt);
        $this->assertStringContainsString('12 horas', $prompt);
    }
}
