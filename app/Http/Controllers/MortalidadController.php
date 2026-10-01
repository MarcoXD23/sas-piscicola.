<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMortalidadRequest;
use App\Http\Resources\MortalidadResource;
use App\Models\Pond;
use App\Models\RegistroMortalidad;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MortalidadController extends Controller
{
    /**
     * Listado histórico y bitácora de mortandades.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RegistroMortalidad::query()->with(['estanque:id,name,code,numero_lote,fish_population,average_weight,biomass', 'user:id,name,role']);

        if ($request->filled('estanque_id')) {
            $query->where('estanque_id', $request->estanque_id);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->filled('causa_probable')) {
            $query->where('causa_probable', $request->causa_probable);
        }

        $mortalidades = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'message' => 'Bitácora de mortalidades recuperada exitosamente.',
            'total_eventos' => $mortalidades->count(),
            'total_peces_bajas' => (int) $mortalidades->sum('cantidad_peces'),
            'data' => MortalidadResource::collection($mortalidades),
        ]);
    }

    /**
     * Registra un reporte de mortalidad, resta automáticamente los peces de la población activa
     * y recalcula la biomasa estimada del estanque en una transacción DB.
     */
    public function store(StoreMortalidadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $fecha = isset($validated['fecha']) ? Carbon::parse($validated['fecha']) : now();
        $cantidadBajas = (int) $validated['cantidad_peces'];

        $registro = DB::transaction(function () use ($validated, $user, $fincaId, $fecha, $cantidadBajas) {
            // Bloqueo pesimista para actualización atómica de población
            $estanque = Pond::where('id', $validated['estanque_id'])->lockForUpdate()->firstOrFail();

            // 1. Crear registro de mortalidad en bitácora
            $mortalidad = RegistroMortalidad::create([
                'finca_id' => $fincaId,
                'estanque_id' => $estanque->id,
                'user_id' => $user?->id ?? 1,
                'fecha' => $fecha->toDateString(),
                'cantidad_peces' => $cantidadBajas,
                'causa_probable' => $validated['causa_probable'],
                'metodo_disposicion' => $validated['metodo_disposicion'] ?? RegistroMortalidad::METODO_COMPOSTAJE,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // 2. Restar peces del lote / población viva
            $poblacionAnterior = (int) $estanque->fish_population;
            $estanque->fish_population = max(0, $poblacionAnterior - $cantidadBajas);

            if (! empty($estanque->fingerlings_stocked) && $estanque->fingerlings_stocked >= $cantidadBajas) {
                $estanque->fingerlings_stocked -= $cantidadBajas;
            }

            // 3. Recalcular biomasa estimada: (Población × Peso Promedio g) / 1000
            $estanque->updateBiomass();

            if ($user) {
                \App\Models\ActividadTrabajador::registrar(
                    $user,
                    \App\Models\ActividadTrabajador::ACCION_MORTALIDAD,
                    "Reportó mortalidad de {$cantidadBajas} peces en estanque {$estanque->name}. Causa: {$mortalidad->causa_probable}",
                    $estanque->id
                );
            }

            return $mortalidad;
        });

        $registro->load(['estanque', 'user']);

        return response()->json([
            'message' => 'Reporte de mortalidad registrado correctamente. Población y biomasa actualizadas en estanque.',
            'data' => new MortalidadResource($registro),
        ], 201);
    }
}
