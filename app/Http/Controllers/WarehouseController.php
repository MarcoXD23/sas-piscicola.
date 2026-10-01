<?php

namespace App\Http\Controllers;

use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\RotativeSchedule;
use App\Models\User;
use App\Models\WarehouseMovement;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Estado general de bodega: Stock actual, días restantes de alimento y alertas de bajo stock.
     */
    public function inventoryStatus(Request $request): JsonResponse
    {
        $inventories = FeedInventory::all()->map(function (FeedInventory $item) {
            $avgConsumption = $item->dailyAverageConsumption();
            $daysRemaining = $item->daysOfFeedRemaining();
            $isLow = $item->isLowStock();

            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'brand' => $item->brand,
                'feed_type' => $item->feed_type,
                'protein_percentage' => (float) $item->protein_percentage,
                'stock_actual_kg' => (float) $item->quantity_kg,
                'umbral_alerta_minimo_kg' => (float) ($item->min_stock_alert_kg ?? 100),
                'consumo_promedio_diario_kg' => $avgConsumption,
                'dias_alimento_restantes' => $daysRemaining >= 900 ? 'Sin registros de consumo' : $daysRemaining,
                'alerta_stock_bajo' => $isLow,
                'mensaje_alerta' => $isLow
                    ? "ALERTA: Stock ({$item->quantity_kg} kg) por debajo del umbral mínimo ({$item->min_stock_alert_kg} kg)."
                    : 'Stock dentro de niveles óptimos.',
            ];
        });

        $lowStockCount = $inventories->where('alerta_stock_bajo', true)->count();

        return response()->json([
            'message' => 'Estado de inventario y bodega recuperado.',
            'alertas_activas_count' => $lowStockCount,
            'inventarios' => $inventories,
        ]);
    }

    /**
     * Registrar entrada, salida o ajuste manual en bodega.
     */
    public function storeMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feed_inventory_id' => ['required', 'exists:feed_inventories,id'],
            'movement_type' => ['required', 'in:entrada,salida,ajuste'],
            'quantity_kg' => ['required', 'numeric', 'min:0.1'],
            'bags_count' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'movement_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = WarehouseMovement::create([
            'feed_inventory_id' => $validated['feed_inventory_id'],
            'movement_type' => $validated['movement_type'],
            'quantity_kg' => $validated['quantity_kg'],
            'bags_count' => $validated['bags_count'] ?? 0,
            'unit_cost' => $validated['unit_cost'] ?? null,
            'movement_date' => $validated['movement_date'] ?? now()->toDateString(),
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        $inventory = $movement->feedInventory->fresh();
        $daysRemaining = $inventory->daysOfFeedRemaining();
        $whatsappNotified = false;

        if ($daysRemaining < 3.0 && $movement->movement_type === 'salida') {
            $admins = User::whereIn('role', [
                User::ROLE_ADMIN,
                User::ROLE_ADMINISTRADOR,
                User::ROLE_JEFE_MAYOR,
                User::ROLE_OWNER,
            ])->get();

            app(WhatsAppNotificationService::class)->sendFeedInventoryAlert(
                $inventory,
                $daysRemaining,
                $admins
            );
            $whatsappNotified = true;
        }

        return response()->json([
            'message' => "Movimiento de bodega ({$movement->movement_type}) registrado exitosamente.",
            'nuevo_stock_kg' => (float) $inventory->quantity_kg,
            'dias_autonomia' => $daysRemaining,
            'alerta_stock_bajo' => $inventory->isLowStock(),
            'whatsapp_notificado' => $whatsappNotified,
            'data' => $movement->load('feedInventory:id,name,quantity_kg', 'createdBy:id,name'),
        ], 201);
    }

    /**
     * Registro diario de alimentación por estanque con nivel de apetito y bultos.
     */
    public function storeFeedingLog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'feed_inventory_id' => ['nullable', 'exists:feed_inventories,id'],
            'amount_kg' => ['required', 'numeric', 'min:0.1'],
            'bags_fed' => ['nullable', 'numeric', 'min:0'],
            'appetite_level' => ['required', 'in:bueno,regular,malo'],
            'feeding_date' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:500'],
        ]);

        $pond = Pond::findOrFail($validated['pond_id']);
        $inventory = ! empty($validated['feed_inventory_id'])
            ? FeedInventory::find($validated['feed_inventory_id'])
            : FeedInventory::first();

        $amountKg = (float) $validated['amount_kg'];
        $appetite = $validated['appetite_level'];
        $feedingDate = $validated['feeding_date'] ?? now()->toDateString();

        // Descontar inventario si existe suficiente
        if ($inventory) {
            $inventory->subtractQuantity($amountKg);
        }

        $log = FeedingLog::create([
            'pond_id' => $pond->id,
            'user_id' => $request->user()->id,
            'feed_inventory_id' => $inventory?->id,
            'feeding_date' => $feedingDate,
            'amount_kg' => $amountKg,
            'bags_fed' => $validated['bags_fed'] ?? ($inventory?->bag_weight_kg ? round($amountKg / $inventory->bag_weight_kg, 2) : 0),
            'appetite_level' => $appetite,
            'feed_name' => $inventory?->name,
            'feed_brand' => $inventory?->brand,
            'feed_type' => $inventory?->feed_type,
            'feed_protein_percentage' => $inventory?->protein_percentage,
            'feed_bag_weight_kg' => $inventory?->bag_weight_kg,
            'observations' => $validated['observations'] ?? null,
        ]);

        return response()->json([
            'message' => 'Alimentación diaria registrada y stock de bodega actualizado.',
            'nivel_apetito' => $appetite,
            'stock_restante_kg' => (float) $inventory?->quantity_kg,
            'alerta_stock_bajo' => $inventory?->isLowStock(),
            'data' => $log->load('pond:id,name', 'user:id,name'),
        ], 201);
    }

    /**
     * Programación y consulta de turnos rotativos semanales para los 4 trabajadores:
     * - lunes_a_viernes
     * - fin_de_semana
     * - guardia_nocturna
     */
    public function rotativeSchedulesIndex(Request $request): JsonResponse
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        $schedules = RotativeSchedule::query()
            ->with(['user:id,name,role,employment_type', 'assignedBy:id,name'])
            ->orderBy('week_start_date', 'desc')
            ->get();

        $currentWeek = $schedules->filter(function ($s) use ($startOfWeek, $endOfWeek) {
            return $s->week_start_date->toDateString() <= $endOfWeek && $s->week_end_date->toDateString() >= $startOfWeek;
        })->values();

        return response()->json([
            'message' => 'Turnos rotativos de trabajadores recuperados exitosamente.',
            'semana_actual' => [
                'inicio_lunes' => $startOfWeek,
                'fin_domingo' => $endOfWeek,
                'turnos' => $currentWeek,
            ],
            'todos_los_turnos' => $schedules,
        ]);
    }

    /**
     * Asignar un turno rotativo a un trabajador.
     */
    public function storeRotativeSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
            'shift_type' => ['required', 'in:lunes_a_viernes,fin_de_semana,guardia_nocturna'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $schedule = RotativeSchedule::create([
            'user_id' => $validated['user_id'],
            'week_start_date' => $validated['week_start_date'],
            'week_end_date' => $validated['week_end_date'],
            'shift_type' => $validated['shift_type'],
            'notes' => $validated['notes'] ?? null,
            'assigned_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Turno rotativo asignado correctamente.',
            'data' => $schedule->load('user:id,name,role', 'assignedBy:id,name'),
        ], 201);
    }
}
