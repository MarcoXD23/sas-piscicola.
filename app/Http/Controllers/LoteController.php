<?php

namespace App\Http\Controllers;

use App\Models\ActividadTrabajador;
use App\Models\Estanque;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoteController extends Controller
{
    /**
     * Almacena un nuevo lago / lote activo en la finca en una transacción DB.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'codigo_estanque' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
            'tipo_estanque' => ['required', 'string', 'max:100'],
            'especie_id' => ['required', 'exists:especies,id'],
            'fecha_siembra' => ['nullable', 'date'],
            'stocked_at' => ['nullable', 'date'],
            'cantidad_sembrada' => ['nullable', 'integer', 'min:1'],
            'fingerlings_stocked' => ['nullable', 'integer', 'min:1'],
            'peso_promedio_inicial' => ['nullable', 'numeric', 'min:0.01'],
            'average_weight' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $codigo = $validated['codigo_estanque'] ?? $validated['name'] ?? 'EST-'.time();
        $fechaSiembra = $validated['fecha_siembra'] ?? $validated['stocked_at'] ?? now()->toDateString();
        $cantidadSembrada = (int) ($validated['cantidad_sembrada'] ?? $validated['fingerlings_stocked'] ?? 1000);
        $pesoPromedioInicial = (float) ($validated['peso_promedio_inicial'] ?? $validated['average_weight'] ?? 5.0);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $pecesVivos = $cantidadSembrada;
        $biomasaKg = round(($pecesVivos * $pesoPromedioInicial) / 1000, 2);
        $diasCultivo = (int) now()->diffInDays(Carbon::parse($fechaSiembra));

        $estanque = DB::transaction(function () use ($fincaId, $codigo, $validated, $fechaSiembra, $pecesVivos, $pesoPromedioInicial, $biomasaKg, $user) {
            $lago = Estanque::create([
                'finca_id' => $fincaId,
                'name' => $codigo,
                'code' => $codigo,
                'tipo_estanque' => $validated['tipo_estanque'],
                'especie_id' => $validated['especie_id'],
                'stocked_at' => $fechaSiembra,
                'fingerlings_stocked' => $pecesVivos,
                'fish_population' => $pecesVivos,
                'average_weight' => $pesoPromedioInicial,
                'biomass' => $biomasaKg,
                'status' => 'Sembrado',
                'numero_lote' => 'LOTE-'.date('Y').'-'.$codigo,
            ]);

            if ($user) {
                ActividadTrabajador::registrar(
                    $user,
                    'siembra_lote',
                    "Siembra de {$pecesVivos} alevinos en lago {$codigo} (Biomasa: {$biomasaKg} kg).",
                    $lago->id
                );
            }

            return $lago;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Lote en lago {$estanque->name} creado exitosamente.",
                'data' => $estanque,
                'biomasa_kg' => $biomasaKg,
                'peces_vivos' => $pecesVivos,
                'dias_cultivo' => $diasCultivo,
            ], 201);
        }

        return redirect()->route('admin.lagos.index')
            ->with('status', "Lote {$estanque->name} registrado exitosamente. Biomasa inicial: {$biomasaKg} kg ({$diasCultivo} días de cultivo).");
    }
}
