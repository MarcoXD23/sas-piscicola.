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
        if (! Schema::hasTable('alimentos_bodega')) {
            Schema::create('alimentos_bodega', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->string('nombre_concentrado', 150);
                $table->integer('proteina_porcentaje')->default(32);
                $table->decimal('peso_bulto_kg', 8, 2)->default(40.00);
                $table->decimal('stock_bultos', 10, 2)->default(0);
                $table->decimal('stock_kilos_actual', 12, 2)->default(0);
                $table->decimal('umbral_alerta_bultos', 8, 2)->default(10.00);
                $table->decimal('costo_unitario_bulto', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['finca_id', 'nombre_concentrado']);
            });
        }

        if (! Schema::hasTable('movimientos_bodega')) {
            Schema::create('movimientos_bodega', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('alimento_id')->constrained('alimentos_bodega')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipo_movimiento', 50); // entrada_compra, salida_alimentacion, ajuste_merma
                $table->decimal('cantidad_bultos', 10, 2);
                $table->decimal('cantidad_kilos', 12, 2);
                $table->date('fecha')->index();
                $table->string('proveedor', 150)->nullable();
                $table->text('observacion')->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_bodega');
        Schema::dropIfExists('alimentos_bodega');
    }
};
