<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected ?string $apiKey;

    protected string $model;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
    }

    /**
     * Enviar mensaje al modelo Google Gemini con prompt del sistema y contexto piscícola.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{reply: string, model: string, is_simulated: bool}
     */
    public function ask(string $message, array $history = []): array
    {
        // Si no hay API Key configurada, proveer respuestas inteligentes de demostración y conocimiento piscícola
        if (empty($this->apiKey)) {
            return [
                'reply' => $this->generateLocalFallbackResponse($message),
                'model' => $this->model,
                'is_simulated' => true,
            ];
        }

        $endpoint = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $contents = $this->buildContentsPayload($message, $history);

        try {
            $response = Http::connectTimeout(5)
                ->timeout(25)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $this->getSystemPrompt()],
                        ],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.6,
                        'maxOutputTokens' => 1200,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $replyText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($replyText) {
                    return [
                        'reply' => trim($replyText),
                        'model' => $this->model,
                        'is_simulated' => false,
                    ];
                }
            }

            Log::warning('Respuesta inesperada de Google Gemini API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'reply' => $this->generateLocalFallbackResponse($message),
                'model' => $this->model.' (contingencia)',
                'is_simulated' => true,
            ];
        } catch (Exception $e) {
            Log::error('Error de comunicación con Google Gemini API: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'reply' => $this->generateLocalFallbackResponse($message),
                'model' => $this->model.' (fallback por error de red)',
                'is_simulated' => true,
            ];
        }
    }

    /**
     * Construye la estructura de mensajes para la API de Gemini (multi-turn chat).
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array<int, array{role: string, parts: array<int, array{text: string}>}>
     */
    protected function buildContentsPayload(string $message, array $history): array
    {
        $contents = [];

        foreach ($history as $entry) {
            $role = match ($entry['role'] ?? '') {
                'model', 'assistant' => 'model',
                default => 'user',
            };

            if (! empty($entry['text'])) {
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $entry['text']],
                    ],
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        return $contents;
    }

    /**
     * Prompt del sistema con conocimiento exhaustivo de las operaciones de El SAS Piscícola.
     */
    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres el "Asistente Experto de El SAS Piscícola", la inteligencia artificial especializada de una destacada empresa y granja de acuicultura en Tolima, Colombia.
Tu propósito es asesorar y guiar al Jefe de Finca (owner / jefe_finca), al Administrador (admin), a los operarios (worker) y celadores (guard) en todas las labores operativas, contables, logísticas y técnicas de la granja.

Reglas del Negocio y Conocimiento de Dominio que DEBES aplicar con precisión:

1. BÁSCULA Y COSECHAS (Kilos Brutos a Kilos Limpios):
   - Las cosechas se pesan por tandas o pesadas múltiples en la báscula.
   - Cada pesada registra el Peso Bruto (kg) y la cantidad de canastillas utilizadas.
   - Cada canastilla tiene una tara estándar de 2.0 kg (a menos que se especifique un valor distinto en báscula).
   - Fórmula: Peso Limpio Real = Total Kilos Brutos - (Total Canastillas * Peso Tara por Canastilla).
   - Al finalizar el pesaje se registra el despacho indicando: Kilos limpios totales, canastillas despachadas, datos completos del conductor (nombre, cédula, placa del vehículo, teléfono) y rumbo o destino del envío.

2. VENTAS Y CAJA DIARIA DE PESCADO:
   - Política de precios oficiales estrictos:
     * Visitantes y compradores externos: $9.000 por kilo.
     * Trabajadores internos de la finca: $7.000 por kilo (tarifa interna preferencial).
   - Opciones de pago: Efectivo (ingresa a caja diaria), Transferencia bancaria, o Pescado Fiado (descuento automático en nómina).

3. LIBRETA DE JORNALES Y NÓMINA DE SÁBADO:
   - Los días de corte y liquidación semanal son estrictamente los días SÁBADO.
   - Diferenciación fundamental de trabajadores:
     * Trabajadores Fijos: Tienen un salario mensual o fijo pactado. Quedan automáticamente EXCLUIDOS de la liquidación semanal del sábado.
     * Personal Temporal / Jornaleros (pescadores, rayadores, personal de lavado de estanques, empaque): Se les registra el jornal diario durante la semana y se les liquida el sábado.
   - Deducción por Pescado Fiado: Si un trabajador temporal llevó pescado fiado durante la semana, el sistema deduce automáticamente de su liquidación del sábado el valor exacto: (Kilos de pescado fiado * $7.000).
   - Fórmula de Liquidación: Total Neto a Pagar = (Jornales acumulados en la semana) - (Kilos fiados * $7.000).

4. HORARIO Y ZONA HORARIA:
   - La finca opera en Colombia (zona horaria America/Bogota).
   - Utiliza SIEMPRE y de forma estricta el formato de 12 horas con indicador AM/PM (ejemplo: "07:30 AM", "03:45 PM"). NUNCA uses formato militar o de 24 horas.

5. ROLES DEL SISTEMA:
   - 'owner' o 'jefe_finca': Dueño o Gerente General. Tiene acceso total e irrestricto a todas las fincas, reportes financieros, auditorías y configuración global.
   - 'admin': Administrador de la finca. Gestiona turnos, cosechas, báscula, ventas de caja diaria y nómina de sábados.
   - 'worker': Operario de campo. Registra raciones y bitácoras de alimentación de estanques.
   - 'guard': Celador / Seguridad. Consulta programación de turnos y accesos.

6. ESTILO DE COMUNICACIÓN:
   - Responde de forma cordial, ejecutiva, clara y en español de Colombia.
   - Utiliza formato Markdown limpio (viñetas, negritas, fórmulas matemáticas desglosadas).
   - Si el usuario te pide un cálculo (ej. cuántos kilos limpios dan 500 kg brutos en 10 canastillas de 2 kg, o cuánto debe pagar un jornalero con 3 jornales de $50.000 y 4 kilos fiados), haz la operación matemática paso a paso.
PROMPT;
    }

    /**
     * Motor local inteligente de respaldo ante ausencia de API Key o falta de internet en la granja rural.
     */
    protected function generateLocalFallbackResponse(string $message): string
    {
        $msg = mb_strtolower(trim($message), 'UTF-8');

        if (str_contains($msg, 'precio') || str_contains($msg, 'tarifa') || str_contains($msg, 'cuanto vale') || str_contains($msg, 'cuesta')) {
            return <<<'MARKDOWN'
**Tarifas Oficiales de Venta de Pescado en El SAS Piscícola:**

1. **Visitantes y Clientes Externos:** **$9.000** por kilo.
2. **Trabajadores Internos:** **$7.000** por kilo (tarifa preferencial para empleados y apoyo).

*Modalidades de pago disponibles:* Efectivo en caja diaria, Transferencia o **Pescado Fiado** (descuento automático en nómina de sábado).
MARKDOWN;
        }

        if (str_contains($msg, 'bascula') || str_contains($msg, 'báscula') || str_contains($msg, 'tara') || str_contains($msg, 'limpio') || str_contains($msg, 'bruto') || str_contains($msg, 'canastilla')) {
            // Intentar detectar si el usuario ingresó números para cálculo directo
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos|kilo|brutos|bruto)?.*?(\d+)\s*(?:canastillas|canastas|canastilla)/i', $msg, $matches)) {
                $gross = (float) str_replace(',', '.', $matches[1]);
                $baskets = (int) $matches[2];
                $taraTotal = $baskets * 2.0;
                $clean = max(0, $gross - $taraTotal);

                return sprintf(
                    "**Resultado de Báscula (Cálculo Inmediato):**\n\n* **Total Kilos Brutos:** %.2f kg\n* **Canastillas:** %d unidades\n* **Tara descontada (2.0 kg/canastilla):** -%.2f kg\n* **Peso Limpio Real a Despachar:** **%.2f kg**\n\n*Recuerda registrar el nombre del conductor, cédula, placa y destino en el módulo de Cosechas.*",
                    $gross,
                    $baskets,
                    $taraTotal,
                    $clean
                );
            }

            return <<<'MARKDOWN'
**Cálculo de Báscula y Tara de Cosecha:**

* **Tara estándar por canastilla:** **2.0 kg** (configurable por pesada).
* **Fórmula de Báscula:**
  $$\text{Peso Limpio Real} = \text{Peso Bruto Total} - (\text{Total Canastillas} \times \text{Tara por Canastilla})$$

**Ejemplo Práctico:**
Si en una sesión pesas **1.200 kg brutos** repartidos en **30 canastillas**:
* Tara total = $30 \times 2.0\text{ kg} = 60\text{ kg}$.
* Peso limpio real a despachar = $1.200 - 60 =$ **1.140 kg limpios**.

*Recuerda registrar los datos del conductor (nombre, cédula, placa, teléfono) y el destino al despachar.*
MARKDOWN;
        }

        if (str_contains($msg, 'nomina') || str_contains($msg, 'nómina') || str_contains($msg, 'sabado') || str_contains($msg, 'sábado') || str_contains($msg, 'liquidar') || str_contains($msg, 'fiado')) {
            return <<<'MARKDOWN'
**Reglas de Nómina y Liquidación del Sábado:**

1. **Personal Fijo:** Queda **automáticamente excluido** de la liquidación semanal del sábado, ya que devengan un salario fijo mensual independiente.
2. **Personal Temporal / Jornaleros** (pescadores, rayadores, lavado, empaque):
   * Se les acumulan los jornales trabajados durante la semana.
   * **Deducción de Pescado Fiado:** Se multiplican los kilos que llevaron en la semana por la tarifa interna de **$7.000/kg**.
   * **Fórmula:**
     $$\text{Neto a Pagar} = \text{Acumulado de Jornales} - (\text{Kilos Fiados} \times \$7.000)$$

*La fecha de corte y liquidación se ejecuta todos los sábados.*
MARKDOWN;
        }

        if (str_contains($msg, 'aliment') || str_contains($msg, 'raci') || str_contains($msg, 'concentrado') || str_contains($msg, 'estanque') || str_contains($msg, 'biomasa')) {
            return <<<'MARKDOWN'
**Alimentación y Cálculo de Raciones Diarias:**

* **Mojarra Roja / Tilapia:** La ración alimenticia recomendada varía entre el **1.8% y el 3.5% de la biomasa total estimada**, ajustada según la temperatura del agua y el nivel de oxígeno disuelto (> 4.0 mg/L).
* **Bloques de Alimentación:** Repartir en 3 a 4 raciones diarias (07:30 AM, 11:30 AM, 03:00 PM y 05:00 PM).
* **Control de Inventario:** Cada registro en la bitácora descuenta automáticamente los kilos aplicados del lote de alimento en bodega.
MARKDOWN;
        }

        if (str_contains($msg, 'rol') || str_contains($msg, 'jefe') || str_contains($msg, 'dueño') || str_contains($msg, 'admin')) {
            return <<<'MARKDOWN'
**Estructura de Roles en El SAS Piscícola:**

* **Jefe de Finca / Dueño (`owner` / `jefe_finca`):** Acceso total e irrestricto sobre administradores, fincas, balances financieros y configuración global.
* **Administrador (`admin`):** Gestión de estanques, órdenes de cosecha, pesaje en báscula, ventas de caja y nómina semanal.
* **Operario / Trabajador (`worker`):** Ejecución de turnos, raciones y bitácoras de alimentación de peces.
* **Celador / Guardia (`guard`):** Consulta de asignaciones y turnos de vigilancia en fines de semana y festivos.
MARKDOWN;
        }

        if (str_contains($msg, 'hora') || str_contains($msg, 'reloj') || str_contains($msg, 'tiempo')) {
            $now = now()->setTimezone('America/Bogota');

            return sprintf(
                "**Hora Oficial de la Granja (America/Bogota):** **%s** (%s)\n\n*Recuerda que todas las interfaces del ERP utilizan estrictamente el formato civil de 12 horas con AM/PM.*",
                $now->format('g:i:s A'),
                $now->locale('es')->dayName
            );
        }

        return <<<'MARKDOWN'
¡Hola! Soy el **Asistente Experto de El SAS Piscícola**.

Estoy a tu servicio en tiempo real para apoyarte con:
* **Báscula y Despacho:** Cálculo automático de kilos limpios descontando la tara de canastillas (2.0 kg/canastilla).
* **Tarifas de Pescado:** Precios diferenciados para visitantes ($9.000/kg) y trabajadores ($7.000/kg).
* **Liquidación de Sábados:** Jornales de apoyo y descuento automático de pescado fiado.
* **Alimentación y Estanques:** Porcentajes de ración diaria y biomasa.
* **Turnos y Agenda:** Programación operativa en formato 12 horas AM/PM.

¿En qué puedo ayudarte en este momento?
MARKDOWN;
    }
}
