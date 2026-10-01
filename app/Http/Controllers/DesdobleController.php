<?php

namespace App\Http\Controllers;

use App\Models\ActividadTrabajador;
use App\Models\Pond;
use App\Models\TrasladoPeces;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DesdobleController extends Controller
{
    /**
     * Vista de traslados y desdobles entre estanques.
     */
    public function index(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()?->finca_id ?? 1;

        $traslados = TrasladoPeces::where('finca_id', $fincaId)
            ->with(['estanqueOrigen:id,name,code,biomass,fish_population', 'estanqueDestino:id,name,code,biomass,fish_population', 'user:id,name'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $estanques = Pond::where('finca_id', $fincaId)->orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Historial de desdobles y traslados recuperado con éxito.',
                'data' => $traslados,
            ]);
        }

        return view('traslados.index', [
            'traslados' => $traslados,
            'estanques' => $estanques,
        ]);
    }

    /**
     * Registro de desdoble / traslado de peces entre lagos con actualización atómica de biomasas.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // Normalización de parámetros entrantes
        if (! $request->has('cantidad_peces_trasladados') && $request->has('cantidad_peces')) {
            $request->merge(['cantidad_peces_trasladados' => $request->input('cantidad_peces')]);
        }
        if (! $request->has('estanque_origen_id') && $request->has('lago_origen_id')) {
            $request->merge(['estanque_origen_id' => $request->input('lago_origen_id')]);
        }
        if (! $request->has('estanque_destino_id') && $request->has('lago_destino_id')) {
            $request->merge(['estanque_destino_id' => $request->input('lago_destino_id')]);
        }

        $validated = $request->validate([
            'estanque_origen_id' => ['required', 'exists:ponds,id', 'different:estanque_destino_id'],
            'estanque_destino_id' => ['required', 'exists:ponds,id'],
            'fecha' => ['nullable', 'date'],
            'cantidad_peces_trasladados' => ['required', 'integer', 'min:1'],
            'peso_promedio_gramos' => ['required', 'numeric', 'min:0.1'],
            'merma_traslado_peces' => ['nullable', 'integer', 'min:0'],
            'motivo' => ['nullable', 'string', 'in:desdoble_densidad,cambio_etapa,limpieza_estanque'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;
        $cantidad = (int) $validated['cantidad_peces_trasladados'];
        $merma = (int) ($validated['merma_traslado_peces'] ?? 0);
        $pesoPromedio = (float) $validated['peso_promedio_gramos'];
        $fecha = $validated['fecha'] ?? now()->toDateString();
        $motivo = $validated['motivo'] ?? TrasladoPeces::MOTIVO_DESDOBLE_DENSIDAD;

        $resultado = DB::transaction(function () use ($validated, $user, $fincaId, $cantidad, $merma, $pesoPromedio, $fecha, $motivo) {
            $origen = Pond::where('id', $validated['estanque_origen_id'])->lockForUpdate()->firstOrFail();
            $destino = Pond::where('id', $validated['estanque_destino_id'])->lockForUpdate()->firstOrFail();

            if ($origen->fish_population < $cantidad) {
                return [
                    'success' => false,
                    'message' => "El estanque de origen '{$origen->name}' solo dispone de "
                        .number_format($origen->fish_population).' peces para trasladar.',
                ];
            }

            // 1. Descontar peces del estanque origen y recalcular biomasa
            $origen->fish_population = max(0, (int) $origen->fish_population - $cantidad);
            if ($origen->fish_population === 0) {
                $origen->status = 'cosechado';
                $origen->biomass = 0.0;
            } else {
                $origen->biomass = max(0.0, round(($origen->fish_population * (float) $origen->average_weight) / 1000, 2));
            }
            $origen->save();

            // 2. Sumar peces al estanque destino descontando merma y recalcular biomasa
            $pecesEfectivos = max(0, $cantidad - $merma);
            $destino->fish_population = (int) $destino->fish_population + $pecesEfectivos;
            $destino->average_weight = $pesoPromedio;
            if (in_array(strtolower($destino->status ?? ''), ['vacio', 'inactivo', 'cosechado', 'limpieza', ''])) {
                $destino->status = 'Sembrado';
                if (! $destino->stocked_at) {
                    $destino->stocked_at = $fecha;
                }
            }
            $destino->biomass = max(0.0, round(($destino->fish_population * (float) $destino->average_weight) / 1000, 2));
            $destino->save();

            // 3. Crear registro histórico de traslado
            $traslado = TrasladoPeces::create([
                'finca_id' => $fincaId,
                'estanque_origen_id' => $origen->id,
                'estanque_destino_id' => $destino->id,
                'user_id' => $user->id,
                'fecha' => $fecha,
                'cantidad_peces_trasladados' => $cantidad,
                'peso_promedio_gramos' => $pesoPromedio,
                'merma_traslado_peces' => $merma,
                'motivo' => $motivo,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // 4. Registrar en bitácora de actividades del trabajador
            ActividadTrabajador::registrar(
                $user,
                ActividadTrabajador::ACCION_TRASLADO,
                "Desdoble de {$cantidad} peces desde {$origen->name} hacia {$destino->name} (Peso promedio: {$pesoPromedio} g).",
                $destino->id
            );

            return [
                'success' => true,
                'traslado' => $traslado,
                'origen' => $origen->fresh(),
                'destino' => $destino->fresh(),
            ];
        });

        if (! $resultado['success']) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $resultado['message'], 'error' => 'poblacion_insuficiente'], 422);
            }

            return back()->withErrors(['cantidad_peces_trasladados' => $resultado['message']])->withInput();
        }

        $msg = "Desdoble / Traslado de {$cantidad} peces procesado con éxito. Biomasas actualizadas en origen y destino.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $resultado['traslado']->load(['estanqueOrigen', 'estanqueDestino', 'user:id,name']),
            ], 201);
        }

        return redirect()->route('traslados.index')->with('success', $msg);
    }
}
