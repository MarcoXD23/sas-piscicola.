<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Http\Resources\VentaResource;
use App\Models\HarvestOrder;
use App\Models\Pond;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * Listado general de ventas comerciales y liquidaciones.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Venta::query()->with(['estanque:id,name', 'harvestOrder', 'user:id,name,role']);

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->filled('cliente')) {
            $query->where('cliente', 'like', '%'.$request->cliente.'%');
        }

        if ($request->filled('forma_pago')) {
            $query->where('forma_pago', $request->forma_pago);
        }

        if ($request->filled('harvest_order_id')) {
            $query->where('harvest_order_id', $request->harvest_order_id);
        }

        $ventas = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Ventas de pescado recuperadas exitosamente.',
            'total_registros' => $ventas->count(),
            'total_kg_vendidos' => round($ventas->sum('kg_vendidos'), 2),
            'total_ingresos_cop' => round($ventas->sum('total_venta'), 2),
            'data' => VentaResource::collection($ventas),
        ]);
    }

    /**
     * Registra una venta comercial con control obligatorio de tiempo de retiro ICA
     * y liquidación de órdenes de cosecha.
     */
    public function store(StoreVentaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $fecha = isset($validated['fecha']) ? Carbon::parse($validated['fecha']) : now();

        // 1. Verificación estricta de tiempo de retiro ICA si está asociado a un estanque
        if (! empty($validated['estanque_id'])) {
            $estanque = Pond::findOrFail($validated['estanque_id']);
            if ($estanque->estaEnTiempoRetiro($fecha)) {
                $fechaFin = $estanque->fechaFinRetiroActivo($fecha);

                return response()->json([
                    'message' => "BLOQUEO SANITARIO ICA: Prohibida la venta de pescado del {$estanque->name}. El estanque se encuentra en período de carencia hasta el {$fechaFin}.",
                    'error' => 'RETIRO_ICA_ACTIVO',
                    'estanque_id' => $estanque->id,
                    'fecha_habil_venta' => $fechaFin,
                ], 422);
            }
        }

        $kgVendidos = (float) $validated['kg_vendidos'];
        $precioPorKg = (float) $validated['precio_por_kg'];
        $totalVenta = round($kgVendidos * $precioPorKg, 2);

        // 2. Transacción de Base de Datos para registrar venta y actualizar la orden de cosecha
        $venta = DB::transaction(function () use ($validated, $user, $fincaId, $fecha, $kgVendidos, $precioPorKg, $totalVenta) {
            $ventaRecord = Venta::create([
                'finca_id' => $fincaId,
                'lote_id' => $validated['lote_id'] ?? null,
                'estanque_id' => $validated['estanque_id'] ?? null,
                'harvest_order_id' => $validated['harvest_order_id'] ?? null,
                'cliente' => $validated['cliente'],
                'kg_vendidos' => $kgVendidos,
                'precio_por_kg' => $precioPorKg,
                'total_venta' => $totalVenta,
                'forma_pago' => $validated['forma_pago'],
                'fecha' => $fecha->toDateString(),
                'caja_id' => $validated['caja_id'] ?? null,
                'user_id' => $user?->id,
                'notas' => $validated['notas'] ?? null,
            ]);

            // Si proviene de una orden de cosecha, actualizar su estado y liquidación
            if (! empty($validated['harvest_order_id'])) {
                $harvestOrder = HarvestOrder::find($validated['harvest_order_id']);
                if ($harvestOrder) {
                    $harvestOrder->customer_name = $validated['cliente'];
                    $harvestOrder->price_per_kg = $precioPorKg;
                    $harvestOrder->total_sale_amount = $totalVenta;
                    if ($harvestOrder->status !== HarvestOrder::STATUS_DESPACHADA) {
                        $harvestOrder->status = HarvestOrder::STATUS_DESPACHADA;
                    }
                    $harvestOrder->save();
                }
            }

            return $ventaRecord;
        });

        return response()->json([
            'message' => 'Venta registrada y liquidación comercial asentada exitosamente.',
            'data' => new VentaResource($venta->load(['estanque', 'harvestOrder', 'user'])),
        ], 201);
    }
}
