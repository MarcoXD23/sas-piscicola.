<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlimentacionRequest;
use App\Http\Resources\AlimentacionResource;
use App\Models\FeedingLog;
use App\Models\FeedInventory;
use App\Models\InventarioAlimento;
use App\Models\Pond;
use App\Services\FeedingCalculationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlimentacionController extends Controller
{
    public function __construct(
        protected FeedingCalculationService $feedingService
    ) {}

    /**
     * Vista de Auditoría y Control de Alimentación (Propietario / Administrador).
     * Muestra: Fecha, Hora, Estanque/Lago, Kilos suministrados, Tipo de concentrado y Nombre del trabajador responsable.
     */
    public function historial(Request $request): View
    {
        $fincaId = $request->user()?->finca_id ?? 1;

        $query = FeedingLog::where('finca_id', $fincaId)
            ->with(['pond', 'user']);

        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('feeding_date', $request->fecha);
        }

        $logs = $query->orderBy('feeding_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        $ponds = Pond::where('finca_id', $fincaId)->orderBy('name')->get();
        $totalKilos = round((float) FeedingLog::where('finca_id', $fincaId)->sum('amount_kg'), 2);

        return view('admin.alimentacion.historial', [
            'logs' => $logs,
            'ponds' => $ponds,
            'totalKilos' => $totalKilos,
        ]);
    }

    /**
     * Listado de registros de alimentación.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FeedingLog::query()->with(['pond:id,name', 'user:id,name,role']);

        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('feeding_date', $request->fecha);
        }

        $logs = $query->orderBy('feeding_date', 'desc')->orderBy('id', 'desc')->paginate(25);

        return response()->json([
            'message' => 'Historial de alimentación recuperado exitosamente.',
            'total_kilos_suministrados' => round(FeedingLog::sum('amount_kg'), 2),
            'data' => AlimentacionResource::collection($logs),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Registra una ración de alimentación, DESCUENTA automáticamente el stock en una transacción DB,
     * y genera una alerta si el inventario cae por debajo de los días de consumo proyectados.
     */
    public function store(StoreAlimentacionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $pond = Pond::findOrFail($validated['pond_id']);
        $cantidadKg = (float) $validated['cantidad_kg'];
        $fecha = $validated['fecha'] ?? now()->toDateString();

        // 1. Localizar el registro de inventario de bodega correspondiente
        $alimentoBodega = null;
        if (! empty($validated['alimento_id'])) {
            $alimentoBodega = \App\Models\AlimentoBodega::where('finca_id', $fincaId)->find($validated['alimento_id']);
        }
        if (! $alimentoBodega && ! empty($validated['tipo_concentrado'])) {
            $alimentoBodega = \App\Models\AlimentoBodega::where('finca_id', $fincaId)
                ->where('nombre_concentrado', 'like', '%'.$validated['tipo_concentrado'].'%')
                ->first();
        }
        if (! $alimentoBodega) {
            $alimentoBodega = \App\Models\AlimentoBodega::where('finca_id', $fincaId)->first();
        }

        $alimento = null;
        if (! empty($validated['inventario_alimento_id'])) {
            $alimento = InventarioAlimento::find($validated['inventario_alimento_id']);
        } elseif (! empty($validated['tipo_concentrado'])) {
            $alimento = InventarioAlimento::where('finca_id', $fincaId)
                ->where('tipo_concentrado', 'like', '%'.$validated['tipo_concentrado'].'%')
                ->first();
        }

        if (! $alimento && $alimentoBodega) {
            $alimento = InventarioAlimento::firstOrCreate(
                [
                    'finca_id' => $fincaId,
                    'tipo_concentrado' => $alimentoBodega->nombre_concentrado,
                ],
                [
                    'proteina_porcentaje' => $alimentoBodega->proteina_porcentaje ?? 32.00,
                    'stock_actual_kg' => (float) $alimentoBodega->stock_kilos_actual,
                    'stock_minimo_alerta_kg' => 100.00,
                    'costo_unitario' => 0.00,
                ]
            );
        }

        if (! $alimento) {
            $alimento = InventarioAlimento::where('finca_id', $fincaId)->first();
        }

        // Si no existe aún en inventario_alimento ni en bodega
        if (! $alimento) {
            $alimento = InventarioAlimento::create([
                'finca_id' => $fincaId,
                'tipo_concentrado' => $validated['tipo_concentrado'] ?? 'Concentrado Comercial 32%',
                'proteina_porcentaje' => 32.00,
                'stock_actual_kg' => 0.00,
                'stock_minimo_alerta_kg' => 100.00,
                'costo_unitario' => 0.00,
            ]);
        }

        // 2. Transacción Atómica de Base de Datos para asegurar concurrencia y consistencia contable
        $resultado = DB::transaction(function () use ($pond, $alimento, $alimentoBodega, $cantidadKg, $fecha, $validated, $user, $fincaId) {
            // Bloqueo pesimista para evitar carreras entre operarios en campo
            $alimentoBloqueado = InventarioAlimento::where('id', $alimento->id)->lockForUpdate()->first();

            $stockActual = (float) $alimentoBloqueado->stock_actual_kg;
            $bodegaBloqueado = null;
            if ($alimentoBodega) {
                $bodegaBloqueado = \App\Models\AlimentoBodega::where('id', $alimentoBodega->id)->lockForUpdate()->first();
                if ($bodegaBloqueado) {
                    $stockActual = min($stockActual, (float) $bodegaBloqueado->stock_kilos_actual);
                }
            }

            if ($stockActual < $cantidadKg) {
                return [
                    'success' => false,
                    'error' => 'INSUFFICIENT_STOCK',
                    'stock_actual' => $stockActual,
                    'requerido' => $cantidadKg,
                ];
            }

            // Descontar stock de inventario_alimento
            $alimentoBloqueado->decrement('stock_actual_kg', $cantidadKg);

            // Sincronizar AlimentoBodega y registrar MovimientoBodega
            if ($bodegaBloqueado) {
                $pesoBulto = (float) ($bodegaBloqueado->peso_bulto_kg ?: 40.00);
                $bultosDescontar = round($cantidadKg / $pesoBulto, 2);
                $bodegaBloqueado->stock_kilos_actual = max(0, round((float) $bodegaBloqueado->stock_kilos_actual - $cantidadKg, 2));
                $bodegaBloqueado->stock_bultos = max(0, round((float) $bodegaBloqueado->stock_kilos_actual / $pesoBulto, 2));
                $bodegaBloqueado->save();

                \App\Models\MovimientoBodega::create([
                    'finca_id' => $fincaId,
                    'alimento_id' => $bodegaBloqueado->id,
                    'user_id' => $user?->id ?? 1,
                    'tipo_movimiento' => \App\Models\MovimientoBodega::TIPO_SALIDA_ALIMENTACION,
                    'cantidad_bultos' => $bultosDescontar,
                    'cantidad_kilos' => $cantidadKg,
                    'fecha' => $fecha,
                    'observaciones' => "Alimentación lago {$pond->name} ({$pond->code})",
                ]);
            }

            // También sincronizar con FeedInventory si existe modelo legacy
            $feedLegacy = FeedInventory::where('finca_id', $fincaId)->first();
            if ($feedLegacy && $feedLegacy->quantity_kg >= $cantidadKg) {
                $feedLegacy->decrement('quantity_kg', $cantidadKg);
            }

            // Crear registro de alimentación
            $log = FeedingLog::create([
                'finca_id' => $fincaId,
                'user_id' => $user?->id ?? 1,
                'pond_id' => $pond->id,
                'feed_inventory_id' => $feedLegacy?->id,
                'feeding_date' => $fecha,
                'amount_kg' => $cantidadKg,
                'feed_name' => $alimentoBloqueado->tipo_concentrado,
                'observations' => $validated['observaciones'] ?? null,
            ]);

            return [
                'success' => true,
                'log' => $log,
                'alimento' => $alimentoBloqueado->fresh(),
            ];
        });

        if (! $resultado['success']) {
            return response()->json([
                'message' => 'Stock insuficiente en bodega para suministrar esa cantidad.',
                'error' => 'INSUFFICIENT_STOCK',
                'errors' => [
                    'amount_kg' => ['Stock insuficiente en bodega para suministrar esa cantidad.'],
                    'cantidad_kg' => ['Stock insuficiente en bodega para suministrar esa cantidad.'],
                ],
                'alimento' => $alimento->tipo_concentrado,
                'stock_disponible_kg' => $resultado['stock_actual'],
                'cantidad_solicitada_kg' => $resultado['requerido'],
            ], 422);
        }

        $log = $resultado['log'];
        $alimentoActualizado = $resultado['alimento'];

        // Registrar en bitácora de trazabilidad de trabajadores
        if ($user) {
            \App\Models\ActividadTrabajador::registrar(
                $user,
                \App\Models\ActividadTrabajador::ACCION_ALIMENTACION,
                "Suministró {$cantidadKg} kg de alimento ({$alimentoActualizado->tipo_concentrado}) en estanque {$pond->name}.",
                $pond->id
            );
        }

        // 3. Proyección y evaluación de alerta mediante FeedingCalculationService
        $evaluacion = $this->feedingService->evaluateInventoryStatus($alimentoActualizado);

        return response()->json([
            'message' => 'Alimentación registrada exitosamente y stock de bodega descontado en transacción atómica.',
            'data' => new AlimentacionResource($log->load(['pond', 'user'])),
            'bodega' => [
                'alimento' => $alimentoActualizado->tipo_concentrado,
                'stock_actual_kg' => (float) $alimentoActualizado->stock_actual_kg,
                'descontado_kg' => $cantidadKg,
            ],
            'alerta_inventario' => $evaluacion,
        ], 201);
    }

    /**
     * Reporte del estado de inventario de alimentos y días restantes de consumo proyectados.
     */
    public function estadoBodega(Request $request): JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;
        $alimentos = InventarioAlimento::where('finca_id', $fincaId)->get();

        $reporte = $alimentos->map(function ($alimento) {
            return $this->feedingService->evaluateInventoryStatus($alimento);
        });

        return response()->json([
            'message' => 'Estado de inventario y proyección de agotamiento recuperados exitosamente.',
            'total_alimentos' => $reporte->count(),
            'alertas_activas' => $reporte->where('alerta_critica', true)->count(),
            'data' => $reporte,
        ]);
    }
}
