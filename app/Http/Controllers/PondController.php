<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyRationRequest;
use App\Http\Requests\CalculateRationRequest;
use App\Http\Requests\StorePondRequest;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\WorkSchedule;
use App\Services\RationCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PondController extends Controller
{
    public function __construct(
        protected RationCalculatorService $rationCalculator
    ) {}

    /**
     * Registra un nuevo estanque y calcula su biomasa inicial.
     */
    public function store(StorePondRequest $request): JsonResponse
    {
        $pond = Pond::create($request->validated());

        // Calculamos la biomasa inicial tras crearlo
        $pond->updateBiomass();

        return response()->json([
            'message' => 'Estanque registrado exitosamente.',
            'data' => $pond,
        ], 201);
    }

    /**
     * Calcula la ración diaria mediante el servicio especializado (RationCalculatorService),
     * con opción de previsualizar disponibilidad de stock si se envía feed_inventory_id.
     */
    public function calculateRation(CalculateRationRequest $request, Pond $pond): JsonResponse
    {
        $customRate = $request->filled('feeding_rate') ? (float) $request->input('feeding_rate') : null;

        // Cálculo inteligente de biomasa y ración usando el Servicio
        $calculation = $this->rationCalculator->calculateForPond($pond, $customRate);

        $response = [
            'message' => 'Ración calculada exitosamente.',
            'pond_id' => $calculation['pond_id'],
            'pond_name' => $calculation['pond_name'],
            'fish_population' => $calculation['fish_population'],
            'average_weight_g' => $calculation['average_weight_g'],
            'biomass_kg' => $calculation['biomass_kg'],
            'feeding_rate_percentage' => $calculation['feeding_rate_percentage'],
            'is_suggested_rate' => $calculation['is_suggested_rate'],
            'daily_ration_kg' => $calculation['daily_ration_kg'],
            'suggested_daily_portions' => $calculation['suggested_daily_portions'],
            'ration_per_portion_kg' => $calculation['ration_per_portion_kg'],
        ];

        // Si se provee un alimento, validamos disponibilidad de stock
        if ($request->filled('feed_inventory_id')) {
            $feed = FeedInventory::findOrFail($request->feed_inventory_id);
            $response['feed_stock_check'] = $this->rationCalculator->checkStock($feed, $calculation['daily_ration_kg']);
        }

        return response()->json($response);
    }

    /**
     * Calcula y aplica la ración de alimento diaria de un estanque,
     * validando estrictamente que el inventario tenga stock suficiente antes de registrar la bitácora.
     */
    public function applyRation(ApplyRationRequest $request, Pond $pond): JsonResponse
    {
        $validated = $request->validated();

        // 1. Obtener ración usando el Servicio o el valor manual
        $customRate = isset($validated['feeding_rate']) ? (float) $validated['feeding_rate'] : null;
        $calculation = $this->rationCalculator->calculateForPond($pond, $customRate);

        $dailyRation = isset($validated['amount_kg'])
            ? (float) $validated['amount_kg']
            : $calculation['daily_ration_kg'];

        $dailyRation = round($dailyRation, 2);
        $feedingDate = $validated['feeding_date'] ?? now()->toDateString();

        // 2. Obtener el alimento (FincaScope aísla por finca multi-tenant)
        $feed = FeedInventory::findOrFail($validated['feed_inventory_id']);

        // 3. Validación estricta de stock usando el Servicio antes de tocar la bitácora
        $stockCheck = $this->rationCalculator->checkStock($feed, $dailyRation);

        if (! $stockCheck['has_sufficient_stock']) {
            return response()->json([
                'message' => 'Stock insuficiente en el inventario para suministrar la ración solicitada.',
                'error' => 'INSUFFICIENT_STOCK',
                'pond' => [
                    'id' => $pond->id,
                    'name' => $pond->name,
                    'biomass_kg' => $calculation['biomass_kg'],
                ],
                'feed' => [
                    'id' => $feed->id,
                    'name' => $feed->name,
                    'brand' => $feed->brand,
                    'protein_percentage' => (float) $feed->protein_percentage,
                    'bag_weight_kg' => (float) $feed->bag_weight_kg,
                ],
                'required_ration_kg' => $dailyRation,
                'available_stock_kg' => $stockCheck['available_stock_kg'],
                'deficit_kg' => $stockCheck['deficit_kg'],
            ], 422);
        }

        // 4. Verificar y vincular turno del trabajador programado para la fecha
        $user = $request->user();
        $schedule = WorkSchedule::where('user_id', $user->id)
            ->forDate($feedingDate)
            ->first();

        // 5. Operación atómica: Descontar stock y registrar bitácora
        $previousStock = (float) $feed->quantity_kg;

        $log = DB::transaction(function () use ($pond, $feed, $dailyRation, $feedingDate, $validated, $user, $schedule) {
            // Descontar inventario
            $feed->decrement('quantity_kg', $dailyRation);

            // Registrar en la bitácora vinculando al trabajador y su turno correspondiente
            return FeedingLog::create([
                'user_id' => $user->id,
                'work_schedule_id' => $schedule?->id,
                'pond_id' => $pond->id,
                'feed_inventory_id' => $feed->id,
                'feeding_date' => $feedingDate,
                'amount_kg' => $dailyRation,
                'feed_name' => $feed->name,
                'feed_brand' => $feed->brand,
                'feed_type' => $feed->feed_type,
                'feed_protein_percentage' => $feed->protein_percentage,
                'feed_bag_weight_kg' => $feed->bag_weight_kg,
                'observations' => $validated['observations'] ?? null,
            ]);
        });

        // Refrescamos modelo para obtener el stock actualizado
        $feed->refresh();

        return response()->json([
            'message' => 'Ración calculada, validada contra inventario y registrada exitosamente en la bitácora.',
            'pond_id' => $pond->id,
            'pond_name' => $pond->name,
            'biomass_kg' => $calculation['biomass_kg'],
            'feeding_rate_percentage' => $calculation['feeding_rate_percentage'],
            'daily_ration_kg' => $dailyRation,
            'feed' => [
                'id' => $feed->id,
                'name' => $feed->name,
                'brand' => $feed->brand,
                'protein_percentage' => (float) $feed->protein_percentage,
                'previous_stock_kg' => $previousStock,
                'remaining_stock_kg' => (float) $feed->quantity_kg,
            ],
            'shift_verification' => [
                'trabajador' => $user->name,
                'tiene_turno_programado' => (bool) $schedule,
                'turno' => $schedule ? [
                    'id' => $schedule->id,
                    'shift_type' => $schedule->shift_type,
                    'horario' => ($schedule->start_time && $schedule->end_time) ? "{$schedule->start_time} - {$schedule->end_time}" : 'Jornada asignada',
                ] : null,
            ],
            'feeding_log' => $log,
        ], 201);
    }
}
