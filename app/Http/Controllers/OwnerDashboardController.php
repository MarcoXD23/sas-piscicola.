<?php

namespace App\Http\Controllers;

use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\Pond;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    /**
     * Resumen global y financiero para el Dueño / Jefe de Finca.
     */
    public function overview(Request $request): JsonResponse
    {
        $pondsCount = Pond::count();
        $totalBiomassKg = round(Pond::sum('biomass'), 2);
        $totalPopulation = (int) Pond::sum('fish_population');
        $feedStockKg = round(FeedInventory::sum('quantity_kg'), 2);
        $totalFeedSuppliedKg = round(FeedingLog::sum('amount_kg'), 2);

        $teamCount = [
            'total_usuarios' => User::count(),
            'administradores' => User::where('role', User::ROLE_ADMIN)->count(),
            'trabajadores' => User::where('role', User::ROLE_WORKER)->count(),
            'vigilantes' => User::where('role', User::ROLE_GUARD)->count(),
        ];

        return response()->json([
            'message' => 'Panel general gerencial de El SAS Piscícola recuperado exitosamente.',
            'rol_acceso' => $request->user()->role,
            'kpis_operativos' => [
                'total_estanques' => $pondsCount,
                'poblacion_total_peces' => $totalPopulation,
                'biomasa_total_kg' => $totalBiomassKg,
                'alimento_disponible_stock_kg' => $feedStockKg,
                'alimento_suministrado_historico_kg' => $totalFeedSuppliedKg,
            ],
            'equipo_humano' => $teamCount,
        ]);
    }

    /**
     * Reporte financiero y de consumo de alimento consolidado.
     */
    public function financialReport(Request $request): JsonResponse
    {
        // Consumo de alimento agrupado por marca y nivel de proteína
        $feedBreakdown = FeedingLog::query()
            ->selectRaw('feed_brand, feed_protein_percentage, sum(amount_kg) as total_kg_consumidos, count(*) as tandas_suministradas')
            ->groupBy('feed_brand', 'feed_protein_percentage')
            ->get();

        $activeSchedules = WorkSchedule::query()
            ->whereDate('schedule_date', '>=', now()->startOfWeek())
            ->whereDate('schedule_date', '<=', now()->endOfWeek())
            ->count();

        return response()->json([
            'message' => 'Reporte financiero y de consumo consolidado generado.',
            'resumen_financiero' => [
                'alimento_total_consumido_kg' => round(FeedingLog::sum('amount_kg'), 2),
                'inventario_valor_stock_kg' => round(FeedInventory::sum('quantity_kg'), 2),
                'turnos_programados_semana_actual' => $activeSchedules,
            ],
            'desglose_por_alimento_y_proteina' => $feedBreakdown,
        ]);
    }

    /**
     * Lista de administradores y personal de todas las fincas.
     */
    public function teamMembers(Request $request): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'finca_id', 'created_at')
            ->orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'total_miembros' => $users->count(),
            'data' => $users,
        ]);
    }
}
