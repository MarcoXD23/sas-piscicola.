<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFishSaleRequest;
use App\Models\FishSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FishSaleController extends Controller
{
    /**
     * Listado de ventas de pescado.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FishSale::query()->with('registeredBy:id,name,role');

        if ($request->filled('date')) {
            $query->whereDate('sale_date', $request->date);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        $sales = $query->orderBy('sale_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Ventas de pescado recuperadas exitosamente.',
            'total_ventas' => $sales->count(),
            'total_kilos' => round($sales->sum('kilos_sold'), 2),
            'total_dinero' => round($sales->sum('total_amount'), 2),
            'data' => $sales,
        ]);
    }

    /**
     * Registrar una venta diaria de pescado aplicando tarifa diferenciada:
     * - Visitantes externos: $9.000 / kg (soporta registro por efectivo con conversión automática a kilos)
     * - Trabajadores internos: $7.000 / kg
     */
    public function store(StoreFishSaleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $customerType = $validated['customer_type'];
        $cashReceived = isset($validated['cash_received']) ? (float) $validated['cash_received'] : null;

        $defaultVisitorPrice = (float) ($user?->finca?->obtenerConfig('precios.pescado_visitante_kg') ?? FishSale::DEFAULT_PRICE_VISITOR);
        $defaultWorkerPrice = (float) ($user?->finca?->obtenerConfig('precios.pescado_empleado_kg') ?? FishSale::DEFAULT_PRICE_WORKER);

        $pricePerKg = isset($validated['price_per_kg'])
            ? (float) $validated['price_per_kg']
            : ($customerType === FishSale::TYPE_VISITOR ? $defaultVisitorPrice : $defaultWorkerPrice);

        if ($customerType === FishSale::TYPE_VISITOR && $cashReceived > 0 && empty($validated['kilos_sold'])) {
            $kilos = round($cashReceived / $pricePerKg, 2);
            $totalAmount = $cashReceived;
        } else {
            $kilos = (float) ($validated['kilos_sold'] ?? 0);
            $totalAmount = round($kilos * $pricePerKg, 2);
            if (! $cashReceived && ($validated['payment_method'] ?? 'efectivo') === 'efectivo') {
                $cashReceived = $totalAmount;
            }
        }

        $saleDate = $validated['sale_date'] ?? now()->toDateString();

        $sale = FishSale::create([
            'sale_date' => $saleDate,
            'customer_type' => $customerType,
            'customer_name' => $validated['customer_name'] ?? ($customerType === FishSale::TYPE_VISITOR ? 'Visitante Finca' : null),
            'kilos_sold' => $kilos,
            'price_per_kg' => $pricePerKg,
            'cash_received' => $cashReceived,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'] ?? 'efectivo',
            'registered_by_user_id' => $user->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Venta de pescado registrada exitosamente en la caja diaria.',
            'categoria_tarifa' => $customerType === FishSale::TYPE_WORKER
                ? 'Trabajador interno ($'.number_format($pricePerKg, 0, ',', '.').'/kg)'
                : 'Visitante externo ($'.number_format($pricePerKg, 0, ',', '.').'/kg)',
            'conversion' => [
                'efectivo_recaudado' => $sale->cash_received,
                'precio_fijo_kg' => $pricePerKg,
                'kilos_calculados' => $sale->kilos_sold,
            ],
            'data' => $sale->load('registeredBy:id,name'),
        ], 201);
    }

    /**
     * Endpoint directo para venta rápida en efectivo a visitantes:
     * Convierte dinero en efectivo a kilos (Total Dinero / precio_visitante_finca).
     */
    public function storeVisitorCash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_amount' => ['required', 'numeric', 'min:1000'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $visitorPrice = (float) ($user?->finca?->obtenerConfig('precios.pescado_visitante_kg') ?? FishSale::DEFAULT_PRICE_VISITOR);

        $cash = (float) $validated['cash_amount'];
        $kilos = round($cash / $visitorPrice, 2);

        $sale = FishSale::create([
            'sale_date' => now()->toDateString(),
            'customer_type' => FishSale::TYPE_VISITOR,
            'customer_name' => $validated['customer_name'] ?? 'Visitante Particular',
            'kilos_sold' => $kilos,
            'price_per_kg' => $visitorPrice,
            'cash_received' => $cash,
            'total_amount' => $cash,
            'payment_method' => 'efectivo',
            'registered_by_user_id' => $user->id,
            'notes' => $validated['notes'] ?? 'Venta rápida en efectivo a visitante en portería/finca',
        ]);

        return response()->json([
            'message' => "Venta en efectivo registrada: $ {$cash} equivalen a {$kilos} kg a $ ".number_format($visitorPrice, 0, ',', '.').'/kg.',
            'kilos_vendidos' => $kilos,
            'dinero_recaudado' => $cash,
            'data' => $sale,
        ], 201);
    }

    /**
     * Caja Diaria de Ventas:
     * Calcula automáticamente los kilos totales vendidos y el total en dinero recaudado,
     * desglosado por categoría (visitantes vs trabajadores).
     */
    public function dailyCashbox(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        $salesQuery = FishSale::whereDate('sale_date', $date);

        // Ventas de Visitantes ($9.000/kg)
        $visitorSales = (clone $salesQuery)->where('customer_type', FishSale::TYPE_VISITOR)->get();
        $visitorKilos = round($visitorSales->sum('kilos_sold'), 2);
        $visitorTotal = round($visitorSales->sum('total_amount'), 2);

        // Ventas de Trabajadores ($7.000/kg)
        $workerSales = (clone $salesQuery)->where('customer_type', FishSale::TYPE_WORKER)->get();
        $workerKilos = round($workerSales->sum('kilos_sold'), 2);
        $workerTotal = round($workerSales->sum('total_amount'), 2);

        // Totales globales del día
        $totalKilos = round($visitorKilos + $workerKilos, 2);
        $totalDinero = round($visitorTotal + $workerTotal, 2);

        $allSales = (clone $salesQuery)->with('registeredBy:id,name')->get();

        return response()->json([
            'fecha_caja' => $date,
            'balance_caja_diaria' => [
                'total_kilos_vendidos' => $totalKilos,
                'total_dinero_recaudado' => $totalDinero,
            ],
            'desglose_por_categoria' => [
                'visitantes' => [
                    'tarifa_estandar_kg' => FishSale::DEFAULT_PRICE_VISITOR,
                    'kilos_vendidos' => $visitorKilos,
                    'total_recaudado' => $visitorTotal,
                    'transacciones_count' => $visitorSales->count(),
                ],
                'trabajadores' => [
                    'tarifa_estandar_kg' => FishSale::DEFAULT_PRICE_WORKER,
                    'kilos_vendidos' => $workerKilos,
                    'total_recaudado' => $workerTotal,
                    'transacciones_count' => $workerSales->count(),
                ],
            ],
            'detalle_ventas' => $allSales,
        ]);
    }
}
