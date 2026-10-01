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
        Schema::table('harvest_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('harvest_orders', 'total_tare_kg')) {
                $table->decimal('total_tare_kg', 10, 2)->nullable()->after('basket_tare_kg');
            }
            if (! Schema::hasColumn('harvest_orders', 'weighing_batches')) {
                $table->json('weighing_batches')->nullable()->after('total_tare_kg');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harvest_orders', function (Blueprint $table) {
            if (Schema::hasColumn('harvest_orders', 'weighing_batches')) {
                $table->dropColumn('weighing_batches');
            }
            if (Schema::hasColumn('harvest_orders', 'total_tare_kg')) {
                $table->dropColumn('total_tare_kg');
            }
        });
    }
};
