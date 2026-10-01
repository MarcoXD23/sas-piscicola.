<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Finca::firstOrCreate(['id' => 1], [
            'nombre' => 'El SAS Piscícola',
            'codigo' => 'SAS-01',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ]);
    }

    /**
     * 1. GET /api/products -> Retorna la lista con código 200 OK.
     */
    public function test_can_list_all_products(): void
    {
        Product::create([
            'name' => 'Alimento Iniciación Mojarra',
            'description' => 'Bulto de concentrado 45% proteína',
            'price' => 125000.00,
            'stock' => 50,
            'finca_id' => 1,
        ]);

        Product::create([
            'name' => 'Malla Antipájaros 50x20m',
            'description' => 'Protección perimetral de estanque',
            'price' => 380000.50,
            'stock' => 12,
            'finca_id' => 1,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment([
            'name' => 'Alimento Iniciación Mojarra',
        ]);
        $response->assertJsonFragment([
            'name' => 'Malla Antipájaros 50x20m',
        ]);
    }

    /**
     * 2. POST /api/products -> Crea el producto y responde con 201 Created.
     */
    public function test_can_create_product_successfully(): void
    {
        $payload = [
            'name' => 'Aireador Splash 2HP',
            'description' => 'Motor eléctrico sumergible para oxigenación rápida',
            'price' => 1850000.00,
            'stock' => 6,
            'finca_id' => 1,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'Aireador Splash 2HP',
            'stock' => 6,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Aireador Splash 2HP',
            'stock' => 6,
        ]);
    }

    /**
     * 3. POST /api/products -> Valida campos requeridos y responde con 422 Unprocessable Entity.
     */
    public function test_create_product_fails_validation_with_missing_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    /**
     * 4. GET /api/products/{id} -> Retorna producto existente con 200 OK.
     */
    public function test_can_show_an_existing_product(): void
    {
        $product = Product::create([
            'name' => 'Kit Medidor de Oxígeno Digital',
            'description' => 'Sonda óptica con calibración automática',
            'price' => 950000.00,
            'stock' => 4,
            'finca_id' => 1,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $product->id,
            'name' => 'Kit Medidor de Oxígeno Digital',
            'stock' => 4,
        ]);
    }

    /**
     * 5. GET /api/products/{id} -> Retorna 404 Not Found si no existe.
     */
    public function test_show_product_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/products/999999');

        $response->assertStatus(404);
    }

    /**
     * 6. PUT/PATCH /api/products/{id} -> Actualiza el producto y retorna 200 OK.
     */
    public function test_can_update_product_successfully(): void
    {
        $product = Product::create([
            'name' => 'Atarraya Monofilamento',
            'description' => 'Muestreo de peces juveniles',
            'price' => 160000.00,
            'stock' => 8,
            'finca_id' => 1,
        ]);

        $updatePayload = [
            'name' => 'Atarraya Monofilamento Reforzada',
            'price' => 175000.00,
            'stock' => 15,
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updatePayload);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Atarraya Monofilamento Reforzada',
            'stock' => 15,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Atarraya Monofilamento Reforzada',
            'stock' => 15,
        ]);
    }

    /**
     * 7. DELETE /api/products/{id} -> Elimina producto y retorna 200 OK con mensaje.
     */
    public function test_can_delete_product_successfully(): void
    {
        $product = Product::create([
            'name' => 'Producto Temporal',
            'description' => 'Para prueba de eliminación',
            'price' => 50000.00,
            'stock' => 2,
            'finca_id' => 1,
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Producto eliminado',
        ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }
}

