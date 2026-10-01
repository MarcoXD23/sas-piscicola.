<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feed_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->string('name'); // Ej: Inicio, Engorde, Harina de Pescado
            $table->string('category')->default('Alimento Terminado'); // Ej: Alimento Terminado, Materia Prima, Suplemento
            $table->string('brand')->nullable(); // Marca (ej. Purina, Italcol)
            $table->string('feed_type')->nullable(); // Tipo de alimento (ej. Extrudizado, Peletizado, Polvo)
            $table->decimal('protein_percentage', 5, 2)->nullable(); // Ej: 32.50, 40.00, 45.00
            $table->decimal('bag_weight_kg', 8, 2)->nullable(); // Peso por bulto en kg (ej. 40.00)
            $table->decimal('quantity_kg', 10, 2)->default(0); // Cantidad total disponible en kilos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_inventories');
    }
};
