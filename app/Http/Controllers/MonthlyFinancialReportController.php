<?php

namespace App\Http\Controllers;

use App\Models\ControlAireador;
use App\Models\DailyLabor;
use App\Models\FeedingLog;
use App\Models\FishSale;
use App\Models\HarvestOrder;
use App\Models\PayrollSettlement;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonthlyFinancialReportController extends Controller
{
    /**
     * Informe Ejecutivo Mensual de Rentabilidad y Costos de Producción para el Dueño / Jefe Mayor.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $finca = $user->finca_segura;

        // Mes a consultar (soporta 'Y-m', o parámetros year y month)
        $rawMonth = (string) $request->input('month', now()->format('Y-m'));
        if (preg_match('/^\d{4}-\d{1,2}$/', $rawMonth)) {
            $parts = explode('-', $rawMonth);
            $startOfMonth = Carbon::create((int) $parts[0], (int) $parts[1], 1)->startOfMonth();
        } else {
            $year = (int) $request->input('year', now()->year);
            $month = (int) $rawMonth ?: now()->month;
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        }
        $monthStr = $startOfMonth->format('Y-m');
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 1. Cosechas y Ventas del Mes
        $cosechasMes = HarvestOrder::where('finca_id', $finca->id)
            ->whereBetween('scheduled_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereNotNull('net_weight_kg')
            ->get();

        $kilosCosechadosTotal = (float) $cosechasMes->sum('net_weight_kg');

        $ventasMes = FishSale::where('finca_id', $finca->id)
            ->whereBetween('sale_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $kilosVendidosTotal = (float) $ventasMes->sum('kilos_sold');

        $ventasVisitantes = $ventasMes->where('customer_type', 'visitante');
        $kilosVisitantes = (float) $ventasVisitantes->sum('kilos_sold');
        $dineroVisitantes = (float) $ventasVisitantes->sum('total_amount');

        $ventasMayoristas = $ventasMes->whereIn('customer_type', ['mayorista', 'intermediario', 'empresa']);
        $kilosMayoristas = (float) $ventasMayoristas->sum('kilos_sold');
        $dineroMayoristas = (float) $ventasMayoristas->sum('total_amount');

        $ventasTrabajadores = $ventasMes->where('customer_type', 'trabajador');
        $kilosTrabajadores = (float) $ventasTrabajadores->sum('kilos_sold');
        $dineroTrabajadores = (float) $ventasTrabajadores->sum('total_amount');

        $ingresoBrutoTotal = (float) $ventasMes->sum('total_amount');

        // 2. Alimento Suministrado y Cálculo del FCA
        $alimentacionesMes = FeedingLog::where('finca_id', $finca->id)
            ->whereBetween('feeding_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $kilosAlimentoTotal = (float) $alimentacionesMes->sum('amount_kg');

        // Costo promedio de concentrado: $4.800/kg (o cálculo si está en inventario)
        $costoPorKiloAlimento = (float) $finca->obtenerConfig('precios.costo_alimento_kg', 4800);
        $gastoAlimentoTotal = round($kilosAlimentoTotal * $costoPorKiloAlimento, 2);

        // Kilos base para cálculo de FCA (mínimo kilos cosechados o estimación biomasa)
        $kilosPescadoBaseFca = $kilosCosechadosTotal > 0 ? $kilosCosechadosTotal : max(1.0, $kilosVendidosTotal);
        $fcaGlobal = round($kilosAlimentoTotal / $kilosPescadoBaseFca, 2);

        // 3. Mano de Obra y Nómina del Mes
        $liquidacionesNomina = PayrollSettlement::whereBetween('cutoff_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $gastoNominaTotal = (float) $liquidacionesNomina->sum('total_amount');
        if ($gastoNominaTotal === 0.0) {
            $gastoNominaTotal = (float) DailyLabor::where('finca_id', $finca->id)
                ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->sum('daily_wage');
        }

        // 4. Costo de Energía / Combustible de Equipos y Aireadores
        $horasAireadores = (float) ControlAireador::where('finca_id', $finca->id)
            ->whereBetween('fecha', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereNotNull('total_horas')
            ->sum('total_horas');

        // Costo estimado de energía/combustible por hora de aireador: $3.200 COP
        $costoHoraAireador = (float) $finca->obtenerConfig('operacion.costo_hora_aireador', 3200);
        $gastoEnergiaTotal = round($horasAireadores * $costoHoraAireador, 2);

        // 5. Consolidación de Costos Directos
        $costosOperativosTotales = round($gastoAlimentoTotal + $gastoNominaTotal + $gastoEnergiaTotal, 2);

        // 6. Costo Real por Kilo Producido
        $kilosReferenciaCosto = $kilosCosechadosTotal > 0 ? $kilosCosechadosTotal : max(1.0, $kilosVendidosTotal);
        $costoPorKiloProducido = round($costosOperativosTotales / $kilosReferenciaCosto, 2);

        // 7. Utilidad Neta Real
        $utilidadNetaReal = round($ingresoBrutoTotal - $costosOperativosTotales, 2);
        $margenRentabilidadPorcentaje = $ingresoBrutoTotal > 0
            ? round(($utilidadNetaReal / $ingresoBrutoTotal) * 100, 1)
            : 0.0;

        $data = [
            'finca' => $finca,
            'monthStr' => $monthStr,
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
            'kilosCosechadosTotal' => $kilosCosechadosTotal,
            'kilosVendidosTotal' => $kilosVendidosTotal,
            'desgloseVentas' => [
                'visitantes' => ['kilos' => $kilosVisitantes, 'total' => $dineroVisitantes],
                'mayoristas' => ['kilos' => $kilosMayoristas, 'total' => $dineroMayoristas],
                'trabajadores' => ['kilos' => $kilosTrabajadores, 'total' => $dineroTrabajadores],
            ],
            'ingresoBrutoTotal' => $ingresoBrutoTotal,
            'kilosAlimentoTotal' => $kilosAlimentoTotal,
            'fcaGlobal' => $fcaGlobal,
            'horasAireadores' => $horasAireadores,
            'costosOperativos' => [
                'alimento' => $gastoAlimentoTotal,
                'nomina' => $gastoNominaTotal,
                'energia' => $gastoEnergiaTotal,
                'total' => $costosOperativosTotales,
            ],
            'costoPorKiloProducido' => $costoPorKiloProducido,
            'utilidadNetaReal' => $utilidadNetaReal,
            'margenRentabilidadPorcentaje' => $margenRentabilidadPorcentaje,
            'fechaGeneracion' => now()->timezone('America/Bogota')->format('d/m/Y g:i A'),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.reporte-mensual', $data);
    }
}
