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
        Schema::create('feeding_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->foreignId('pond_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feed_inventory_id')->nullable()->constrained()->nullOnDelete();
            $table->date('feeding_date');
            $table->decimal('amount_kg', 10, 2);
            $table->string('feed_name')->nullable();
            $table->string('feed_brand')->nullable(); // Guardamos una instantánea (snapshot) de los datos del alimento
            $table->string('feed_type')->nullable();
            $table->decimal('feed_protein_percentage', 5, 2)->nullable();
            $table->decimal('feed_bag_weight_kg', 8, 2)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feeding_logs');
    }
};
