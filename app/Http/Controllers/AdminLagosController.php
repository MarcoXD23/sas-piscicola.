<?php

namespace App\Http\Controllers;

use App\Models\ActividadTrabajador;
use App\Models\Especie;
use App\Models\Estanque;
use App\Models\PondSampling;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminLagosController extends Controller
{
    /**
     * Muestra el panel maestro exclusivo de gestión de lagos, biomasa y conteo poblacional.
     * Solo accesible por Administrador y Jefe Mayor.
     */
    public function index(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()->finca_id ?? 1;

        $lagos = Estanque::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
            ->orderBy('name', 'asc')
            ->get();

        $especies = Especie::orderBy('nombre_comun', 'asc')->get();

        $totalBiomasa = round((float) $lagos->sum('biomass'), 2);
        $totalPecesVivos = (int) $lagos->sum('fish_population');
        $lagosListosPesca = $lagos->filter(fn ($e) => (float) $e->average_weight >= 450 || $e->status === 'En Cosecha');

        $data = [
            'lagos' => $lagos,
            'especies' => $especies,
            'lagos_listos_pesca' => $lagosListosPesca,
            'total_biomasa_kg' => $totalBiomasa,
            'total_peces_vivos' => $totalPecesVivos,
            'total_lagos_count' => $lagos->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.lagos.index', $data);
    }

    /**
     * Muestra la ficha técnica detallada de un lago específico.
     */
    public function show(int|string $id): View|JsonResponse
    {
        $lago = Estanque::with(['especiePrincipal', 'samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
            ->findOrFail($id);

        $data = [
            'lago' => $lago,
            'listo_pesca' => (float) $lago->average_weight >= 450 || $lago->status === 'En Cosecha',
            'muestreos' => $lago->samplings,
        ];

        if (request()->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.lagos.show', $data);
    }

    /**
     * Lista los muestreos biométricos sabatinos con filtros de fecha y estanque.
     */
    public function muestreosIndex(Request $request): View|JsonResponse
    {
        $fincaId = $request->user()->finca_id ?? 1;

        $lagos = Estanque::where('finca_id', $fincaId)->orderBy('name', 'asc')->get();

        $query = PondSampling::query()
            ->where('finca_id', $fincaId)
            ->with(['pond:id,name,code,fingerlings_stocked,fish_population', 'registeredBy:id,name']);

        if ($request->filled('pond_id')) {
            $query->where('pond_id', $request->pond_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('sampling_date', $request->date);
        }

        $muestreos = $query->orderBy('sampling_date', 'desc')->orderBy('id', 'desc')->get();

        $data = [
            'lagos' => $lagos,
            'muestreos' => $muestreos,
            'total_muestreos' => $muestreos->count(),
            'selected_pond_id' => $request->pond_id,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.muestreos.index', $data);
    }

    /**
     * Registrar nuevo muestreo biométrico sabatino y actualizar el peso promedio y biomasa del estanque.
     */
    public function storeMuestreo(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'sampling_date' => ['required', 'date'],
            'sampled_fish_count' => ['required', 'integer', 'min:1'],
            'sample_total_weight_kg' => ['required', 'numeric', 'min:0.01'],
            'small_count' => ['nullable', 'integer', 'min:0'],
            'medium_count' => ['nullable', 'integer', 'min:0'],
            'large_count' => ['nullable', 'integer', 'min:0'],
            'commercial_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $lago = Estanque::findOrFail($validated['pond_id']);

        $avgWeightGrams = round(($validated['sample_total_weight_kg'] * 1000) / $validated['sampled_fish_count'], 2);

        $muestreo = PondSampling::create([
            'finca_id' => $user->finca_id ?? $lago->finca_id ?? 1,
            'pond_id' => $lago->id,
            'sampling_date' => $validated['sampling_date'],
            'sampled_fish_count' => (int) $validated['sampled_fish_count'],
            'sample_total_weight_kg' => (float) $validated['sample_total_weight_kg'],
            'average_weight_g' => $avgWeightGrams,
            'small_count' => (int) ($validated['small_count'] ?? 0),
            'medium_count' => (int) ($validated['medium_count'] ?? 0),
            'large_count' => (int) ($validated['large_count'] ?? 0),
            'commercial_count' => (int) ($validated['commercial_count'] ?? 0),
            'registered_by_user_id' => $user->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Recalcular peso promedio y biomasa del lago
        $lago->average_weight = $avgWeightGrams;
        $lago->biomass = round(($lago->fish_population * $avgWeightGrams) / 1000, 2);
        $lago->save();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Muestreo sabatino registrado exitosamente para {$lago->name}. Peso promedio: {$avgWeightGrams} g.",
                'data' => $muestreo->load('pond:id,name', 'registeredBy:id,name'),
            ], 201);
        }

        return redirect()->route('admin.muestreos.index')
            ->with('status', "Muestreo sabatino registrado con éxito para {$lago->name}. Peso promedio actualizado a {$avgWeightGrams} g.");
    }

    /**
     * Registra un nuevo lago/estanque y su siembra inicial de alevinos.
     */
    /**
     * Registra un nuevo lago/estanque y su siembra inicial de alevinos.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // Normalización de parámetros entrantes
        if ($request->has('codigo_estanque')) {
            if (! $request->has('name')) {
                $request->merge(['name' => $request->input('codigo_estanque')]);
            }
            if (! $request->has('code')) {
                $request->merge(['code' => $request->input('codigo_estanque')]);
            }
        }
        $fechaSiembraInput = $request->input('stocked_at') ?? $request->input('stocking_date') ?? $request->input('fecha_siembra');
        if ($fechaSiembraInput) {
            $request->merge(['stocked_at' => $fechaSiembraInput]);
        }
        if ($request->has('cantidad_sembrada') && ! $request->has('fingerlings_stocked')) {
            $request->merge(['fingerlings_stocked' => $request->input('cantidad_sembrada')]);
        }
        if ($request->has('peso_promedio_inicial') && ! $request->has('average_weight')) {
            $request->merge(['average_weight' => $request->input('peso_promedio_inicial')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => ['nullable', 'string', 'max:50'],
            'tipo_estanque' => ['required', 'string', 'max:100'],
            'especie_id' => ['required', 'exists:especies,id'],
            'stocked_at' => ['required', 'date'],
            'fingerlings_stocked' => ['required', 'integer', 'min:1'],
            'average_weight' => ['required', 'numeric', 'min:0.01'],
            'numero_lote' => ['nullable', 'string', 'max:80'],
            'alevinera_origen' => ['nullable', 'string', 'max:150'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $pecesVivos = (int) $validated['fingerlings_stocked'];
        $pesoPromedio = (float) $validated['average_weight'];
        $biomasaInicial = round(($pecesVivos * $pesoPromedio) / 1000, 2);
        $fechaSiembra = Carbon::parse($validated['stocked_at']);
        $diasCultivo = (int) now()->diffInDays($fechaSiembra);

        $code = ! empty($validated['code'])
            ? $validated['code']
            : 'EST-'.str_pad((string) (Estanque::where('finca_id', $fincaId)->count() + 1), 2, '0', STR_PAD_LEFT);

        $numeroLote = ! empty($validated['numero_lote'])
            ? $validated['numero_lote']
            : 'LOTE-'.date('Y').'-'.$code;

        $estanque = DB::transaction(function () use ($fincaId, $validated, $code, $pecesVivos, $pesoPromedio, $biomasaInicial, $numeroLote, $user) {
            $nuevoEstanque = Estanque::create([
                'finca_id' => $fincaId,
                'name' => $validated['name'],
                'code' => $code,
                'tipo_estanque' => $validated['tipo_estanque'],
                'especie_id' => $validated['especie_id'],
                'stocked_at' => $validated['stocked_at'],
                'fingerlings_stocked' => $pecesVivos,
                'fish_population' => $pecesVivos,
                'average_weight' => $pesoPromedio,
                'biomass' => $biomasaInicial,
                'status' => 'Sembrado',
                'numero_lote' => $numeroLote,
                'alevinera_origen' => $validated['alevinera_origen'] ?? 'Origen Local',
            ]);

            if ($user) {
                ActividadTrabajador::registrar(
                    $user,
                    'siembra_lago',
                    "Siembra inicial registrada en lago {$nuevoEstanque->name} ({$code}): {$pecesVivos} alevinos sembrados, biomasa inicial de {$biomasaInicial} kg.",
                    $nuevoEstanque->id
                );
            }

            return $nuevoEstanque;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Lago {$estanque->name} registrado exitosamente con biomasa inicial de {$biomasaInicial} kg y {$diasCultivo} días de cultivo.",
                'data' => $estanque->load('especiePrincipal'),
                'dias_cultivo' => $diasCultivo,
            ], 201);
        }

        return redirect()->route('admin.lagos.index')
            ->with('status', "Lago {$estanque->name} ({$code}) registrado con éxito. Biomasa inicial: {$biomasaInicial} kg ({$diasCultivo} días de cultivo).");
    }
}
