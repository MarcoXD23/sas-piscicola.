<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedInventoryRequest;
use App\Models\FeedInventory;
use Illuminate\Http\JsonResponse;

class FeedInventoryController extends Controller
{
    /**
     * Display a listing of the feed inventory.
     */
    public function index(): JsonResponse
    {
        // El FincaScope asegura que solo veamos el inventario de la finca autenticada
        $inventories = FeedInventory::all();

        return response()->json([
            'message' => 'Inventario recuperado exitosamente.',
            'data' => $inventories,
        ]);
    }

    /**
     * Store a newly created feed inventory in storage.
     */
    public function store(StoreFeedInventoryRequest $request): JsonResponse
    {
        // BelongsToFinca automáticamente inyectará el finca_id del usuario
        $feed = FeedInventory::create($request->validated());

        return response()->json([
            'message' => 'Alimento registrado en el inventario exitosamente.',
            'data' => $feed,
        ], 201);
    }
}
