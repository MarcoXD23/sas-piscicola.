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
        if (!Schema::hasTable('pond_especies')) {
            Schema::create('pond_especies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pond_id')->constrained('ponds')->onDelete('cascade');
                $table->foreignId('especie_id')->constrained('especies')->onDelete('restrict');
                $table->integer('fingerlings_stocked')->default(0);
                $table->integer('fish_population')->default(0);
                $table->decimal('average_weight', 8, 2)->default(0);
                $table->decimal('biomass', 10, 2)->default(0);
                $table->unsignedBigInteger('finca_id')->default(1)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('ponds', 'es_policultivo')) {
            Schema::table('ponds', function (Blueprint $table) {
                $table->boolean('es_policultivo')->default(false)->after('especie_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pond_especies');

        if (Schema::hasColumn('ponds', 'es_policultivo')) {
            Schema::table('ponds', function (Blueprint $table) {
                $table->dropColumn('es_policultivo');
            });
        }
    }
};

