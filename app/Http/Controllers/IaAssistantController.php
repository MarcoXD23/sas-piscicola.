<?php

namespace App\Http\Controllers;

use App\Models\AlimentoBodega;
use App\Models\Estanque;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IaAssistantController extends Controller
{
    /**
     * Procesa la consulta técnica del usuario interactuando con Google Gemini API
     * e inyectando el estado operativo en tiempo real de la finca (con motor de respaldo offline).
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:3000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model,assistant'],
            'history.*.text' => ['required_with:history', 'string'],
        ]);

        $message = trim($validated['message']);
        $history = $validated['history'] ?? [];
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        // 1. Inyección de Contexto en Tiempo Real desde la Base de Datos
        $contextoFinca = $this->obtenerContextoOperativo($fincaId);

        // 2. Intentar llamar a Google Gemini API si existe API Key
        $apiKey = env('GEMINI_API_KEY') ?: config('services.gemini.api_key');
        $model = env('GEMINI_MODEL') ?: config('services.gemini.model', 'gemini-1.5-flash');

        if (! empty($apiKey)) {
            try {
                $geminiResponse = $this->llamarGeminiApi($apiKey, $model, $message, $history, $contextoFinca);

                if ($geminiResponse) {
                    return response()->json([
                        'status' => 'success',
                        'reply' => $geminiResponse,
                        'model' => $model,
                        'is_simulated' => false,
                        'timestamp' => now()->setTimezone('America/Bogota')->format('g:i A'),
                    ]);
                }
            } catch (Exception $e) {
                Log::warning('Error comunicando con Google Gemini API, activando respaldo local: '.$e->getMessage());
            }
        }

        // 3. Modo de Respaldo Offline (Fallback Local Zootécnico Inteligente)
        $localReply = $this->generarRespuestaLocal($message, $contextoFinca);

        return response()->json([
            'status' => 'success',
            'reply' => $localReply,
            'model' => empty($apiKey) ? 'motor-zootecnico-local' : 'gemini-1.5-flash (respaldo local)',
            'is_simulated' => true,
            'timestamp' => now()->setTimezone('America/Bogota')->format('g:i A'),
        ]);
    }

    /**
     * Recopila el estado operativo actual de la finca para enriquecer el contexto.
     *
     * @return array<string, mixed>
     */
    protected function obtenerContextoOperativo(int $fincaId): array
    {
        $estanques = Estanque::where('finca_id', $fincaId)
            ->with('especiePrincipal')
            ->orderBy('code')
            ->get();

        $totalBiomasaKg = round((float) $estanques->sum('biomass'), 2);
        $totalPecesVivos = (int) $estanques->sum('fish_population');
        $totalAlevinosSembrados = (int) $estanques->sum('fingerlings_stocked');

        $alertasBodega = AlimentoBodega::where('finca_id', $fincaId)
            ->whereRaw('stock_bultos <= umbral_alerta_bultos')
            ->get();

        $lagosListosPesca = $estanques->filter(function ($e) {
            return (float) $e->average_weight >= 450 || $e->status === 'En Cosecha';
        });

        return [
            'estanques' => $estanques,
            'total_biomasa_kg' => $totalBiomasaKg,
            'total_peces_vivos' => $totalPecesVivos,
            'total_alevinos' => $totalAlevinosSembrados,
            'alertas_bodega' => $alertasBodega,
            'lagos_listos_cosecha' => $lagosListosPesca,
        ];
    }

    /**
     * Construye el System Prompt oficial enriquecido con los datos en tiempo real de la finca.
     *
     * @param  array<string, mixed>  $contexto
     */
    protected function construirSystemPrompt(array $contexto): string
    {
        $estanques = $contexto['estanques'];
        $lineasEstanques = [];

        foreach ($estanques as $e) {
            $especieNombre = $e->especiePrincipal->nombre_comun ?? 'Especie mixta';
            $pesoG = (float) $e->average_weight;
            $peces = (int) $e->fish_population;
            $bio = (float) $e->biomass;
            $estado = $e->status;
            $lineasEstanques[] = "- {$e->code} ({$e->name}): {$especieNombre} | {$peces} peces vivos | Peso prom: {$pesoG}g | Biomasa: {$bio} kg | Estado: {$estado} | Tipo: {$e->tipo_estanque}";
        }

        $resumenEstanques = ! empty($lineasEstanques)
            ? implode("\n", $lineasEstanques)
            : 'No hay estanques registrados actualmente.';

        $alertasBodega = $contexto['alertas_bodega'];
        $lineasBodega = [];
        foreach ($alertasBodega as $b) {
            $lineasBodega[] = "- {$b->nombre_concentrado}: Stock crítico de {$b->stock_bultos} bultos ({$b->stock_kilos_actual} kg).";
        }
        $resumenBodega = ! empty($lineasBodega)
            ? implode("\n", $lineasBodega)
            : 'Stock de bodega en niveles óptimos.';

        $biomasaTotal = $contexto['total_biomasa_kg'];
        $totalPeces = $contexto['total_peces_vivos'];

        return <<<PROMPT
Eres el Asistente Técnico Acuícola de 'El SAS Piscícola', ubicado en Tolima, Colombia. Asistes a operarios y administradores en el cultivo de Mojarra Roja, Cachama Blanca y Bagre. Respondes con rigor técnico pero lenguaje claro y directo: rangos de oxígeno disuelto (alerta < 3.5 mg/L), tablas de porcentaje de peso vivo según biomasa, dosificación de alimento balanceado, encendido de aireadores en madrugada y sanidad piscícola (normas ICA). Tienes acceso a los datos actuales de los estanques de la finca para responder consultas específicas.

--- ESTADO EN TIEMPO REAL DE LA FINCA (DATOS ACTUALES DEL SISTEMA) ---
- Biomasa Total Activa en Finca: {$biomasaTotal} kg
- Población Total de Peces Vivos: {$totalPeces} ejemplares
- Total de Estanques Activos: {$estanques->count()}
- Detalle Actual de Cada Estanque:
{$resumenEstanques}
- Alertas de Concentrado en Bodega:
{$resumenBodega}
----------------------------------------------------------------------

Directivas de Respuesta:
1. Responde en español profesional, conciso y técnico pero accesible.
2. Si te preguntan por un lago o estanque en particular, revisa y cita los datos numéricos exactos del contexto anterior.
3. Alerta siempre si los valores de oxígeno disuelto son menores a 3.5 mg/L, recomendando encendido inmediato de aireadores y suspensión de alimento.
4. Usa formato Markdown con viñetas limpias y negritas en conceptos clave.
5. Evita saludos largos y ve directo a la solución o explicación técnica.
PROMPT;
    }

    /**
     * Ejecuta la llamada HTTP a Google Gemini API (gemini-1.5-flash).
     *
     * @param  array<int, array{role: string, text: string}>  $history
     * @param  array<string, mixed>  $contexto
     */
    protected function llamarGeminiApi(string $apiKey, string $model, string $message, array $history, array $contexto): ?string
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        $systemPrompt = $this->construirSystemPrompt($contexto);

        $contents = [];
        foreach ($history as $h) {
            $role = match ($h['role'] ?? '') {
                'model', 'assistant' => 'model',
                default => 'user',
            };
            if (! empty($h['text'])) {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $h['text']]],
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $response = Http::connectTimeout(4)
            ->timeout(20)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, [
                'system_instruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 1000,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        Log::warning('Respuesta no exitosa de Gemini API: '.$response->status().' - '.$response->body());

        return null;
    }

    /**
     * Motor de Reglas Zootécnicas Locales (Modo de Respaldo Offline).
     * Proporciona respuestas técnicas precisas basadas en los datos de la base de datos.
     *
     * @param  array<string, mixed>  $contexto
     */
    protected function generarRespuestaLocal(string $message, array $contexto): string
    {
        $normalized = mb_strtolower($message, 'UTF-8');
        $estanques = $contexto['estanques'];
        $totalBiomasa = $contexto['total_biomasa_kg'];
        $lagosListos = $contexto['lagos_listos_cosecha'];

        // Regla 1: Protocolo de Caída de Oxígeno Disuelto (< 3.0 o < 3.5 mg/L)
        if (str_contains($normalized, 'oxigeno') || str_contains($normalized, 'oxígeno') || str_contains($normalized, '3.0') || str_contains($normalized, '3.5') || str_contains($normalized, 'boqueo') || str_contains($normalized, 'asfixia')) {
            return <<<'MARKDOWN'
**PROTOCOLO DE EMERGENCIA ZOOTÉCNICA: CAÍDA DE OXÍGENO DISUELTO (< 3.5 mg/L)**

1. **Encendido Inmediato de Aireadores:**
   - Activar al 100% de potencia los aireadores mecánicos (paletas y splash) en los estanques afectados.
   - En estanques de tierra, priorizar zonas de mayor biomasa y corrientes de fondo.

2. **Suspensión Total de la Alimentación:**
   - **No alimentar.** La digestión incrementa el consumo metabólico de oxígeno hasta en un 40% (calor específico dinámico), lo que provocaría mortalidad masiva.

3. **Recambio Hídrico de Emergencia:**
   - Abrir compuertas de entrada de agua fresca proveniente del canal principal para generar flujo y renovación de la columna de agua.

4. **Monitoreo Continuo:**
   - Tomar lectura con oxímetro cada 30 minutos hasta estabilizar por encima de **4.5 mg/L**.
   - El umbral crítico de peligro letal para Mojarra Roja y Cachama es menor a **2.0 mg/L**.
MARKDOWN;
        }

        // Regla 2: Cálculo de Ración Diaria de Alimento Balanceado
        if (str_contains($normalized, 'racion') || str_contains($normalized, 'ración') || str_contains($normalized, 'calcular') || str_contains($normalized, 'dosific') || str_contains($normalized, 'porcentaje') || str_contains($normalized, 'cuanto alimento') || str_contains($normalized, 'cuánto alimento')) {
            return <<<MARKDOWN
**GUÍA ZOOTÉCNICA DE ALIMENTACIÓN DIARIA (EL SAS PISCÍCOLA)**

La dosificación diaria se calcula aplicando la tasa de alimentación sobre la **biomasa activa total** (actualmente registrada en **{$totalBiomasa} kg** en la finca):

1. **Tabla de Porcentaje de Peso Vivo (% PV) según Etapa:**
   - **Iniciación / Alevinaje (1g a 20g):** 8.0% al 10.0% del peso vivo (Concentrado 45% PB, suministrado en 5 a 6 raciones/día).
   - **Levante Comercial (20g a 150g):** 3.5% al 5.0% del peso vivo (Concentrado 34% - 38% PB, en 3 a 4 raciones/día).
   - **Engorde (150g a 350g):** 2.0% al 3.0% del peso vivo (Concentrado 32% PB, en 2 a 3 raciones/día).
   - **Acabado / Finalización (> 350g):** 1.5% al 1.8% del peso vivo (Concentrado 28% - 30% PB, en 2 raciones/día).

2. **Fórmula Estándar:**
   $$\\text{Ración Diaria (kg)} = \\frac{\\text{Biomasa Estanque (kg)} \\times \\% \\text{ PV}}{100}$$

3. **Criterios de Suspensión:**
   - Temperatura del agua < 24°C o > 32°C: Reducir la ración en un 30% - 50%.
   - Oxígeno disuelto matutino < 3.5 mg/L: **Suspender la primera ración.**
MARKDOWN;
        }

        // Regla 3: Resumen de Lagos Listos para Cosecha
        if (str_contains($normalized, 'cosecha') || str_contains($normalized, 'listos') || str_contains($normalized, 'pesca') || str_contains($normalized, 'sacar') || str_contains($normalized, 'comercial')) {
            if ($lagosListos->isNotEmpty()) {
                $items = [];
                $kilosTotal = 0;
                foreach ($lagosListos as $lago) {
                    $especie = $lago->especiePrincipal->nombre_comun ?? 'Peces';
                    $items[] = "- **{$lago->code} ({$lago->name}):** {$especie} | {$lago->fish_population} ejemplares | Peso promedio: **{$lago->average_weight}g** | Biomasa estimada: **{$lago->biomass} kg**.";
                    $kilosTotal += (float) $lago->biomass;
                }
                $detalleLagos = implode("\n", $items);

                return <<<MARKDOWN
**ESTANQUES LISTOS PARA COSECHA Y COMERCIALIZACIÓN**

Los siguientes estanques han alcanzado el peso comercial mínimo de cosecha (>= 450 gramos) o tienen orden de cosecha activa:

{$detalleLagos}

- **Volumen Total Proyectado para Pesaje:** **{$kilosTotal} kg**.
- **Acción Operativa Requerida:** Verificar el cumplimiento de tiempos de retiro sanitario (norma ICA) antes de emitir la orden de báscula en el módulo comercial.
MARKDOWN;
            } else {
                return <<<MARKDOWN
**ESTADO DE COSECHA:**
Actualmente ningún estanque ha superado el peso objetivo comercial de 450 gramos.
- La biomasa total en cultivo es de **{$totalBiomasa} kg**.
- Puedes consultar el progreso de crecimiento y las biometrías sabatinas en el módulo de **Lagos y Muestreos**.
MARKDOWN;
            }
        }

        // Regla 4: Sanidad Piscícola, Tiempos de Retiro y Normas ICA
        if (str_contains($normalized, 'sanidad') || str_contains($normalized, 'retiro') || str_contains($normalized, 'ica') || str_contains($normalized, 'enfermedad') || str_contains($normalized, 'hongo') || str_contains($normalized, 'bacteria') || str_contains($normalized, 'mortalidad')) {
            return <<<'MARKDOWN'
**PROTOCOLO DE SANIDAD PISCÍCOLA Y CUMPLIMIENTO ICA (BPAP)**

1. **Tiempo de Retiro Sanitario:**
   - Ningún lote tratado con medicamentos veterinarios, antibióticos o químicos antiparasitarios puede cosecharse antes de cumplir el tiempo de retiro estipulado en la ficha técnica (generalmente entre 15 y 30 días según el principio activo).
   - El sistema bloquea automáticamente la cosecha en báscula de estanques con retiro vigente.

2. **Detección de Anomalías o Mortalidad:**
   - **Aislamiento:** Restringir el flujo de agua hacia otros estanques para evitar contaminación cruzada.
   - **Muestreo:** Extraer 5 a 10 ejemplares con signos clínicos (lesiones en piel, branquias pálidas, nado errático) para necropsia técnica.
   - **Registro:** Asentar el evento inmediatamente en el **Libro de Campo Oficial del ICA**.
MARKDOWN;
        }

        // Regla 5: Densidades de Siembra y Desdobles
        if (str_contains($normalized, 'densidad') || str_contains($normalized, 'desdoble') || str_contains($normalized, 'traslado') || str_contains($normalized, 'siembra')) {
            return <<<'MARKDOWN'
**DENSIDADES Y DESDOBLES TÉCNICOS EN EL TOLIMA**

1. **Densidades Máximas por Tipo de Estanque:**
   - **Estanques en Tierra (flujo natural):** 3 a 5 peces/m² (Biomasa máxima 2.5 kg/m²).
   - **Estanques en Geomembrana (con aireación continua):** 20 a 30 peces/m³ (Biomasa máxima 12 a 15 kg/m³).

2. **Criterio de Desdoble:**
   - Cuando la biomasa supera el 80% de la capacidad de soporte del estanque o el peso promedio supera los 150g, se debe realizar un **Desdoble** hacia un estanque receptor previamente preparado y encalado.
   - Registra el movimiento en el módulo de **Desdobles & Traslados** para mantener las biomasas actualizadas automáticamente.
MARKDOWN;
        }

        // Respuesta General / Resumen Técnico Operativo
        return <<<MARKDOWN
**ESTADO TÉCNICO GENERAL - EL SAS PISCÍCOLA**

- **Biomasa Activa Total:** **{$totalBiomasa} kg** en **{$estanques->count()} estanques**.
- **Población Total:** **{$contexto['total_peces_vivos']} peces vivos**.

¿En qué tema específico deseas profundizar?
- **Oxígeno y Calidad de Agua:** Protocolos para valores menores a 3.5 mg/L y manejo de aireadores.
- **Nutrición Acuícola:** Tablas de alimentación según biomasa y proteína recomendada.
- **Planificación de Cosecha:** Estanques listos para venta y tiempos de retiro ICA.
MARKDOWN;
    }
}
