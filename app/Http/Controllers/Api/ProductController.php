<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Muestra la lista de productos (GET /api/products).
     * Retorna código 200 OK.
     */
    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json($products, 200);
    }

    /**
     * Almacena un nuevo producto en la base de datos (POST /api/products).
     * Valida campos obligatorios y retorna 201 Created (o 422 si falla validación).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'finca_id' => 'nullable|exists:fincas,id',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    /**
     * Muestra los detalles de un producto específico (GET /api/products/{product}).
     * Retorna código 200 OK (o 404 Not Found si no existe).
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product, 200);
    }

    /**
     * Actualiza un producto existente (PUT/PATCH /api/products/{product}).
     * Valida campos con regla sometimes|required y retorna 200 OK.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'finca_id' => 'nullable|exists:fincas,id',
        ]);

        $product->update($validated);

        return response()->json($product, 200);
    }

    /**
     * Elimina un producto de la base de datos (DELETE /api/products/{product}).
     * Retorna código 200 OK con mensaje de confirmación.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado',
        ], 200);
    }
}

