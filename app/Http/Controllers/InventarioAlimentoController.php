<?php

namespace App\Http\Controllers;

use App\Models\ActividadTrabajador;
use App\Models\AlimentoBodega;
use App\Models\IngresoAlimento;
use App\Models\InventarioAlimento;
use App\Models\MovimientoBodega;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventarioAlimentoController extends Controller
{
    /**
     * Vista de inventario de alimentos y recepción física.
     */
    public function index(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;

        $inventario = InventarioAlimento::where('finca_id', $fincaId)->get();
        $ingresos = IngresoAlimento::with('recibidoPor:id,name,role')
            ->orderBy('fecha_recepcion', 'desc')
            ->orderBy('id', 'desc')
            ->take(30)
            ->get();

        $alimentos = AlimentoBodega::where('finca_id', $fincaId)
            ->with(['movimientos' => fn ($q) => $q->latest()->take(10)])
            ->get();
        $movimientos = MovimientoBodega::where('finca_id', $fincaId)
            ->with(['alimento', 'user'])
            ->latest()
            ->take(25)
            ->get();

        $data = [
            'inventario' => $inventario,
            'ingresos' => $ingresos,
            'alimentos' => $alimentos,
            'movimientos' => $movimientos,
            'totalBultos' => round((float) $alimentos->sum('stock_bultos'), 1),
            'totalKilos' => round((float) $alimentos->sum('stock_kilos_actual'), 1),
            'alertasStock' => $alimentos->filter(fn ($a) => $a->isBajoStock())->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('bodega.index', $data);
    }

    /**
     * Recepción física de concentrado sin campos de costos monetarios.
     * Exclusivo para el Propietario.
     */
    public function ingresar(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'fecha_recepcion' => ['required', 'date'],
            'proveedor' => ['required', 'string', 'max:150'],
            'tipo_concentrado' => ['required', 'string', 'max:150'],
            'bultos_recibidos' => ['required', 'numeric', 'min:0.5'],
            'peso_bulto_kg' => ['nullable', 'numeric', 'min:1'],
            'lote_fabrica' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $bultos = (float) $validated['bultos_recibidos'];
        $pesoBulto = (float) ($validated['peso_bulto_kg'] ?? 40.00);
        $kilosTotales = round($bultos * $pesoBulto, 2);

        $ingreso = DB::transaction(function () use ($validated, $user, $fincaId, $bultos, $pesoBulto, $kilosTotales) {
            // 1. Guardar ingreso físico en bodega
            $ingreso = IngresoAlimento::create([
                'fecha_recepcion' => $validated['fecha_recepcion'],
                'proveedor' => $validated['proveedor'],
                'tipo_concentrado' => $validated['tipo_concentrado'],
                'bultos_recibidos' => $bultos,
                'peso_bulto_kg' => $pesoBulto,
                'kilos_totales' => $kilosTotales,
                'lote_fabrica' => $validated['lote_fabrica'] ?? null,
                'recibido_por' => $user->id,
            ]);

            // 2. Incrementar stock en inventario_alimento
            $inventario = InventarioAlimento::firstOrCreate(
                [
                    'finca_id' => $fincaId,
                    'tipo_concentrado' => $validated['tipo_concentrado'],
                ],
                [
                    'proteina_porcentaje' => 32.00,
                    'stock_actual_kg' => 0.00,
                    'stock_minimo_alerta_kg' => 100.00,
                    'costo_unitario' => 0.00,
                ]
            );
            $inventario->increment('stock_actual_kg', $kilosTotales);

            // 3. Sincronizar AlimentoBodega
            $alimentoBodega = AlimentoBodega::firstOrCreate(
                [
                    'finca_id' => $fincaId,
                    'nombre_concentrado' => $validated['tipo_concentrado'],
                ],
                [
                    'proteina_porcentaje' => 32,
                    'peso_bulto_kg' => $pesoBulto,
                    'stock_bultos' => 0,
                    'stock_kilos_actual' => 0,
                    'costo_unitario_bulto' => 0,
                ]
            );
            $alimentoBodega->increment('stock_bultos', $bultos);
            $alimentoBodega->increment('stock_kilos_actual', $kilosTotales);

            // 4. Registrar en movimientos_bodega (histórico de auditoría)
            MovimientoBodega::create([
                'finca_id' => $fincaId,
                'alimento_id' => $alimentoBodega->id,
                'user_id' => $user?->id,
                'tipo_movimiento' => MovimientoBodega::TIPO_ENTRADA_COMPRA,
                'cantidad_bultos' => $bultos,
                'cantidad_kilos' => $kilosTotales,
                'fecha' => $validated['fecha_recepcion'],
                'proveedor' => $validated['proveedor'],
                'observaciones' => ! empty($validated['lote_fabrica']) ? "Lote: {$validated['lote_fabrica']}" : 'Recepción física de concentrado',
            ]);

            // 5. Registrar en bitácora de actividades
            ActividadTrabajador::registrar(
                $user,
                ActividadTrabajador::ACCION_INGRESO_ALIMENTO,
                "Ingreso a bodega: {$bultos} bultos ({$kilosTotales} kg) de {$validated['tipo_concentrado']} (Proveedor: {$validated['proveedor']})."
            );

            return $ingreso;
        });

        $msg = "Ingreso de {$bultos} bultos ({$kilosTotales} kg) de {$validated['tipo_concentrado']} registrado exitosamente en bodega.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $ingreso,
            ], 201);
        }

        return redirect()->route('admin.inventario-alimento.index')->with('success', $msg);
    }
}
