<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\DailyLabor;
use App\Models\FishSale;
use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WebDashboardController extends Controller
{
    /**
     * Dashboard Principal del Jefe de Finca / Administrador.
     */
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if ($request->user() && $request->user()->isPendiente()) {
            return redirect()->route('auth.pending-approval');
        }

        $user = $this->resolveActiveUser($request);
        $today = now()->toDateString();

        // 1. Métricas de Ventas de Pescado de Hoy
        $salesToday = FishSale::whereDate('sale_date', $today)->get();
        $kilosSoldToday = round((float) $salesToday->sum('kilos_sold'), 2);
        $cashTotalToday = round((float) $salesToday->sum('total_amount'), 2);

        // 2. Personal Activo / Jornales de la Semana
        $saturdayCutoff = now()->isSaturday() ? now() : now()->next(Carbon::SATURDAY);
        $weekStartDate = $saturdayCutoff->copy()->startOfWeek()->toDateString();
        $pendingLabors = DailyLabor::whereBetween('work_date', [$weekStartDate, $saturdayCutoff->toDateString()])
            ->where('payment_status', DailyLabor::STATUS_PENDIENTE)
            ->get();
        $activeWorkersCount = $pendingLabors->pluck('worker_name')->unique()->count();

        // 3. Estanques y Biomasa
        $ponds = Pond::all();
        $totalBiomass = round((float) $ponds->sum('biomass'), 2);

        // 4. Próximos Eventos de Agenda
        $upcomingEvents = CalendarEvent::with(['pond:id,name', 'createdBy:id,name'])
            ->where('event_date', '>=', $today)
            ->orderBy('event_date', 'asc')
            ->orderBy('event_time', 'asc')
            ->take(6)
            ->get();

        // 5. Órdenes de Cosecha recientes
        $recentHarvests = HarvestOrder::with(['pond:id,name', 'scheduledBy:id,name'])
            ->orderBy('scheduled_date', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'kilosSoldToday',
            'cashTotalToday',
            'activeWorkersCount',
            'ponds',
            'totalBiomass',
            'upcomingEvents',
            'recentHarvests'
        ));
    }

    /**
     * Módulo de Báscula, Cosechas y Despacho.
     */
    public function harvests(Request $request): View
    {
        $user = $this->resolveActiveUser($request);
        $ponds = Pond::all();
        $harvestOrders = HarvestOrder::with(['pond:id,name', 'scheduledBy:id,name'])
            ->orderBy('scheduled_date', 'desc')
            ->get();

        return view('harvest.index', compact('user', 'ponds', 'harvestOrders'));
    }

    /**
     * Módulo de Nómina Semanal y Liquidación de Sábados.
     */
    public function payroll(Request $request): View
    {
        $user = $this->resolveActiveUser($request);

        $saturdayCutoff = $request->input('cutoff_date')
            ? Carbon::parse($request->input('cutoff_date'))
            : (now()->isSaturday() ? now() : now()->next(Carbon::SATURDAY));

        $weekStartDate = $saturdayCutoff->copy()->startOfWeek()->toDateString();
        $cutoffDateStr = $saturdayCutoff->toDateString();

        // Jornales pendientes de la semana
        $labors = DailyLabor::whereBetween('work_date', [$weekStartDate, $cutoffDateStr])
            ->where('payment_status', DailyLabor::STATUS_PENDIENTE)
            ->with(['user', 'pond'])
            ->get();

        $fijoLabors = $labors->filter(fn (DailyLabor $labor) => $labor->isFijo());
        $temporalLabors = $labors->filter(fn (DailyLabor $labor) => $labor->isTemporal());

        // Resumen fijos excluidos
        $fijosSummary = $fijoLabors->groupBy('worker_name')->map(function ($items, $name) {
            return [
                'worker_name' => $name,
                'worker_id_card' => $items->first()->worker_id_card,
                'tipo_empleado' => 'fijo',
                'dias_trabajados' => $items->count(),
                'total_horas' => round((float) $items->sum('hours_worked'), 2),
                'labores' => $items->groupBy('labor_type')->map->count(),
                'paga_acumulada' => 0.0,
            ];
        })->values();

        // Resumen temporales con deducción de pescado fiado
        $temporalesSummary = $temporalLabors->groupBy('worker_name')->map(function ($items, $name) use ($weekStartDate, $cutoffDateStr) {
            $grossWage = round((float) $items->sum('daily_wage'), 2);
            $idCard = $items->first()->worker_id_card;

            $creditSales = FishSale::where('customer_type', FishSale::TYPE_WORKER)
                ->whereIn('payment_method', ['descuento_nomina', 'credito', 'fiado', 'pendiente'])
                ->whereBetween('sale_date', [$weekStartDate, $cutoffDateStr])
                ->where(function ($q) use ($name, $idCard) {
                    $q->where('customer_name', $name);
                    if ($idCard) {
                        $q->orWhere('customer_name', 'like', "%{$idCard}%");
                    }
                })
                ->get();

            $kilosFiados = round((float) $creditSales->sum('kilos_sold'), 2);
            $fishDeduction = round($kilosFiados * FishSale::DEFAULT_PRICE_WORKER, 2);
            $netPay = max(0.0, round($grossWage - $fishDeduction, 2));

            return [
                'worker_name' => $name,
                'worker_id_card' => $idCard,
                'tipo_empleado' => 'temporal',
                'dias_trabajados' => $items->count(),
                'total_horas' => round((float) $items->sum('hours_worked'), 2),
                'labores' => $items->groupBy('labor_type')->map->count(),
                'acumulado_jornales' => $grossWage,
                'kilos_pescado_fiado' => $kilosFiados,
                'tarifa_kilo' => FishSale::DEFAULT_PRICE_WORKER,
                'descuento_pescado' => $fishDeduction,
                'total_neto' => $netPay,
            ];
        })->values();

        $totalGross = round((float) $temporalesSummary->sum('acumulado_jornales'), 2);
        $totalDeductions = round((float) $temporalesSummary->sum('descuento_pescado'), 2);
        $totalNet = round((float) $temporalesSummary->sum('total_neto'), 2);

        return view('payroll.index', compact(
            'user',
            'weekStartDate',
            'cutoffDateStr',
            'fijosSummary',
            'temporalesSummary',
            'totalGross',
            'totalDeductions',
            'totalNet'
        ));
    }

    /**
     * Módulo de Ventas de Pescado y Caja Diaria.
     */
    public function sales(Request $request): View
    {
        $user = $this->resolveActiveUser($request);
        $selectedDate = $request->input('date', now()->toDateString());

        $sales = FishSale::whereDate('sale_date', $selectedDate)
            ->with('registeredBy:id,name')
            ->orderBy('id', 'desc')
            ->get();

        $visitorSales = $sales->where('customer_type', FishSale::TYPE_VISITOR);
        $workerSales = $sales->where('customer_type', FishSale::TYPE_WORKER);

        $totalKilos = round((float) $sales->sum('kilos_sold'), 2);
        $totalCash = round((float) $sales->sum('total_amount'), 2);

        $visitorKilos = round((float) $visitorSales->sum('kilos_sold'), 2);
        $visitorTotal = round((float) $visitorSales->sum('total_amount'), 2);

        $workerKilos = round((float) $workerSales->sum('kilos_sold'), 2);
        $workerTotal = round((float) $workerSales->sum('total_amount'), 2);

        return view('sales.index', compact(
            'user',
            'selectedDate',
            'sales',
            'totalKilos',
            'totalCash',
            'visitorKilos',
            'visitorTotal',
            'workerKilos',
            'workerTotal'
        ));
    }

    /**
     * Resuelve el usuario activo para renderizar las vistas web,
     * usando sesión autenticada o el primer usuario del sistema para vista demo/offline.
     */
    private function resolveActiveUser(Request $request): User
    {
        if ($request->user()) {
            return $request->user();
        }

        return User::where('role', User::ROLE_JEFE_FINCA)->first()
            ?? User::where('role', User::ROLE_ADMIN)->first()
            ?? User::first()
            ?? new User([
                'name' => 'Jefe de Finca (Demo)',
                'email' => 'jefe@piscicola.com',
                'role' => User::ROLE_JEFE_FINCA,
                'finca_id' => 1,
            ]);
    }
}
