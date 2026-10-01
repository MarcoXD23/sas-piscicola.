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
        Schema::create('ponds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->string('name')->nullable();
            $table->integer('fish_population')->default(0);
            $table->decimal('average_weight', 8, 2)->default(0)->comment('Average weight in grams');
            $table->decimal('biomass', 10, 2)->default(0)->comment('Biomass in kilograms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ponds');
    }
};
