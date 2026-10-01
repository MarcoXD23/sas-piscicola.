<?php

namespace App\Http\Controllers;

use App\Http\Requests\LiquidarNominaRequest;
use App\Http\Requests\StorePersonalTemporalRequest;
use App\Http\Resources\LiquidacionSemanalResource;
use App\Models\LiquidacionSemanal;
use App\Models\PersonalTemporal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NominaController extends Controller
{
    /**
     * Listado de personal temporal y destajistas.
     */
    public function personalIndex(Request $request): JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;
        $personal = PersonalTemporal::where('finca_id', $fincaId)->orderBy('nombre')->get();

        return response()->json([
            'message' => 'Personal temporal recuperado exitosamente.',
            'total' => $personal->count(),
            'data' => $personal,
        ]);
    }

    /**
     * Registra un nuevo trabajador temporal o destajista.
     */
    public function storePersonal(StorePersonalTemporalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $fincaId = $request->user()?->finca_id ?? 1;

        $trabajador = PersonalTemporal::create([
            'finca_id' => $fincaId,
            'nombre' => $validated['nombre'],
            'documento' => $validated['documento'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'tipo_pago' => $validated['tipo_pago'],
            'tarifa' => (float) $validated['tarifa'],
            'deducciones' => (float) ($validated['deducciones'] ?? 0),
            'estado' => 'activo',
        ]);

        return response()->json([
            'message' => 'Trabajador temporal registrado exitosamente.',
            'data' => $trabajador,
        ], 201);
    }

    /**
     * Listado del histórico de liquidaciones semanales.
     */
    public function indexLiquidaciones(Request $request): JsonResponse
    {
        $query = LiquidacionSemanal::query()->with(['personalTemporal', 'user', 'liquidadoPor']);

        if ($request->filled('corte_sabado')) {
            $query->whereDate('corte_sabado', $request->corte_sabado);
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->tipo_pago);
        }

        $liquidaciones = $query->orderBy('corte_sabado', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Liquidaciones semanales recuperadas exitosamente.',
            'total_registros' => $liquidaciones->count(),
            'total_bruto' => round($liquidaciones->sum('total_bruto'), 2),
            'total_neto' => round($liquidaciones->sum('total_neto'), 2),
            'data' => LiquidacionSemanalResource::collection($liquidaciones),
        ]);
    }

    /**
     * Asienta la liquidación sabatina calculando automáticamente:
     * - Destajo: unidades (kg cosechados o faenas) × tarifa por kg/faena.
     * - Jornal: días trabajados × tarifa diaria de jornal.
     * Deduce anticipos o préstamos de pescado fiado y genera el neto a pagar.
     */
    public function liquidarSemana(LiquidarNominaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $unidades = (float) $validated['unidades_trabajadas'];
        $tarifa = (float) $validated['tarifa'];
        $deducciones = (float) ($validated['deducciones'] ?? 0);

        $totalBruto = round($unidades * $tarifa, 2);
        $totalNeto = max(0, round($totalBruto - $deducciones, 2));

        $corteSabado = Carbon::parse($validated['corte_sabado']);

        $liquidacion = DB::transaction(function () use ($validated, $user, $fincaId, $corteSabado, $unidades, $tarifa, $totalBruto, $deducciones, $totalNeto) {
            return LiquidacionSemanal::create([
                'finca_id' => $fincaId,
                'personal_temporal_id' => $validated['personal_temporal_id'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'corte_sabado' => $corteSabado->toDateString(),
                'tipo_pago' => $validated['tipo_pago'],
                'unidades_trabajadas' => $unidades,
                'tarifa' => $tarifa,
                'total_bruto' => $totalBruto,
                'deducciones' => $deducciones,
                'total_neto' => $totalNeto,
                'estado' => 'liquidado',
                'liquidado_por_user_id' => $user?->id,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Nómina sabatina liquidada exitosamente.',
            'resumen_financiero' => [
                'corte' => $corteSabado->toDateString(),
                'tipo_pago' => $validated['tipo_pago'],
                'unidades' => $unidades,
                'tarifa' => $tarifa,
                'total_bruto_cop' => $totalBruto,
                'deducciones_cop' => $deducciones,
                'total_neto_cop' => $totalNeto,
            ],
            'data' => new LiquidacionSemanalResource($liquidacion->load(['personalTemporal', 'user', 'liquidadoPor'])),
        ], 201);
    }
}
