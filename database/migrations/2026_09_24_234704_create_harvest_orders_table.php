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
        Schema::create('harvest_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->foreignId('pond_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheduled_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('scheduled_date')->index();
            $table->decimal('estimated_kg', 10, 2);

            // Registro en báscula por Administrador
            $table->decimal('gross_weight_kg', 10, 2)->nullable();
            $table->foreignId('weighed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('weighed_at')->nullable();

            // Proceso de limpieza y despacho
            $table->decimal('clean_weight_kg', 10, 2)->nullable();
            $table->unsignedInteger('baskets_count')->nullable(); // Número de canastas
            $table->string('driver_name')->nullable();
            $table->string('driver_id_card')->nullable();
            $table->string('driver_vehicle_plate')->nullable();
            $table->string('destination')->nullable(); // Rumbo o destino del envío
            $table->foreignId('dispatched_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();

            $table->enum('status', ['programada', 'pesaje_completado', 'despachada', 'cancelada'])->default('programada');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['finca_id', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvest_orders');
    }
};
