<?php

namespace App\Http\Controllers;

use App\Models\AlimentoBodega;
use App\Models\MovimientoBodega;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BodegaController extends Controller
{
    /**
     * Pantalla principal de bodega y control de concentrados.
     */
    public function index(Request $request): View
    {
        $fincaId = $request->user()?->finca_id ?? 1;

        $alimentos = AlimentoBodega::where('finca_id', $fincaId)
            ->with(['movimientos' => function ($q) {
                $q->latest()->take(10);
            }])
            ->get();

        $despachosPendientes = MovimientoBodega::where('finca_id', $fincaId)
            ->whereIn('tipo_movimiento', [
                'despacho_en_transito',
                'en_transito',
                'pendiente_recepcion',
                'despacho_pendiente',
            ])
            ->with(['alimento', 'user'])
            ->latest()
            ->get();

        $movimientos = MovimientoBodega::where('finca_id', $fincaId)
            ->with(['alimento', 'user'])
            ->latest()
            ->take(25)
            ->get();

        return view('bodega.index', [
            'alimentos' => $alimentos,
            'despachosPendientes' => $despachosPendientes,
            'movimientos' => $movimientos,
            'totalBultos' => round($alimentos->sum('stock_bultos'), 1),
            'totalKilos' => round($alimentos->sum('stock_kilos_actual'), 1),
            'alertasStock' => $alimentos->filter(fn ($a) => $a->isBajoStock())->count(),
        ]);
    }

    /**
     * Registro de Despacho de Alimento a Finca (Exclusivo Propietario).
     * El despacho NO suma stock hasta que el Administrador confirme físicamente en bodega.
     * PROHIBIDO: Sin campos de costo unitario, precios ni valores monetarios.
     */
    public function storeDespacho(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Verificar que solo el propietario o cargos directivos puedan despachar
        if ($user && ! ($user->isPropietario() || $user->isOwner() || $user->hasRole(['propietario', 'owner', 'jefe_mayor']))) {
            abort(403, 'Acceso denegado: solo el propietario tiene autorización para despachar alimento a la finca.');
        }

        // Rechazar campos de costos o precios si son enviados
        if ($request->hasAny(['costo_unitario', 'precio', 'valor_total', 'costo', 'valor'])) {
            return response()->json([
                'message' => 'El control de despacho es 100% de inventario físico. No se permiten campos monetarios.',
            ], 422);
        }

        if (! $request->has('cantidad_bultos') && $request->has('bultos_despachados')) {
            $request->merge(['cantidad_bultos' => $request->input('bultos_despachados')]);
        }
        if (! $request->has('cantidad_bultos') && $request->has('bultos')) {
            $request->merge(['cantidad_bultos' => $request->input('bultos')]);
        }
        if (! $request->has('fecha') && $request->has('fecha_despacho')) {
            $request->merge(['fecha' => $request->input('fecha_despacho')]);
        }

        $validated = $request->validate([
            'fecha' => ['nullable', 'date'],
            'proveedor' => ['required', 'string', 'max:150'],
            'tipo_concentrado' => ['required', 'string', 'max:150'],
            'cantidad_bultos' => ['required', 'numeric', 'min:0.5'],
            'peso_bulto_kg' => ['nullable', 'numeric', 'min:1'],
            'lote_fabrica' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $fincaId = $user?->finca_id ?? 1;
        $bultos = (float) $validated['cantidad_bultos'];
        $pesoBulto = (float) ($validated['peso_bulto_kg'] ?? 40.00);
        $kilos = round($bultos * $pesoBulto, 2);
        $fecha = $validated['fecha'] ?? now()->toDateString();

        // 1. Localizar o registrar el tipo de concentrado con 0 stock (NO se suma stock aún)
        $alimento = AlimentoBodega::firstOrCreate(
            [
                'finca_id' => $fincaId,
                'nombre_concentrado' => $validated['tipo_concentrado'],
            ],
            [
                'proteina_porcentaje' => 32,
                'peso_bulto_kg' => $pesoBulto,
                'stock_bultos' => 0,
                'stock_kilos_actual' => 0,
                'umbral_alerta_bultos' => 10,
                'costo_unitario_bulto' => 0,
            ]
        );

        $obs = $validated['observaciones'] ?? null;
        if (! empty($validated['lote_fabrica'])) {
            $obs = $obs ? $obs.' | Lote: '.$validated['lote_fabrica'] : 'Lote: '.$validated['lote_fabrica'];
        }

        // 2. Registrar el movimiento en tránsito (NO afecta stock)
        $movimiento = MovimientoBodega::create([
            'finca_id' => $fincaId,
            'alimento_id' => $alimento->id,
            'user_id' => $user?->id,
            'tipo_movimiento' => 'despacho_en_transito',
            'cantidad_bultos' => $bultos,
            'cantidad_kilos' => $kilos,
            'fecha' => $fecha,
            'proveedor' => $validated['proveedor'],
            'observaciones' => $obs,
        ]);

        $mensaje = "Despacho de {$bultos} bultos ({$kilos} kg) de {$validated['tipo_concentrado']} registrado en tránsito. Pendiente de confirmación física en bodega.";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $mensaje,
                'movimiento' => $movimiento,
                'stock_disponible_kilos' => (float) $alimento->fresh()->stock_kilos_actual,
            ], 201);
        }

        return redirect()->route('admin.bodega.index')->with('success', $mensaje);
    }

    /**
     * Confirmación física de recepción en bodega por el Administrador de Finca.
     * Solo al confirmar se incrementa el stock disponible en bodega dentro de una transacción.
     */
    public function confirmarRecepcion(Request $request, int|string $id): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        // Administrador o Propietario pueden confirmar
        if ($user && ! ($user->isAdmin() || $user->isPropietario() || $user->hasRole(['administrador', 'admin', 'propietario', 'owner', 'jefe_mayor', 'jefe_finca', 'jefe']))) {
            abort(403, 'Acceso denegado: solo el administrador o propietario pueden confirmar la recepción física en bodega.');
        }

        $movimiento = MovimientoBodega::where('finca_id', $fincaId)
            ->where('id', $id)
            ->firstOrFail();

        if (! in_array($movimiento->tipo_movimiento, ['despacho_en_transito', 'en_transito', 'pendiente_recepcion', 'despacho_pendiente'], true)) {
            $msg = 'Este despacho ya fue confirmado previamente o no se encuentra en estado pendiente.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->route('admin.bodega.index')->with('error', $msg);
        }

        // Bultos confirmados (por defecto los despachados, permitiendo ajuste físico si se cuenta)
        $bultosConfirmados = $request->filled('bultos_confirmados')
            ? (float) $request->input('bultos_confirmados')
            : (float) $movimiento->cantidad_bultos;

        $resultado = DB::transaction(function () use ($movimiento, $bultosConfirmados, $user, $fincaId) {
            $movimientoBloqueado = MovimientoBodega::where('id', $movimiento->id)->lockForUpdate()->firstOrFail();

            $alimento = AlimentoBodega::where('id', $movimientoBloqueado->alimento_id)
                ->where('finca_id', $fincaId)
                ->lockForUpdate()
                ->firstOrFail();

            $pesoBulto = (float) ($alimento->peso_bulto_kg ?: 40.00);
            $kilosConfirmados = round($bultosConfirmados * $pesoBulto, 2);

            // 1. Sumar bultos y kilos al stock de bodega
            $alimento->stock_bultos = round((float) $alimento->stock_bultos + $bultosConfirmados, 2);
            $alimento->stock_kilos_actual = round((float) $alimento->stock_kilos_actual + $kilosConfirmados, 2);
            $alimento->save();

            // 2. Sincronizar InventarioAlimento
            $inv = \App\Models\InventarioAlimento::firstOrCreate(
                [
                    'finca_id' => $fincaId,
                    'tipo_concentrado' => $alimento->nombre_concentrado,
                ],
                [
                    'proteina_porcentaje' => $alimento->proteina_porcentaje ?? 32.00,
                    'stock_actual_kg' => 0.00,
                    'stock_minimo_alerta_kg' => 100.00,
                    'costo_unitario' => 0.00,
                ]
            );
            $inv->increment('stock_actual_kg', $kilosConfirmados);

            // 3. Sincronizar FeedInventory si existe
            $feedLegacy = \App\Models\FeedInventory::where('finca_id', $fincaId)->first();
            if ($feedLegacy) {
                $feedLegacy->increment('quantity_kg', $kilosConfirmados);
            }

            // 4. Actualizar estado del movimiento a entrada oficial confirmada
            $movimientoBloqueado->tipo_movimiento = MovimientoBodega::TIPO_ENTRADA_COMPRA;
            $movimientoBloqueado->cantidad_bultos = $bultosConfirmados;
            $movimientoBloqueado->cantidad_kilos = $kilosConfirmados;
            $obsActual = $movimientoBloqueado->observaciones;
            $movimientoBloqueado->observaciones = $obsActual
                ? $obsActual.' | Recepción física confirmada por '.($user?->name ?? 'Administrador')
                : 'Recepción física confirmada por '.($user?->name ?? 'Administrador');
            $movimientoBloqueado->save();

            // 5. Registrar en bitácora de actividad
            if ($user) {
                \App\Models\ActividadTrabajador::registrar(
                    $user,
                    \App\Models\ActividadTrabajador::ACCION_INGRESO_ALIMENTO,
                    "Confirmación en bodega: {$bultosConfirmados} bultos ({$kilosConfirmados} kg) de {$alimento->nombre_concentrado} (Proveedor: {$movimientoBloqueado->proveedor})."
                );
            }

            return [
                'alimento' => $alimento->fresh(),
                'movimiento' => $movimientoBloqueado,
                'kilos_sumados' => $kilosConfirmados,
            ];
        });

        $msg = "Recepción de {$resultado['movimiento']->cantidad_bultos} bultos ({$resultado['kilos_sumados']} kg) confirmada exitosamente en bodega.";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'stock_actual_kilos' => (float) $resultado['alimento']->stock_kilos_actual,
                'stock_actual_bultos' => (float) $resultado['alimento']->stock_bultos,
            ]);
        }

        return redirect()->route('admin.bodega.index')->with('success', $msg);
    }

    /**
     * Recepción de camión o compra de concentrado (Entrada).
     */
    public function storeEntrada(Request $request): RedirectResponse
    {
        // Soporte unificado de inputs físicos
        if (! $request->has('cantidad_bultos') && $request->has('bultos_recibidos')) {
            $request->merge(['cantidad_bultos' => $request->input('bultos_recibidos')]);
        }
        if (! $request->has('fecha') && $request->has('fecha_recepcion')) {
            $request->merge(['fecha' => $request->input('fecha_recepcion')]);
        }

        $validated = $request->validate([
            'alimento_id' => ['nullable', 'exists:alimentos_bodega,id'],
            'tipo_concentrado' => ['nullable', 'string', 'max:150'],
            'cantidad_bultos' => ['required', 'numeric', 'min:0.5'],
            'peso_bulto_kg' => ['nullable', 'numeric', 'min:1'],
            'costo_unitario_bulto' => ['nullable', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'fecha' => ['nullable', 'date'],
            'lote_fabrica' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        DB::transaction(function () use ($validated, $user, $fincaId) {
            $alimento = null;
            if (! empty($validated['alimento_id'])) {
                $alimento = AlimentoBodega::where('id', $validated['alimento_id'])
                    ->where('finca_id', $fincaId)
                    ->lockForUpdate()
                    ->firstOrFail();
            } elseif (! empty($validated['tipo_concentrado'])) {
                $alimento = AlimentoBodega::where('finca_id', $fincaId)
                    ->where('nombre_concentrado', $validated['tipo_concentrado'])
                    ->lockForUpdate()
                    ->first();

                if (! $alimento) {
                    $alimento = AlimentoBodega::create([
                        'finca_id' => $fincaId,
                        'nombre_concentrado' => $validated['tipo_concentrado'],
                        'proteina_porcentaje' => 32,
                        'peso_bulto_kg' => (float) ($validated['peso_bulto_kg'] ?? 40.00),
                        'stock_bultos' => 0,
                        'stock_kilos_actual' => 0,
                        'costo_unitario_bulto' => 0,
                    ]);
                }
            } else {
                $alimento = AlimentoBodega::where('finca_id', $fincaId)->lockForUpdate()->firstOrFail();
            }

            $bultosEntrada = (float) $validated['cantidad_bultos'];
            $pesoBulto = (float) ($validated['peso_bulto_kg'] ?? ($alimento->peso_bulto_kg ?: 40.00));
            $kilosEntrada = round($bultosEntrada * $pesoBulto, 2);

            $alimento->stock_bultos += $bultosEntrada;
            $alimento->stock_kilos_actual += $kilosEntrada;
            $alimento->save();

            $obs = $validated['observaciones'] ?? null;
            if (! empty($validated['lote_fabrica'])) {
                $obs = $obs ? $obs.' | Lote: '.$validated['lote_fabrica'] : 'Lote: '.$validated['lote_fabrica'];
            }

            MovimientoBodega::create([
                'finca_id' => $fincaId,
                'alimento_id' => $alimento->id,
                'user_id' => $user?->id,
                'tipo_movimiento' => MovimientoBodega::TIPO_ENTRADA_COMPRA,
                'cantidad_bultos' => $bultosEntrada,
                'cantidad_kilos' => $kilosEntrada,
                'fecha' => $validated['fecha'] ?? now()->toDateString(),
                'proveedor' => $validated['proveedor'] ?? null,
                'observaciones' => $obs,
            ]);
        });

        return redirect()->route('admin.bodega.index')
            ->with('success', 'Entrada de concentrado registrada exitosamente en bodega.');
    }

    /**
     * Ajuste físico por conteo de inventario o merma por humedad/rotura.
     */
    public function ajuste(Request $request, int|string $id): RedirectResponse
    {
        $validated = $request->validate([
            'stock_bultos' => ['required', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        DB::transaction(function () use ($validated, $id, $user, $fincaId) {
            $alimento = AlimentoBodega::where('id', $id)->lockForUpdate()->firstOrFail();
            $stockActual = (float) $alimento->stock_bultos;
            $nuevoStock = (float) $validated['stock_bultos'];
            $diferenciaBultos = round($stockActual - $nuevoStock, 2);
            $pesoBulto = (float) ($alimento->peso_bulto_kg ?: 40.00);
            $diferenciaKilos = round($diferenciaBultos * $pesoBulto, 2);

            $alimento->stock_bultos = $nuevoStock;
            $alimento->stock_kilos_actual = round($nuevoStock * $pesoBulto, 2);
            $alimento->save();

            MovimientoBodega::create([
                'finca_id' => $fincaId,
                'alimento_id' => $alimento->id,
                'user_id' => $user?->id,
                'tipo_movimiento' => MovimientoBodega::TIPO_AJUSTE_MERMA,
                'cantidad_bultos' => abs($diferenciaBultos),
                'cantidad_kilos' => abs($diferenciaKilos),
                'fecha' => now()->toDateString(),
                'observaciones' => $validated['motivo'] ?? 'Ajuste de inventario físico',
            ]);
        });

        return redirect()->route('admin.bodega.index')
            ->with('success', 'Ajuste de stock asentado exitosamente.');
    }
}
