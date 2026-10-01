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
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        $traslados = TrasladoPeces::where('finca_id', $fincaId)
            ->with(['estanqueOrigen:id,name,code,biomass,fish_population,average_weight', 'estanqueDestino:id,name,code,biomass,fish_population,average_weight', 'user:id,name'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $estanques = Pond::where('finca_id', $fincaId)->orderBy('name')->get();
        $estanquesOrigen = $estanques->filter(fn ($p) => (int) $p->fish_population > 0);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Historial de desdobles y traslados recuperado con éxito.',
                'data' => $traslados,
                'estanques' => $estanques,
                'estanques_origen' => $estanquesOrigen->values(),
            ]);
        }

        return view('traslados.index', [
            'traslados' => $traslados,
            'estanques' => $estanques,
            'estanquesOrigen' => $estanquesOrigen,
        ]);
    }

    /**
     * Registro de desdoble / traslado de peces entre lagos con actualización atómica de biomasas.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Control de roles autorizados: propietario, tecnico_acuicola, operario_campo (y cargos directivos/operativos afines)
        if ($user && ! ($user->isOwner() || $user->isAdmin() || $user->hasRole(['propietario', 'tecnico_acuicola', 'operario_campo', 'operario_alimentador', 'trabajador', 'worker', 'administrador', 'admin']))) {
            abort(403, 'Acceso denegado: no tienes autorización para registrar traslados de peces.');
        }

        // Normalización de parámetros entrantes
        if (! $request->has('cantidad_peces_trasladados') && $request->has('cantidad_peces')) {
            $request->merge(['cantidad_peces_trasladados' => $request->input('cantidad_peces')]);
        }
        if (! $request->has('cantidad_peces_trasladados') && $request->has('cantidad')) {
            $request->merge(['cantidad_peces_trasladados' => $request->input('cantidad')]);
        }
        if (! $request->has('peso_promedio_gramos') && $request->has('peso_promedio')) {
            $request->merge(['peso_promedio_gramos' => $request->input('peso_promedio')]);
        }
        if (! $request->has('peso_promedio_gramos') && $request->has('peso')) {
            $request->merge(['peso_promedio_gramos' => $request->input('peso')]);
        }
        if (! $request->has('estanque_origen_id') && $request->has('lago_origen_id')) {
            $request->merge(['estanque_origen_id' => $request->input('lago_origen_id')]);
        }
        if (! $request->has('estanque_destino_id') && $request->has('lago_destino_id')) {
            $request->merge(['estanque_destino_id' => $request->input('lago_destino_id')]);
        }

        // Normalización de motivo
        if ($request->has('motivo')) {
            $motivoRaw = mb_strtolower(trim((string) $request->input('motivo')));
            $motivoNormalizado = match ($motivoRaw) {
                'desdoble por densidad/crecimiento', 'desdoble por densidad', 'desdoble_densidad' => 'Desdoble por densidad/crecimiento',
                'clasificación por tallas', 'clasificacion por tallas', 'clasificacion_tallas' => 'Clasificación por tallas',
                'mantenimiento/secado de estanque', 'mantenimiento de estanque', 'mantenimiento_estanque', 'limpieza_estanque' => 'Mantenimiento/secado de estanque',
                'sanitario' => 'Sanitario',
                default => $request->input('motivo'),
            };
            $request->merge(['motivo' => $motivoNormalizado]);
        }

        $validated = $request->validate([
            'estanque_origen_id' => ['required', 'exists:ponds,id', 'different:estanque_destino_id'],
            'estanque_destino_id' => ['required', 'exists:ponds,id'],
            'fecha' => ['nullable', 'date'],
            'cantidad_peces_trasladados' => ['required', 'integer', 'min:1'],
            'peso_promedio_gramos' => ['required', 'numeric', 'min:0.1'],
            'merma_traslado_peces' => ['nullable', 'integer', 'min:0'],
            'motivo' => ['required', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'estanque_origen_id.different' => 'El estanque destino debe ser obligatoriamente diferente al estanque origen.',
            'cantidad_peces_trasladados.min' => 'La cantidad de peces a trasladar debe ser un entero positivo.',
        ]);

        $fincaId = $user?->finca_id ?? 1;
        $cantidad = (int) $validated['cantidad_peces_trasladados'];
        $merma = (int) ($validated['merma_traslado_peces'] ?? 0);
        $pesoPromedio = (float) $validated['peso_promedio_gramos'];
        $fecha = $validated['fecha'] ?? now()->toDateString();
        $motivo = $validated['motivo'];

        // 1. Validar que ambos estanques pertenezcan a la misma finca (tenant)
        $origen = Pond::where('finca_id', $fincaId)->where('id', $validated['estanque_origen_id'])->first();
        $destino = Pond::where('finca_id', $fincaId)->where('id', $validated['estanque_destino_id'])->first();

        if (! $origen || ! $destino) {
            $msgTenant = 'Los estanques seleccionados deben pertenecer a la misma finca.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msgTenant, 'error' => 'tenant_mismatch'], 422);
            }

            return back()->withErrors(['estanque_origen_id' => $msgTenant])->withInput();
        }

        // 2. Validar que la cantidad no supere la población viva del estanque origen
        $mensajeExceso = 'La cantidad a trasladar no puede superar los peces vivos actuales del estanque de origen.';
        if ((int) $origen->fish_population < $cantidad) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $mensajeExceso,
                    'errors' => [
                        'cantidad_peces_trasladados' => [$mensajeExceso],
                        'cantidad' => [$mensajeExceso],
                    ],
                ], 422);
            }

            return back()->withErrors(['cantidad_peces_trasladados' => $mensajeExceso])->withInput();
        }

        // 3. Ejecución atómica en DB::transaction
        $resultado = DB::transaction(function () use ($validated, $user, $fincaId, $cantidad, $merma, $pesoPromedio, $fecha, $motivo, $mensajeExceso) {
            $origen = Pond::where('finca_id', $fincaId)->where('id', $validated['estanque_origen_id'])->lockForUpdate()->firstOrFail();
            $destino = Pond::where('finca_id', $fincaId)->where('id', $validated['estanque_destino_id'])->lockForUpdate()->firstOrFail();

            if ((int) $origen->fish_population < $cantidad) {
                return [
                    'success' => false,
                    'message' => $mensajeExceso,
                ];
            }

            // En Estanque Origen: fish_population -= cantidad
            $origen->fish_population = max(0, (int) $origen->fish_population - $cantidad);
            if ($origen->fish_population === 0) {
                $origen->status = 'Vacio';
                $origen->biomass = 0.0;
            } else {
                $origen->biomass = max(0.0, round(($origen->fish_population * (float) $origen->average_weight) / 1000, 2));
            }
            $origen->save();

            // En Estanque Destino: fish_population += cantidad y ponderación
            $pecesActualesDestino = (int) $destino->fish_population;
            $pesoActualDestino = (float) $destino->average_weight;
            $pecesNuevos = max(0, $cantidad - $merma);
            $totalPecesDestino = $pecesActualesDestino + $pecesNuevos;

            if ($pecesActualesDestino > 0) {
                // Recalcular peso promedio ponderado
                $pesoPonderado = round((($pecesActualesDestino * $pesoActualDestino) + ($pecesNuevos * $pesoPromedio)) / max(1, $totalPecesDestino), 2);
                $destino->fish_population = $totalPecesDestino;
                $destino->average_weight = $pesoPonderado;
            } else {
                // Estanque vacío: asignar peso ingresado y cambiar estado a Sembrado
                $destino->fish_population = $pecesNuevos;
                $destino->average_weight = $pesoPromedio;
                $destino->status = 'Sembrado';
                if (! $destino->stocked_at) {
                    $destino->stocked_at = $fecha;
                }
            }

            // Recalcular biomasa total del destino: biomass = (fish_population * average_weight) / 1000
            $destino->biomass = max(0.0, round(($destino->fish_population * (float) $destino->average_weight) / 1000, 2));
            $destino->save();

            // Registrar trazabilidad histórica en traslados_peces
            $traslado = TrasladoPeces::create([
                'finca_id' => $fincaId,
                'estanque_origen_id' => $origen->id,
                'estanque_destino_id' => $destino->id,
                'user_id' => $user?->id ?? 1,
                'fecha' => $fecha,
                'cantidad_peces_trasladados' => $cantidad,
                'peso_promedio_gramos' => $pesoPromedio,
                'merma_traslado_peces' => $merma,
                'motivo' => $motivo,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Registrar en bitácora de actividad
            if ($user) {
                ActividadTrabajador::registrar(
                    $user,
                    ActividadTrabajador::ACCION_TRASLADO,
                    "Traslado de {$cantidad} peces desde {$origen->name} hacia {$destino->name} (Peso: {$pesoPromedio} g, Motivo: {$motivo}).",
                    $destino->id
                );
            }

            return [
                'success' => true,
                'traslado' => $traslado,
                'origen' => $origen->fresh(),
                'destino' => $destino->fresh(),
            ];
        });

        if (! $resultado['success']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $resultado['message'],
                    'errors' => ['cantidad_peces_trasladados' => [$resultado['message']]],
                ], 422);
            }

            return back()->withErrors(['cantidad_peces_trasladados' => $resultado['message']])->withInput();
        }

        $msg = "Desdoble / Traslado de {$cantidad} peces procesado con éxito. Biomasas y poblaciones actualizadas.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $resultado['traslado']->load(['estanqueOrigen', 'estanqueDestino', 'user:id,name']),
                'origen' => $resultado['origen'],
                'destino' => $resultado['destino'],
            ], 201);
        }

        return redirect()->route('traslados.index')->with('success', $msg);
    }
}
