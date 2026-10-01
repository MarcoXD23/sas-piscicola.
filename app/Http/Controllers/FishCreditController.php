<?php

namespace App\Http\Controllers;

use App\Models\FishCredit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FishCreditController extends Controller
{
    /**
     * Listado de registros de pescado fiado.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FishCredit::query()->with(['user:id,name,role,employment_type', 'registeredBy:id,name']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $credits = $query->orderBy('credit_date', 'desc')->get();

        return response()->json([
            'message' => 'Registros de pescado fiado recuperados.',
            'total' => $credits->count(),
            'total_kilos' => round((float) $credits->sum('kilos'), 2),
            'total_dinero' => round((float) $credits->sum('total_amount'), 2),
            'data' => $credits,
        ]);
    }

    /**
     * Registrar entrega de pescado fiado a un trabajador a tarifa de $7.000 COP/kg:
     * - Destajo semanal: se marcará para deducir en la nómina del sábado.
     * - Fijo: se acumula para el cierre mensual.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'kilos' => ['required', 'numeric', 'min:0.1'],
            'credit_date' => ['nullable', 'date'],
            'price_per_kg' => ['nullable', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $kilos = (float) $validated['kilos'];
        $price = (float) ($validated['price_per_kg'] ?? FishCredit::DEFAULT_PRICE_PER_KG);
        $totalAmount = round($kilos * $price, 2);

        $credit = FishCredit::create([
            'user_id' => $user->id,
            'credit_date' => $validated['credit_date'] ?? now()->toDateString(),
            'kilos' => $kilos,
            'price_per_kg' => $price,
            'total_amount' => $totalAmount,
            'status' => FishCredit::STATUS_PENDIENTE,
            'registered_by_user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? 'Entrega de pescado fiado a trabajador',
        ]);

        return response()->json([
            'message' => "Pescado fiado ({$kilos} kg = $ {$totalAmount}) registrado para {$user->name}.",
            'modalidad_descuento' => $user->isDestajoSemanal()
                ? 'Se restará automáticamente de la liquidación del sábado'
                : 'Se acumulará al saldo para el cierre mensual',
            'data' => $credit->load('user:id,name,employment_type', 'registeredBy:id,name'),
        ], 201);
    }
}
