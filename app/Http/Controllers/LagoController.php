<?php

namespace App\Http\Controllers;

use App\Models\Especie;
use App\Models\Lago;
use App\Models\PondSampling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LagoController extends Controller
{
    /**
     * Lista todos los lagos y estanques de la finca.
     */
    public function index(Request $request): View|JsonResponse
    {
        $fincaId = auth()->user()->finca_id ?? 1;

        $lagos = Lago::where('finca_id', $fincaId)
            ->with(['especiePrincipal', 'samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
            ->orderBy('name', 'asc')
            ->get();

        $totalBiomasa = round((float) $lagos->sum('biomass'), 2);
        $totalPecesVivos = (int) $lagos->sum('fish_population');
        $lagosListosPesca = $lagos->filter(fn ($e) => (float) $e->average_weight >= 450 || $e->status === 'En Cosecha');

        $data = [
            'lagos' => $lagos,
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
     * Formulario de creación de un nuevo lago.
     */
    public function create(): View
    {
        $especies = Especie::where('activa', true)->orderBy('nombre_comun')->get();

        return view('admin.lagos.create', compact('especies'));
    }

    /**
     * Guarda un nuevo lago o estanque en la finca.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'especie_id' => ['nullable', 'exists:especies,id'],
            'fingerlings_stocked' => ['required', 'integer', 'min:0'],
            'average_weight' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
            'stocked_at' => ['nullable', 'date'],
        ]);

        $fincaId = auth()->user()->finca_id ?? 1;
        $biomass = round(($validated['fingerlings_stocked'] * $validated['average_weight']) / 1000, 2);

        $lago = Lago::create([
            'finca_id' => $fincaId,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? 'L-'.time(),
            'especie_id' => $validated['especie_id'] ?? null,
            'fingerlings_stocked' => $validated['fingerlings_stocked'],
            'fish_population' => $validated['fingerlings_stocked'],
            'average_weight' => $validated['average_weight'],
            'biomass' => $biomass,
            'status' => $validated['status'] ?? 'Sembrado',
            'stocked_at' => $validated['stocked_at'] ?? now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Lago creado exitosamente.', 'lago' => $lago], 201);
        }

        return redirect()->route('admin.lagos.index')
            ->with('success', "Lago {$lago->name} creado exitosamente.");
    }

    /**
     * Ficha técnica detallada de un lago.
     */
    public function show(int|string $id): View|JsonResponse
    {
        $lago = Lago::with(['especiePrincipal', 'samplings' => fn ($q) => $q->orderBy('sampling_date', 'desc')])
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
     * Formulario de edición de un lago.
     */
    public function edit(int|string $id): View
    {
        $lago = Lago::findOrFail($id);
        $especies = Especie::where('activa', true)->orderBy('nombre_comun')->get();

        return view('admin.lagos.edit', compact('lago', 'especies'));
    }

    /**
     * Actualiza la información de un lago.
     */
    public function update(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $lago = Lago::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'especie_id' => ['nullable', 'exists:especies,id'],
            'fish_population' => ['required', 'integer', 'min:0'],
            'average_weight' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $lago->name = $validated['name'];
        if (isset($validated['code'])) {
            $lago->code = $validated['code'];
        }
        if (isset($validated['especie_id'])) {
            $lago->especie_id = $validated['especie_id'];
        }
        $lago->fish_population = $validated['fish_population'];
        $lago->average_weight = $validated['average_weight'];
        $lago->biomass = round(($validated['fish_population'] * $validated['average_weight']) / 1000, 2);
        if (isset($validated['status'])) {
            $lago->status = $validated['status'];
        }
        $lago->save();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Lago actualizado exitosamente.', 'lago' => $lago]);
        }

        return redirect()->route('admin.lagos.index')
            ->with('success', "Lago {$lago->name} actualizado exitosamente.");
    }

    /**
     * Elimina un lago.
     */
    public function destroy(int|string $id): RedirectResponse|JsonResponse
    {
        $lago = Lago::findOrFail($id);
        $nombre = $lago->name;
        $lago->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => "Lago {$nombre} eliminado exitosamente."]);
        }

        return redirect()->route('admin.lagos.index')
            ->with('success', "Lago {$nombre} eliminado exitosamente.");
    }

    /**
     * Lista los muestreos biométricos sabatinos con filtros de fecha y estanque.
     */
    public function muestreosIndex(Request $request): View|JsonResponse
    {
        $fincaId = auth()->user()->finca_id ?? 1;

        $lagos = Lago::where('finca_id', $fincaId)->orderBy('name', 'asc')->get();

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
        $lago = Lago::findOrFail($validated['pond_id']);

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
}
