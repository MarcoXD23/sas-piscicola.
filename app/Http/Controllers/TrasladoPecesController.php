<?php

namespace App\Models;

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\TrasladoPeces;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrasladoPecesController extends Controller
{
    /**
     * Muestra la vista de traslados y desdobles de peces.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $finca = $user->finca_segura;

        $traslados = TrasladoPeces::where('finca_id', $finca->id)
            ->with(['estanqueOrigen:id,name,code,biomass,fish_population', 'estanqueDestino:id,name,code,biomass,fish_population', 'user:id,name'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $estanques = Pond::where('finca_id', $finca->id)->orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Historial de traslados recuperado exitosamente.',
                'total' => $traslados->count(),
                'data' => $traslados,
            ]);
        }

        return view('traslados.index', [
            'finca' => $finca,
            'traslados' => $traslados,
            'estanques' => $estanques,
        ]);
    }

    /**
     * Registra y aplica un nuevo traslado / desdoble de peces.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'estanque_origen_id' => ['required', 'exists:ponds,id', 'different:estanque_destino_id'],
            'estanque_destino_id' => ['required', 'exists:ponds,id'],
            'fecha' => ['nullable', 'date'],
            'cantidad_peces_trasladados' => ['required', 'integer', 'min:1'],
            'peso_promedio_gramos' => ['required', 'numeric', 'min:0.1'],
            'merma_traslado_peces' => ['nullable', 'integer', 'min:0'],
            'motivo' => ['required', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $origen = Pond::where('finca_id', $fincaId)->where('id', $validated['estanque_origen_id'])->first();
        $destino = Pond::where('finca_id', $fincaId)->where('id', $validated['estanque_destino_id'])->first();

        if (! $origen || ! $destino) {
            $msgTenant = 'Los estanques seleccionados deben pertenecer a la misma finca.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msgTenant, 'error' => 'tenant_mismatch'], 422);
            }

            return back()->withErrors(['estanque_origen_id' => $msgTenant])->withInput();
        }

        $mensajeExceso = 'La cantidad a trasladar no puede superar los peces vivos actuales del estanque de origen.';
        if ((int) $origen->fish_population < (int) $validated['cantidad_peces_trasladados']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $mensajeExceso,
                    'errors' => ['cantidad_peces_trasladados' => [$mensajeExceso]],
                ], 422);
            }

            return back()->withErrors(['cantidad_peces_trasladados' => $mensajeExceso])->withInput();
        }

        $traslado = TrasladoPeces::create([
            'finca_id' => $fincaId,
            'estanque_origen_id' => $validated['estanque_origen_id'],
            'estanque_destino_id' => $validated['estanque_destino_id'],
            'user_id' => $user->id,
            'fecha' => $validated['fecha'],
            'cantidad_peces_trasladados' => (int) $validated['cantidad_peces_trasladados'],
            'peso_promedio_gramos' => (float) $validated['peso_promedio_gramos'],
            'merma_traslado_peces' => (int) ($validated['merma_traslado_peces'] ?? 0),
            'motivo' => $validated['motivo'],
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        // Aplicar la deducción y suma de peces y biomasa automáticamente
        $traslado->aplicarTraslado();

        $msgExito = "Traslado de {$traslado->cantidad_peces_trasladados} peces procesado con éxito. Biomasas actualizadas.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msgExito,
                'data' => $traslado->fresh(['estanqueOrigen', 'estanqueDestino', 'user:id,name']),
            ], 201);
        }

        return redirect()->route('traslados.index')->with('success', $msgExito);
    }
}
