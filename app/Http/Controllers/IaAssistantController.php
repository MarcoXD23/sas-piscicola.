<?php

namespace App\Http\Controllers;

use App\Models\AlimentoBodega;
use App\Models\Estanque;
use App\Models\InventarioAlimento;
use App\Models\TratamientoSanitario;
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
        $fincaId = (int) ($user?->finca_id ?? 1);

        // 1. Inyección de Contexto en Tiempo Real desde la Base de Datos de la Finca
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
     * Recopila el estado operativo actual de la finca en tiempo real para enriquecer el contexto.
     *
     * @return array<string, mixed>
     */
    protected function obtenerContextoOperativo(int $fincaId): array
    {
        $estanques = Estanque::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'especiesDetalle.especie'])
            ->orderBy('code')
            ->get();

        $totalBiomasaKg = round((float) $estanques->sum('biomass'), 2);
        $totalPecesVivos = (int) $estanques->sum('fish_population');
        $totalAlevinosSembrados = (int) $estanques->sum('fingerlings_stocked');

        // Alimentos en bodega (alimentos_bodega e inventario_alimento)
        $alimentosBodega = AlimentoBodega::where('finca_id', $fincaId)->get();
        $inventarioAlimento = InventarioAlimento::where('finca_id', $fincaId)->get();

        $totalStockKilos = round((float) $alimentosBodega->sum('stock_kilos_actual') + (float) $inventarioAlimento->sum('stock_actual_kg'), 2);
        $totalStockBultos = round((float) $alimentosBodega->sum('stock_bultos'), 2);

        $alertasBodega = $alimentosBodega->filter(function ($b) {
            return (float) $b->stock_bultos <= (float) $b->umbral_alerta_bultos;
        });

        // Alertas sanitarias: tratamientos con tiempo de retiro vigente (ICA)
        $hoy = now()->toDateString();
        $alertasSanitarias = TratamientoSanitario::where('finca_id', $fincaId)
            ->where(function ($q) use ($hoy) {
                $q->where('fecha_habil_cosecha', '>=', $hoy)
                    ->orWhere('fecha_fin_retiro', '>=', $hoy);
            })
            ->with('estanque')
            ->get();

        $lagosListosPesca = $estanques->filter(function ($e) {
            return (float) $e->average_weight >= 450 || $e->status === 'En Cosecha';
        });

        return [
            'finca_id' => $fincaId,
            'estanques' => $estanques,
            'total_estanques' => $estanques->count(),
            'total_biomasa_kg' => $totalBiomasaKg,
            'total_peces_vivos' => $totalPecesVivos,
            'total_alevinos' => $totalAlevinosSembrados,
            'alimentos_bodega' => $alimentosBodega,
            'inventario_alimento' => $inventarioAlimento,
            'total_stock_kilos' => $totalStockKilos,
            'total_stock_bultos' => $totalStockBultos,
            'alertas_bodega' => $alertasBodega,
            'alertas_sanitarias' => $alertasSanitarias,
            'lagos_listos_cosecha' => $lagosListosPesca,
        ];
    }

    /**
     * Construye el System Prompt oficial enriquecido con datos en tiempo real y base zootécnica Tolima.
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

        // Bodega
        $alimentosBodega = $contexto['alimentos_bodega'];
        $lineasBodega = [];
        foreach ($alimentosBodega as $b) {
            $alerta = ((float) $b->stock_bultos <= (float) $b->umbral_alerta_bultos) ? ' [CRÍTICO]' : '';
            $lineasBodega[] = "- {$b->nombre_concentrado} ({$b->proteina_porcentaje}% PB): {$b->stock_bultos} bultos ({$b->stock_kilos_actual} kg){$alerta}";
        }
        foreach ($contexto['inventario_alimento'] as $inv) {
            $lineasBodega[] = "- {$inv->tipo_concentrado} ({$inv->proteina_porcentaje}% PB): {$inv->stock_actual_kg} kg";
        }
        $resumenBodega = ! empty($lineasBodega)
            ? implode("\n", $lineasBodega)
            : 'Sin alimento registrado en bodega.';

        // Alertas Sanitarias
        $alertasSanitarias = $contexto['alertas_sanitarias'];
        $lineasSanitarias = [];
        foreach ($alertasSanitarias as $s) {
            $lagoCode = $s->estanque->code ?? "ID {$s->estanque_id}";
            $lineasSanitarias[] = "- Estanque {$lagoCode}: Tratamiento con {$s->producto} ({$s->principio_activo}). Retiro vigente hasta {$s->fecha_habil_cosecha}. COSECHA BLOQUEADA.";
        }
        $resumenSanitario = ! empty($lineasSanitarias)
            ? implode("\n", $lineasSanitarias)
            : 'Sin tratamientos sanitarios en periodo de retiro activo.';

        $biomasaTotal = $contexto['total_biomasa_kg'];
        $totalPeces = $contexto['total_peces_vivos'];
        $totalEstanques = $contexto['total_estanques'];
        $totalKilosBodega = $contexto['total_stock_kilos'];
        $totalBultosBodega = $contexto['total_stock_bultos'];

        return <<<PROMPT
Eres el Asistente Técnico Acuícola de 'El SAS Piscícola', ubicado en Tolima, Colombia. Asistes a operarios, administradores y técnicos en el cultivo intensivo y semi-intensivo de Mojarra Roja (Oreochromis sp.), Cachama Blanca (Piaractus brachypomus), Bocachico (Prochilodus magdalenae) y Bagre Rayado (Pseudoplatystoma fasciatum).

--- CRITERIOS TÉCNICOS Y ZOOTÉCNICOS OBLIGATORIOS (COLOMBIA / TOLIMA) ---
1. Calidad de Agua:
   - Oxígeno Disuelto Crítico: Alerta inmediata si O₂ < 3.5 mg/L. Indicar encendido urgente de aireadores mecánicos (especialmente en la madrugada: 01:00 AM - 06:00 AM) y suspender inmediatamente la primera ración de alimento. Letal si < 2.0 mg/L.
   - Temperatura Óptima: 26°C a 30°C. Si temperatura < 24°C o > 32°C, reducir ración en un 30% a 50%.
   - pH Óptimo: 6.5 a 8.5.
2. Nutrición y Raciones (% Peso Vivo - PV):
   - Iniciación / Alevinaje (1g a 20g): 8.0% - 10.0% PV (Concentrado 45% PB, 5-6 raciones/día).
   - Levante Comercial (20g a 150g): 3.5% - 5.0% PV (Concentrado 34% - 38% PB, 3-4 raciones/día).
   - Engorde (150g a 350g): 2.0% - 3.0% PV (Concentrado 32% PB, 2-3 raciones/día).
   - Acabado / Finalización (> 350g): 1.5% - 1.8% PV (Concentrado 28% - 30% PB, 2 raciones/día).
   - Criterio de Suspensión: Suspender primera ración si O₂ < 3.5 mg/L. Reducir 30%-50% si temperatura < 24°C o > 32°C.
3. Sanidad y Cosecha (Normativa ICA):
   - Tiempos de Retiro: Prohibida la cosecha de lotes bajo tratamiento veterinario hasta cumplir el periodo de carencia fijado por el ICA.
   - Peso comercial objetivo para cosecha: ≥ 450 gramos.

--- ESTADO EN TIEMPO REAL DE LA FINCA (DATOS ACTUALES DEL SISTEMA) ---
- Biomasa Total Activa en Finca: {$biomasaTotal} kg
- Censo Total de Peces Vivos: {$totalPeces} ejemplares
- Total de Estanques Activos: {$totalEstanques}
- Stock en Bodega: {$totalKilosBodega} kg ({$totalBultosBodega} bultos)
- Detalle de Estanques:
{$resumenEstanques}
- Inventario en Bodega:
{$resumenBodega}
- Alertas Sanitarias (Retiro ICA):
{$resumenSanitario}
----------------------------------------------------------------------

Directivas de Respuesta:
1. Responde en español profesional, conciso y técnico pero accesible.
2. Si te preguntan por un lago en particular, el censo total de peces o el stock de comida, cita los datos numéricos exactos del contexto anterior.
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
     * Proporciona respuestas técnicas precisas basadas en los datos en tiempo real de la base de datos.
     *
     * @param  array<string, mixed>  $contexto
     */
    protected function generarRespuestaLocal(string $message, array $contexto): string
    {
        $normalized = mb_strtolower($message, 'UTF-8');
        $estanques = $contexto['estanques'];
        $totalBiomasa = $contexto['total_biomasa_kg'];
        $totalPeces = $contexto['total_peces_vivos'];
        $totalEstanques = $contexto['total_estanques'];
        $lagosListos = $contexto['lagos_listos_cosecha'];
        $totalStockKilos = $contexto['total_stock_kilos'];
        $totalStockBultos = $contexto['total_stock_bultos'];
        $alimentosBodega = $contexto['alimentos_bodega'];

        // Regla 1: Protocolo de Caída de Oxígeno Disuelto (< 3.5 mg/L) y Calidad de Agua
        if (str_contains($normalized, 'oxigeno') || str_contains($normalized, 'oxígeno') || str_contains($normalized, '3.5') || str_contains($normalized, '3.0') || str_contains($normalized, 'boqueo') || str_contains($normalized, 'asfixia') || str_contains($normalized, 'aireador') || str_contains($normalized, 'aireadores') || str_contains($normalized, 'calidad de agua') || str_contains($normalized, 'ph') || str_contains($normalized, 'temperatura')) {
            return <<<'MARKDOWN'
**PROTOCOLO DE EMERGENCIA ZOOTÉCNICA: OXÍGENO DISUELTO (< 3.5 mg/L) Y CALIDAD DE AGUA**

1. **Oxígeno Disuelto Crítico (< 3.5 mg/L):**
   - **Encendido Urgente de Aireadores:** Activar al 100% de potencia los aireadores mecánicos (paletas y splash), especialmente en la madrugada (01:00 AM - 06:00 AM) cuando la fotosíntesis cesa y la respiración algal consume el oxígeno.
   - **Suspensión Total de la Alimentación:** **No alimentar.** La digestión incrementa el consumo metabólico de oxígeno hasta en un 40% (calor específico dinámico), provocando asfixia y mortalidad masiva.
   - **Recambio Hídrico:** Abrir compuertas de entrada de agua fresca del canal principal para renovación de la columna de agua.
   - Monitorear cada 30 minutos con oxímetro hasta superar **4.5 mg/L**. Peligro letal si cae por debajo de **2.0 mg/L**.

2. **Parámetros Óptimos de Calidad de Agua (Tolima):**
   - **Temperatura:** Óptima entre **26°C y 30°C** para Mojarra Roja, Cachama Blanca, Bocachico y Bagre. Si la temperatura es menor a 24°C o mayor a 32°C, reducir la ración entre 30% y 50%.
   - **pH:** Rango óptimo entre **6.5 y 8.5**.
   - **Transparencia (Disco Secchi):** 25 a 35 cm.
MARKDOWN;
        }

        // Regla 2: Inventario Disponible en Bodega / Comida
        if (str_contains($normalized, 'bodega') || str_contains($normalized, 'comida') || str_contains($normalized, 'alimento') || str_contains($normalized, 'concentrado') || str_contains($normalized, 'inventario') || str_contains($normalized, 'stock') || str_contains($normalized, 'bultos')) {
            $itemsBodega = [];
            foreach ($alimentosBodega as $b) {
                $alerta = ((float) $b->stock_bultos <= (float) $b->umbral_alerta_bultos) ? ' ⚠️ *(Stock Crítico)*' : '';
                $itemsBodega[] = "- **{$b->nombre_concentrado} ({$b->proteina_porcentaje}% PB):** {$b->stock_bultos} bultos ({$b->stock_kilos_actual} kg){$alerta}";
            }

            foreach ($contexto['inventario_alimento'] as $inv) {
                $itemsBodega[] = "- **{$inv->tipo_concentrado} ({$inv->proteina_porcentaje}% PB):** {$inv->stock_actual_kg} kg";
            }

            $detalleBodega = ! empty($itemsBodega) ? implode("\n", $itemsBodega) : 'No hay concentrado registrado en bodega.';

            $alertasCriticas = $contexto['alertas_bodega'];
            $alertaTexto = $alertasCriticas->isNotEmpty()
                ? "\n\n⚠️ **Alerta de Reorden:** Hay {$alertasCriticas->count()} producto(s) por debajo del umbral mínimo de seguridad. Solicitar pedido urgente a proveedor."
                : "\n\n✅ **Estado de Suministro:** Todos los concentrados están en niveles de stock adecuados.";

            return <<<MARKDOWN
**INVENTARIO DISPONIBLE EN BODEGA DE ALIMENTOS**

- **Stock Total en Bodega:** **{$totalStockKilos} kg** (**{$totalStockBultos} bultos**).
- **Detalle por Tipo de Concentrado:**
{$detalleBodega}{$alertaTexto}
MARKDOWN;
        }

        // Regla 3: Consulta de Estanque Específico
        foreach ($estanques as $estanque) {
            $codeMatch = str_contains($normalized, mb_strtolower($estanque->code, 'UTF-8'));
            $nameMatch = str_contains($normalized, mb_strtolower($estanque->name, 'UTF-8'));
            if ($codeMatch || $nameMatch) {
                $especieNombre = $estanque->especiePrincipal->nombre_comun ?? 'Especie Mixta';
                $pesoG = (float) $estanque->average_weight;
                $peces = (int) $estanque->fish_population;
                $bio = (float) $estanque->biomass;

                return <<<MARKDOWN
**ESTADO EN TIEMPO REAL: {$estanque->code} ({$estanque->name})**

- **Especie Principal:** {$especieNombre}
- **Población Actual:** **{$peces} peces vivos**
- **Peso Promedio:** **{$pesoG} g**
- **Biomasa Estimada:** **{$bio} kg**
- **Tipo de Estanque:** {$estanque->tipo_estanque}
- **Estado Operativo:** {$estanque->status}
- **Recomendación Técnica:** Mantener monitoreo de oxígeno matutino y calibrar ración diaria según biomasa activa.
MARKDOWN;
            }
        }

        // Regla 4: Censo Total de Peces Vivos
        if (str_contains($normalized, 'cuantos peces') || str_contains($normalized, 'cuántos peces') || str_contains($normalized, 'censo') || str_contains($normalized, 'poblacion') || str_contains($normalized, 'población')) {
            $itemsPeces = [];
            foreach ($estanques as $e) {
                $especie = $e->especiePrincipal->nombre_comun ?? 'Mixta';
                $itemsPeces[] = "- **{$e->code} ({$e->name}):** {$e->fish_population} peces vivos ({$especie}) | Biomasa: {$e->biomass} kg";
            }
            $detallePeces = ! empty($itemsPeces) ? implode("\n", $itemsPeces) : 'Sin estanques activos.';

            return <<<MARKDOWN
**CENSO DE POBLACIÓN DE PECES - EL SAS PISCÍCOLA**

- **Población Total en Finca:** **{$totalPeces} ejemplares vivos**.
- **Biomasa Total Acumulada:** **{$totalBiomasa} kg** en **{$totalEstanques} estanques**.
- **Distribución por Estanque:**
{$detallePeces}
MARKDOWN;
        }

        // Regla 5: Cálculo de Ración Diaria de Alimento Balanceado (% PV)
        if (str_contains($normalized, 'racion') || str_contains($normalized, 'ración') || str_contains($normalized, 'calcular') || str_contains($normalized, 'dosific') || str_contains($normalized, 'porcentaje') || str_contains($normalized, 'peso vivo') || str_contains($normalized, '% pv') || str_contains($normalized, 'cuanto alimento') || str_contains($normalized, 'cuánto alimento')) {
            return <<<MARKDOWN
**GUÍA ZOOTÉCNICA DE ALIMENTACIÓN DIARIA (EL SAS PISCÍCOLA)**

La dosificación diaria se calcula aplicando la tasa de alimentación sobre la **biomasa activa total** (actualmente registrada en **{$totalBiomasa} kg** en la finca):

1. **Tabla de Porcentaje de Peso Vivo (% PV) según Etapa:**
   - **Iniciación / Alevinaje (1g a 20g):** **8.0% al 10.0% PV** (Concentrado 45% PB, suministrado en 5 a 6 raciones/día).
   - **Levante Comercial (20g a 150g):** **3.5% al 5.0% PV** (Concentrado 34% - 38% PB, en 3 a 4 raciones/día).
   - **Engorde (150g a 350g):** **2.0% al 3.0% PV** (Concentrado 32% PB, en 2 a 3 raciones/día).
   - **Acabado / Finalización (> 350g):** **1.5% al 1.8% PV** (Concentrado 28% - 30% PB, en 2 raciones/día).

2. **Fórmula Estándar:**
   $$\\text{Ración Diaria (kg)} = \\frac{\\text{Biomasa Estanque (kg)} \\times \\% \\text{ PV}}{100}$$

3. **Criterios Obligatorios de Suspensión:**
   - **Oxígeno Disuelto < 3.5 mg/L:** **Suspender la primera ración.**
   - **Temperatura < 24°C o > 32°C:** Reducir la ración diaria entre un 30% y 50%.
MARKDOWN;
        }

        // Regla 6: Resumen de Biomasa y Lagos Hoy
        if (str_contains($normalized, 'biomasa') || str_contains($normalized, 'lagos hoy') || str_contains($normalized, 'estanques') || str_contains($normalized, 'como esta') || str_contains($normalized, 'cómo está')) {
            $itemsEstanques = [];
            foreach ($estanques as $e) {
                $especie = $e->especiePrincipal->nombre_comun ?? 'Peces';
                $itemsEstanques[] = "- **{$e->code} ({$e->name}):** {$especie} | {$e->fish_population} peces | Peso: {$e->average_weight}g | Biomasa: **{$e->biomass} kg** | Estado: {$e->status}";
            }
            $detalleEstanques = ! empty($itemsEstanques) ? implode("\n", $itemsEstanques) : 'No hay estanques registrados.';

            return <<<MARKDOWN
**REPORTE DE BIOMASA Y ESTADO DE LAGOS HOY**

- **Biomasa Total Activa:** **{$totalBiomasa} kg**.
- **Población Total:** **{$totalPeces} peces vivos**.
- **Estanques Activos:** **{$totalEstanques} estanques**.

**Detalle Operativo por Estanque:**
{$detalleEstanques}
MARKDOWN;
        }

        // Regla 7: Resumen de Lagos Listos para Cosecha
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
- Puedes consultar el progreso de crecimiento y las biometrías en el módulo de **Lagos y Muestreos**.
MARKDOWN;
            }
        }

        // Regla 8: Sanidad Piscícola, Tiempos de Retiro y Normas ICA
        if (str_contains($normalized, 'sanidad') || str_contains($normalized, 'retiro') || str_contains($normalized, 'ica') || str_contains($normalized, 'enfermedad') || str_contains($normalized, 'hongo') || str_contains($normalized, 'bacteria') || str_contains($normalized, 'mortalidad') || str_contains($normalized, 'tratamiento')) {
            $alertasSanitarias = $contexto['alertas_sanitarias'];
            $detalleAlertas = '';
            if ($alertasSanitarias->isNotEmpty()) {
                $lineas = [];
                foreach ($alertasSanitarias as $t) {
                    $lagoCode = $t->estanque->code ?? "Estanque #{$t->estanque_id}";
                    $lineas[] = "- ⚠️ **{$lagoCode}:** Retiro activo por {$t->producto} ({$t->principio_activo}). Fecha hábil de cosecha: **{$t->fecha_habil_cosecha}**.";
                }
                $detalleAlertas = "\n\n**Tratamientos Activos en Finca:**\n".implode("\n", $lineas);
            }

            return <<<MARKDOWN
**PROTOCOLO DE SANIDAD PISCÍCOLA Y CUMPLIMIENTO ICA (BPAP)**

1. **Tiempo de Retiro Sanitario:**
   - Ningún lote tratado con medicamentos veterinarios, antibióticos o antiparasitarios puede cosecharse antes de cumplir el tiempo de retiro estipulado en la ficha técnica del producto.
   - El sistema bloquea automáticamente la cosecha en báscula de estanques con retiro vigente.

2. **Detección de Anomalías o Mortalidad:**
   - **Aislamiento:** Restringir el flujo de agua hacia otros estanques para evitar contaminación cruzada.
   - **Muestreo:** Extraer ejemplares con signos clínicos (lesiones en piel, branquias pálidas, nado errático) para necropsia técnica.
   - **Registro:** Asentar el evento inmediatamente en el **Libro de Campo Oficial del ICA**.{$detalleAlertas}
MARKDOWN;
        }

        // Respuesta General / Resumen Técnico Operativo
        return <<<MARKDOWN
**ESTADO TÉCNICO GENERAL - EL SAS PISCÍCOLA**

- **Biomasa Activa Total:** **{$totalBiomasa} kg** en **{$totalEstanques} estanques**.
- **Población Total:** **{$totalPeces} peces vivos**.
- **Stock en Bodega:** **{$totalStockKilos} kg** ({$totalStockBultos} bultos).

¿En qué tema específico deseas profundizar?
- **Oxígeno y Calidad de Agua:** Protocolos para valores menores a 3.5 mg/L y manejo de aireadores.
- **Nutrición Acuícola:** Tablas de alimentación según biomasa y porcentaje de peso vivo (% PV).
- **Inventario de Alimento:** Stock actual disponible y alertas en bodega.
- **Planificación de Cosecha:** Estanques listos para venta y tiempos de retiro ICA.
MARKDOWN;
    }
}

