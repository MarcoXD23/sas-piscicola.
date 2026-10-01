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
        Schema::create('fish_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->date('sale_date')->index();
            $table->enum('customer_type', ['visitante', 'trabajador'])->index();
            $table->string('customer_name')->nullable();
            $table->decimal('kilos_sold', 10, 2);
            $table->decimal('price_per_kg', 10, 2); // $9.000 visitante / $7.000 trabajador
            $table->decimal('total_amount', 12, 2); // Kilos * Precio
            $table->string('payment_method')->default('efectivo'); // efectivo, transferencia, descuento_nomina
            $table->foreignId('registered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['finca_id', 'sale_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_sales');
    }
};
