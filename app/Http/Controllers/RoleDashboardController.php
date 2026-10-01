<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\AlimentoBodega;
use App\Models\Estanque;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\FishCredit;
use App\Models\FishSale;
use App\Models\HarvestOrder;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleDashboardController extends Controller
{
    /**
     * Enrutador central del dashboard: Redirige según el rol del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isJefe()) {
            return redirect()->route('dashboard.jefe');
        }

        if ($user->isAdmin()) {
            return redirect()->route('dashboard.admin');
        }

        return redirect()->route('dashboard.trabajador');
    }

    /**
     * Panel Macro del Jefe Mayor:
     * - Biomasa total de la finca
     * - Proyecciones de cosecha programadas
     * - Resumen financiero (ventas recaudadas vs nómina)
     * - Solicitudes de permisos laborales pendientes de revisión
     */
    public function jefeDashboard(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()->finca_id ?? 1;
        $ponds = Estanque::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
            ->get();
        $totalBiomass = round((float) $ponds->sum('biomass'), 2);
        $totalFingerlings = (int) $ponds->sum('fingerlings_stocked');
        $totalPecesVivos = (int) $ponds->sum('fish_population');

        $upcomingHarvests = HarvestOrder::query()
            ->with(['pond:id,name', 'scheduledBy:id,name'])
            ->whereIn('status', [HarvestOrder::STATUS_PROGRAMADA, HarvestOrder::STATUS_PESAJE])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        $projectedKg = round((float) $upcomingHarvests->sum('estimated_kg'), 2);

        $salesThisMonth = (float) FishSale::query()
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('total_amount');

        $pendingLeaves = LeaveRequest::query()
            ->with('user:id,name,role,employment_type')
            ->where('status', LeaveRequest::STATUS_PENDIENTE)
            ->get();

        $workers = User::whereIn('role', [
            User::ROLE_WORKER,
            User::ROLE_TRABAJADOR,
            User::ROLE_GUARD,
        ])->get();

        $lagosListosPesca = $ponds->filter(fn ($e) => (float) $e->average_weight >= 450 || $e->status === 'En Cosecha');

        $lagosActivosCount = $ponds->where('status', '!=', 'Inactivo')->count();
        if ($lagosActivosCount === 0 && $ponds->isNotEmpty()) {
            $lagosActivosCount = $ponds->count();
        }

        $data = [
            'rol_usuario' => 'jefe_mayor',
            'metricas_macro' => [
                'biomasa_total_kg' => $totalBiomass,
                'total_biomasa_kg' => $totalBiomass,
                'alevinos_sembrados_total' => $totalFingerlings,
                'peces_vivos_total' => $totalPecesVivos,
                'total_peces_vivos' => $totalPecesVivos,
                'estanques_activos_count' => $lagosActivosCount,
                'lagos_activos_count' => $lagosActivosCount,
                'total_lagos_count' => $ponds->count(),
                'lagos_listos_pesca_count' => $lagosListosPesca->count(),
                'kilos_cosecha_proyectados' => $projectedKg,
                'ventas_mes_dinero' => $salesThisMonth,
                'personal_activo_count' => $workers->count(),
                'permisos_pendientes_count' => $pendingLeaves->count(),
            ],
            'cosechas_proyectadas' => $upcomingHarvests,
            'permisos_pendientes' => $pendingLeaves,
            'estanques' => $ponds,
            'lagos_listos_pesca' => $lagosListosPesca,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('dashboards.jefe', $data);
    }

    /**
     * Panel Operativo del Administrador:
     * - Control de nómina sabatina y jornales pendientes
     * - Alertas de inventario y bodega (stock crítico, días restantes)
     * - Tablero de tareas asignadas para ausencias
     * - Muestreos y bitácora de alimentación de hoy
     * - Gestión exclusiva de lagos, conteo de peces vivos, días/meses y biomasa
     */
    public function adminDashboard(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()->finca_id ?? 1;

        $estanques = Estanque::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
            ->get();

        $inventories = FeedInventory::all();
        $lowStockItems = $inventories->filter(fn (FeedInventory $inv) => $inv->isLowStock())->values();

        $tasks = AdminTask::query()
            ->with(['assignedTo:id,name'])
            ->where('status', '!=', AdminTask::STATUS_COMPLETADA)
            ->orderBy('due_date', 'asc')
            ->get();

        $todayFeedingLogs = FeedingLog::query()
            ->with(['pond:id,name', 'user:id,name'])
            ->whereDate('feeding_date', now()->toDateString())
            ->get();

        $todaySales = FishSale::query()
            ->whereDate('sale_date', now()->toDateString())
            ->get();

        $pendingFishCredits = FishCredit::query()
            ->with('user:id,name')
            ->where('status', FishCredit::STATUS_PENDIENTE)
            ->get();

        $lagosListosPesca = $estanques->filter(fn ($e) => (float) $e->average_weight >= 450 || $e->status === 'En Cosecha');
        $totalBultosBodega = (float) AlimentoBodega::where('finca_id', $fincaId)->sum('stock_bultos');
        $lagosActivosCount = $estanques->where('status', '!=', 'Inactivo')->count();
        if ($lagosActivosCount === 0 && $estanques->isNotEmpty()) {
            $lagosActivosCount = $estanques->count();
        }

        $data = [
            'rol_usuario' => 'administrador',
            'metricas_operativas' => [
                'alertas_bodega_count' => $lowStockItems->count(),
                'tareas_pendientes_count' => $tasks->count(),
                'kilos_alimentados_hoy' => round((float) $todayFeedingLogs->sum('amount_kg'), 2),
                'recaudo_ventas_hoy' => round((float) $todaySales->sum('total_amount'), 2),
                'kilos_pescado_fiado_pendiente' => round((float) $pendingFishCredits->sum('kilos'), 2),
                'total_biomasa_kg' => round((float) $estanques->sum('biomass'), 2),
                'biomasa_total_kg' => round((float) $estanques->sum('biomass'), 2),
                'total_peces_vivos' => (int) $estanques->sum('fish_population'),
                'peces_vivos_total' => (int) $estanques->sum('fish_population'),
                'lagos_activos_count' => $lagosActivosCount,
                'estanques_activos_count' => $lagosActivosCount,
                'total_lagos_count' => $estanques->count(),
                'lagos_listos_pesca_count' => $lagosListosPesca->count(),
            ],
            'estanques' => $estanques,
            'lagos_listos_pesca' => $lagosListosPesca,
            'alertas_bodega' => $lowStockItems,
            'tareas_activas' => $tasks,
            'alimentacion_hoy' => $todayFeedingLogs,
            'creditos_pendientes' => $pendingFishCredits,
            'total_bultos_bodega' => $totalBultosBodega,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('dashboards.admin', $data);
    }

    /**
     * Panel Operativo del Trabajador:
     * - Turno rotativo asignado para la semana
     * - Registro de alimentación con apetito
     * - Saldo de pescado fiado propio
     * - Mis solicitudes de permiso y horas extras
     */
    public function trabajadorDashboard(Request $request): View|JsonResponse
    {
        return app(TrabajadorController::class)->dashboard($request);
    }
}
