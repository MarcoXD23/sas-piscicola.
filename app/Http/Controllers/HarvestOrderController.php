<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordDispatchRequest;
use App\Http\Requests\RecordGrossWeightRequest;
use App\Http\Requests\StoreHarvestOrderRequest;
use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\User;
use App\Notifications\HarvestScheduledNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class HarvestOrderController extends Controller
{
    /**
     * Listado de órdenes de cosecha y despacho.
     */
    public function index(Request $request): JsonResponse
    {
        $query = HarvestOrder::query()->with([
            'pond:id,name,biomass',
            'scheduledBy:id,name,role',
            'weighedBy:id,name,role',
            'dispatchedBy:id,name,role',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        $orders = $query->orderBy('scheduled_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Órdenes de cosecha recuperadas exitosamente.',
            'total' => $orders->count(),
            'data' => $orders,
        ]);
    }

    /**
     * Paso 1: El Jefe Mayor o de Finca programa en el calendario el estanque a pescar y la cantidad estimada,
     * enviando una notificación directa a los administradores de la finca.
     */
    public function store(StoreHarvestOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $pond = Pond::findOrFail($validated['pond_id']);
        $fechaProgramada = Carbon::parse($validated['scheduled_date']);

        if ($pond->estaEnTiempoRetiro($fechaProgramada)) {
            $fechaFin = $pond->fechaFinRetiroActivo($fechaProgramada);

            return response()->json([
                'message' => "EN TIEMPO DE RETIRO - Prohibida su cosecha hasta {$fechaFin}. El estanque se encuentra en período de carencia sanitaria bajo normativa ICA.",
                'error' => 'estanque_en_tiempo_retiro',
                'fecha_fin_retiro' => $fechaFin,
                'pond_id' => $pond->id,
            ], 422);
        }

        $harvestOrder = HarvestOrder::create([
            'pond_id' => $validated['pond_id'],
            'scheduled_by_user_id' => $user->id,
            'scheduled_date' => $validated['scheduled_date'],
            'estimated_kg' => $validated['estimated_kg'],
            'observations' => $validated['observations'] ?? null,
            'status' => HarvestOrder::STATUS_PROGRAMADA,
        ]);

        $harvestOrder->load(['pond:id,name', 'scheduledBy:id,name']);

        // Notificar a todos los administradores de la finca correspondiente
        $adminsQuery = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_ADMINISTRADOR]);
        if ($harvestOrder->finca_id) {
            $adminsQuery->where('finca_id', $harvestOrder->finca_id);
        }
        $admins = $adminsQuery->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new HarvestScheduledNotification($harvestOrder));
        }

        return response()->json([
            'message' => 'Orden de cosecha programada exitosamente y notificación enviada al Administrador.',
            'notificados_count' => $admins->count(),
            'data' => $harvestOrder,
        ], 201);
    }

    /**
     * Paso 2: El Administrador registra en su libreta digital los kilos brutos obtenidos en la báscula y la tara de canastillas:
     * Peso Neto = Peso Bruto - (Canastillas * Peso_Tara)
     */
    public function recordGrossWeight(RecordGrossWeightRequest $request, HarvestOrder $harvestOrder): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $grossWeight = (float) $validated['gross_weight_kg'];
        $basketsCount = isset($validated['baskets_count']) ? (int) $validated['baskets_count'] : (int) ($harvestOrder->baskets_count ?: 0);
        $defaultTare = (float) ($user?->finca?->obtenerConfig('operacion.peso_tara_canastilla_kg') ?? 2.0);
        $basketTareKg = isset($validated['basket_tare_kg']) ? (float) $validated['basket_tare_kg'] : (float) ($harvestOrder->basket_tare_kg ?: $defaultTare);

        $netWeight = $basketsCount > 0
            ? max(0.0, round($grossWeight - ($basketsCount * $basketTareKg), 2))
            : $grossWeight;

        $harvestOrder->update([
            'gross_weight_kg' => $grossWeight,
            'baskets_count' => $basketsCount > 0 ? $basketsCount : $harvestOrder->baskets_count,
            'basket_tare_kg' => $basketTareKg,
            'net_weight_kg' => $netWeight,
            'weighed_by_user_id' => $user->id,
            'weighed_at' => now(),
            'status' => HarvestOrder::STATUS_PESAJE,
            'observations' => $validated['observations'] ?? $harvestOrder->observations,
        ]);

        $harvestOrder->load(['pond:id,name', 'scheduledBy:id,name', 'weighedBy:id,name']);

        return response()->json([
            'message' => 'Pesaje bruto en báscula registrado en la libreta digital del Administrador.',
            'peso_bruto_kg' => $harvestOrder->gross_weight_kg,
            'canastillas' => $harvestOrder->baskets_count,
            'tara_canastilla_kg' => $harvestOrder->basket_tare_kg,
            'peso_neto_kg' => $harvestOrder->net_weight_kg,
            'diferencia_vs_estimado_kg' => round($harvestOrder->net_weight_kg - $harvestOrder->estimated_kg, 2),
            'data' => $harvestOrder,
        ]);
    }

    /**
     * Paso 3: Registro del proceso de limpieza, kilos limpios despachados, canastas, conductor, comprador y destino.
     */
    public function recordDispatch(RecordDispatchRequest $request, HarvestOrder $harvestOrder): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $harvestOrder->update([
            'clean_weight_kg' => $validated['clean_weight_kg'],
            'gross_weight_kg' => $validated['gross_weight_kg'] ?? $harvestOrder->gross_weight_kg,
            'total_tare_kg' => $validated['total_tare_kg'] ?? $harvestOrder->total_tare_kg,
            'baskets_count' => $validated['baskets_count'],
            'weighing_batches' => $validated['batches'] ?? $harvestOrder->weighing_batches,
            'driver_name' => $validated['driver_name'],
            'driver_id_card' => $validated['driver_id_card'] ?? null,
            'driver_vehicle_plate' => strtoupper($validated['driver_vehicle_plate']),
            'destination' => $validated['destination'],
            'buyer_name' => $validated['buyer_name'] ?? $harvestOrder->buyer_name ?? 'Comprador Mayorista',
            'dispatched_by_user_id' => $user->id,
            'dispatched_at' => now(),
            'status' => HarvestOrder::STATUS_DESPACHADA,
            'observations' => $validated['observations'] ?? $harvestOrder->observations,
        ]);

        $harvestOrder->load([
            'pond:id,name',
            'scheduledBy:id,name',
            'weighedBy:id,name',
            'dispatchedBy:id,name',
        ]);

        // Merma de eviscerado/limpieza: peso bruto - peso limpio
        $mermaKg = $harvestOrder->gross_weight_kg
            ? round((float) $harvestOrder->gross_weight_kg - (float) $harvestOrder->clean_weight_kg, 2)
            : 0.0;

        $rendimiento = ($harvestOrder->gross_weight_kg && $harvestOrder->gross_weight_kg > 0)
            ? round(((float) $harvestOrder->clean_weight_kg / (float) $harvestOrder->gross_weight_kg) * 100, 2)
            : 0.0;

        return response()->json([
            'message' => 'Despacho y proceso de limpieza registrados exitosamente.',
            'resumen_despacho' => [
                'kilos_brutos_pescados' => (float) $harvestOrder->gross_weight_kg,
                'peso_neto_kg' => (float) ($harvestOrder->net_weight_kg ?? $harvestOrder->gross_weight_kg),
                'kilos_limpios_despachados' => (float) $harvestOrder->clean_weight_kg,
                'merma_kg' => (float) $mermaKg,
                'rendimiento_limpieza_porcentaje' => (float) $rendimiento,
                'canastas_despachadas' => (int) $harvestOrder->baskets_count,
                'conductor' => $harvestOrder->driver_name,
                'placa' => $harvestOrder->driver_vehicle_plate,
                'comprador' => $harvestOrder->buyer_name,
                'destino' => $harvestOrder->destination,
            ],
            'data' => $harvestOrder,
        ]);
    }

    /**
     * Paso 3 / Registro directo: Proceso de pesaje por tandas, descuento de tara y remisión de despacho.
     */
    public function storeDispatchSession(RecordDispatchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $finca = $user->finca_segura;

        $pondId = $validated['pond_id'] ?? null;
        if (! $pondId) {
            $pond = Pond::where('finca_id', $finca->id)->where('status', 'Sembrado')->first()
                ?? Pond::where('finca_id', $finca->id)->first();
            $pondId = $pond?->id;
        }

        $grossKg = (float) ($validated['gross_weight_kg'] ?? 0);
        if ($grossKg <= 0 && ! empty($validated['batches'])) {
            $grossKg = (float) collect($validated['batches'])->sum('gross_kg');
        }

        $totalTare = (float) ($validated['total_tare_kg'] ?? 0);
        if ($totalTare <= 0 && ! empty($validated['batches'])) {
            $totalTare = (float) collect($validated['batches'])->sum('total_tare');
        }

        $basketsCount = (int) ($validated['baskets_count'] ?? 0);
        if ($basketsCount <= 0 && ! empty($validated['batches'])) {
            $basketsCount = (int) collect($validated['batches'])->sum('baskets');
        }

        $harvestOrder = HarvestOrder::create([
            'finca_id' => $finca->id,
            'pond_id' => $pondId,
            'scheduled_by_user_id' => $user->id,
            'scheduled_date' => now()->toDateString(),
            'estimated_kg' => (float) $validated['clean_weight_kg'],
            'gross_weight_kg' => $grossKg,
            'total_tare_kg' => $totalTare,
            'basket_tare_kg' => $basketsCount > 0 ? round($totalTare / $basketsCount, 2) : 2.0,
            'net_weight_kg' => max(0.0, round($grossKg - $totalTare, 2)),
            'clean_weight_kg' => (float) $validated['clean_weight_kg'],
            'baskets_count' => $basketsCount,
            'weighing_batches' => $validated['batches'] ?? null,
            'weighed_by_user_id' => $user->id,
            'weighed_at' => now(),
            'dispatched_by_user_id' => $user->id,
            'dispatched_at' => now(),
            'driver_name' => $validated['driver_name'],
            'driver_id_card' => $validated['driver_id_card'] ?? null,
            'driver_vehicle_plate' => strtoupper($validated['driver_vehicle_plate']),
            'destination' => $validated['destination'],
            'buyer_name' => $validated['buyer_name'] ?? 'Comprador Mayorista',
            'status' => HarvestOrder::STATUS_DESPACHADA,
            'observations' => $validated['observations'] ?? null,
        ]);

        $harvestOrder->load([
            'pond:id,name',
            'dispatchedBy:id,name',
        ]);

        return response()->json([
            'message' => '¡Despacho registrado con éxito con detalle de tandas y tara!',
            'resumen_despacho' => [
                'kilos_brutos_pescados' => (float) $harvestOrder->gross_weight_kg,
                'tara_total_descontada_kg' => (float) $harvestOrder->total_tare_kg,
                'kilos_limpios_despachados' => (float) $harvestOrder->clean_weight_kg,
                'canastas_despachadas' => (int) $harvestOrder->baskets_count,
                'conductor' => $harvestOrder->driver_name,
                'placa' => $harvestOrder->driver_vehicle_plate,
                'comprador' => $harvestOrder->buyer_name,
                'destino' => $harvestOrder->destination,
                'tandas_count' => count($validated['batches'] ?? []),
            ],
            'data' => $harvestOrder,
        ], 201);
    }

    /**
     * Detalle de una orden de cosecha específica.
     */
    public function show(HarvestOrder $harvestOrder): JsonResponse
    {
        $harvestOrder->load([
            'pond:id,name,biomass',
            'scheduledBy:id,name,role',
            'weighedBy:id,name,role',
            'dispatchedBy:id,name,role',
        ]);

        return response()->json([
            'data' => $harvestOrder,
            'rendimiento_limpieza' => $harvestOrder->cleaning_yield,
        ]);
    }
}
