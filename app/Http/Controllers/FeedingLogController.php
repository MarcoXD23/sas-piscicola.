<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedingLogRequest;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\WorkSchedule;
use App\Services\RationCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedingLogController extends Controller
{
    public function __construct(
        protected RationCalculatorService $rationCalculator
    ) {}

    /**
     * Muestra el historial y bitácoras diarias de alimentación.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FeedingLog::query()->with([
            'user:id,name,email,role',
            'workSchedule:id,shift_type,schedule_date,status',
            'pond:id,name',
            'feedInventory:id,name,brand,feed_type,protein_percentage,bag_weight_kg',
        ]);

        // Filtro por estanque
        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        // Filtro por trabajador que alimentó
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('start_date')) {
            $query->whereDate('feeding_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('feeding_date', '<=', $request->end_date);
        }

        // Filtro por porcentaje de proteína (ej. 32, 38)
        if ($request->filled('protein_percentage')) {
            $query->where('feed_protein_percentage', $request->protein_percentage);
        }

        // Filtro por marca
        if ($request->filled('brand')) {
            $query->where('feed_brand', 'like', '%'.$request->brand.'%');
        }

        $logs = $query->orderBy('feeding_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Bitácora de alimentación recuperada exitosamente.',
            'total_records' => $logs->count(),
            'total_amount_kg' => round($logs->sum('amount_kg'), 2),
            'data' => $logs,
        ]);
    }

    /**
     * Registra manualmente una entrada en la bitácora diaria de alimentación,
     * validando el stock disponible y vinculando el turno programado del trabajador.
     */
    public function store(StoreFeedingLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $feedingDate = $validated['feeding_date'] ?? now()->toDateString();
        $amountKg = (float) $validated['amount_kg'];
        $user = $request->user();

        // FincaScope asegura pertenencia a la finca autenticada
        $feed = FeedInventory::findOrFail($validated['feed_inventory_id']);
        $pond = Pond::findOrFail($validated['pond_id']);

        // 1. Validación de stock mediante el Servicio
        $stockCheck = $this->rationCalculator->checkStock($feed, $amountKg);

        if (! $stockCheck['has_sufficient_stock']) {
            return response()->json([
                'message' => 'Stock insuficiente en el inventario para registrar la alimentación.',
                'error' => 'INSUFFICIENT_STOCK',
                'feed' => [
                    'id' => $feed->id,
                    'name' => $feed->name,
                    'brand' => $feed->brand,
                ],
                'required_kg' => $amountKg,
                'available_stock_kg' => $stockCheck['available_stock_kg'],
                'deficit_kg' => $stockCheck['deficit_kg'],
            ], 422);
        }

        // 2. Verificar si el trabajador tiene un turno programado en esta fecha
        $schedule = WorkSchedule::where('user_id', $user->id)
            ->forDate($feedingDate)
            ->first();

        // 3. Operación atómica: Descontar stock y registrar en bitácora
        $log = DB::transaction(function () use ($pond, $feed, $amountKg, $feedingDate, $validated, $user, $schedule) {
            $feed->decrement('quantity_kg', $amountKg);

            return FeedingLog::create([
                'user_id' => $user->id,
                'work_schedule_id' => $schedule?->id,
                'pond_id' => $pond->id,
                'feed_inventory_id' => $feed->id,
                'feeding_date' => $feedingDate,
                'amount_kg' => $amountKg,
                'feed_name' => $feed->name,
                'feed_brand' => $feed->brand,
                'feed_type' => $feed->feed_type,
                'feed_protein_percentage' => $feed->protein_percentage,
                'feed_bag_weight_kg' => $feed->bag_weight_kg,
                'observations' => $validated['observations'] ?? null,
            ]);
        });

        $feed->refresh();

        return response()->json([
            'message' => 'Alimentación registrada en bitácora y descontada del inventario exitosamente.',
            'data' => $log->load(['user:id,name,role', 'workSchedule:id,shift_type,schedule_date']),
            'shift_verification' => [
                'trabajador' => $user->name,
                'tiene_turno_programado' => (bool) $schedule,
                'turno' => $schedule ? [
                    'id' => $schedule->id,
                    'shift_type' => $schedule->shift_type,
                ] : null,
            ],
            'remaining_feed_kg' => (float) $feed->quantity_kg,
        ], 201);
    }

    /**
     * Muestra el detalle de un registro específico de la bitácora.
     */
    public function show(FeedingLog $feedingLog): JsonResponse
    {
        $feedingLog->load([
            'user:id,name,email,role',
            'workSchedule:id,shift_type,schedule_date,status',
            'pond:id,name',
            'feedInventory',
        ]);

        return response()->json([
            'data' => $feedingLog,
        ]);
    }
}
