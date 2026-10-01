<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\PondSampling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PondSamplingController extends Controller
{
    /**
     * Listado de lagos con su información de siembra, tiempo transcurrido y muestreos.
     */
    public function pondsIndex(Request $request): JsonResponse
    {
        $ponds = Pond::query()
            ->with(['samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
            ->get()
            ->map(function (Pond $pond) {
                return [
                    'id' => $pond->id,
                    'code' => $pond->code,
                    'name' => $pond->name,
                    'fingerlings_stocked' => $pond->fingerlings_stocked,
                    'stocked_at' => $pond->stocked_at?->toDateString(),
                    'days_in_culture' => $pond->days_in_culture,
                    'months_in_culture' => $pond->months_in_culture,
                    'fish_population' => $pond->fish_population,
                    'average_weight_g' => (float) $pond->average_weight,
                    'biomass_kg' => (float) $pond->biomass,
                    'status' => $pond->status,
                    'ultimos_muestreos_count' => $pond->samplings->count(),
                    'ultimo_muestreo' => $pond->samplings->first(),
                ];
            });

        return response()->json([
            'message' => 'Lagos y métricas de cultivo recuperados exitosamente.',
            'total_lagos' => $ponds->count(),
            'data' => $ponds,
        ]);
    }

    /**
     * Historial de muestreos sabatinos.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PondSampling::query()
            ->with(['pond:id,name,code,fingerlings_stocked', 'registeredBy:id,name']);

        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('sampling_date', $request->date);
        }

        $samplings = $query->orderBy('sampling_date', 'desc')->get();

        return response()->json([
            'message' => 'Historial de muestreos sabatinos recuperado exitosamente.',
            'total' => $samplings->count(),
            'data' => $samplings,
        ]);
    }

    /**
     * Registro de muestreo sabatino semanal con cálculo automático de:
     * - Peso promedio (g) = (Peso total muestra kg * 1000) / Número peces
     * - Distribución y porcentaje de tallas (pequeña, mediana, grande, comercial)
     * - Estimación de biomasa total del lago
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'sampling_date' => ['nullable', 'date'],
            'sampled_fish_count' => ['required', 'integer', 'min:1'],
            'sample_total_weight_kg' => ['required', 'numeric', 'min:0.01'],
            'small_count' => ['nullable', 'integer', 'min:0'],
            'medium_count' => ['nullable', 'integer', 'min:0'],
            'large_count' => ['nullable', 'integer', 'min:0'],
            'commercial_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $samplingDate = $validated['sampling_date'] ?? now()->toDateString();
        $sampledCount = (int) $validated['sampled_fish_count'];
        $totalWeightKg = (float) $validated['sample_total_weight_kg'];

        $smallCount = (int) ($validated['small_count'] ?? 0);
        $mediumCount = (int) ($validated['medium_count'] ?? 0);
        $largeCount = (int) ($validated['large_count'] ?? 0);
        $commercialCount = (int) ($validated['commercial_count'] ?? 0);

        $sampling = PondSampling::create([
            'pond_id' => $validated['pond_id'],
            'sampling_date' => $samplingDate,
            'sampled_fish_count' => $sampledCount,
            'sample_total_weight_kg' => $totalWeightKg,
            'average_weight_g' => round(($totalWeightKg * 1000) / $sampledCount, 2),
            'small_count' => $smallCount,
            'medium_count' => $mediumCount,
            'large_count' => $largeCount,
            'commercial_count' => $commercialCount,
            'registered_by_user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $sampling->load(['pond:id,name,code,fingerlings_stocked', 'registeredBy:id,name']);

        return response()->json([
            'message' => 'Muestreo sabatino registrado y biomasa del lago actualizada exitosamente.',
            'distribucion_tallas' => [
                'pequena' => ['cantidad' => $sampling->small_count, 'porcentaje' => ((float) $sampling->small_percent).'%'],
                'mediana' => ['cantidad' => $sampling->medium_count, 'porcentaje' => ((float) $sampling->medium_percent).'%'],
                'grande' => ['cantidad' => $sampling->large_count, 'porcentaje' => ((float) $sampling->large_percent).'%'],
                'comercial' => ['cantidad' => $sampling->commercial_count, 'porcentaje' => ((float) $sampling->commercial_percent).'%'],
            ],
            'peso_promedio_g' => $sampling->average_weight_g,
            'biomasa_estimada_kg' => $sampling->biomass_estimate_kg,
            'data' => $sampling,
        ], 201);
    }

    /**
     * Detalle de un muestreo sabatino específico.
     */
    public function show(PondSampling $pondSampling): JsonResponse
    {
        $pondSampling->load(['pond', 'registeredBy:id,name']);

        return response()->json([
            'data' => $pondSampling,
        ]);
    }
}
