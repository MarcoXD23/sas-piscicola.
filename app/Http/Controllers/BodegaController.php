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

        $movimientos = MovimientoBodega::where('finca_id', $fincaId)
            ->with(['alimento', 'user'])
            ->latest()
            ->take(25)
            ->get();

        return view('bodega.index', [
            'alimentos' => $alimentos,
            'movimientos' => $movimientos,
            'totalBultos' => round($alimentos->sum('stock_bultos'), 1),
            'totalKilos' => round($alimentos->sum('stock_kilos_actual'), 1),
            'alertasStock' => $alimentos->filter(fn ($a) => $a->isBajoStock())->count(),
        ]);
    }

    /**
     * Recepción de camión o compra de concentrado (Entrada).
     */
    public function storeEntrada(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alimento_id' => ['required', 'exists:alimentos_bodega,id'],
            'cantidad_bultos' => ['required', 'numeric', 'min:0.5'],
            'costo_unitario_bulto' => ['nullable', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:150'],
            'fecha' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $fincaId = $user?->finca_id ?? 1;

        DB::transaction(function () use ($validated, $user, $fincaId) {
            $alimento = AlimentoBodega::where('id', $validated['alimento_id'])->lockForUpdate()->firstOrFail();
            $bultosEntrada = (float) $validated['cantidad_bultos'];
            $pesoBulto = (float) ($alimento->peso_bulto_kg ?: 40.00);
            $kilosEntrada = round($bultosEntrada * $pesoBulto, 2);

            $alimento->stock_bultos += $bultosEntrada;
            $alimento->stock_kilos_actual += $kilosEntrada;

            if (! empty($validated['costo_unitario_bulto'])) {
                $alimento->costo_unitario_bulto = (float) $validated['costo_unitario_bulto'];
            }

            $alimento->save();

            MovimientoBodega::create([
                'finca_id' => $fincaId,
                'alimento_id' => $alimento->id,
                'user_id' => $user?->id,
                'tipo_movimiento' => MovimientoBodega::TIPO_ENTRADA_COMPRA,
                'cantidad_bultos' => $bultosEntrada,
                'cantidad_kilos' => $kilosEntrada,
                'fecha' => $validated['fecha'] ?? now()->toDateString(),
                'proveedor' => $validated['proveedor'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
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
