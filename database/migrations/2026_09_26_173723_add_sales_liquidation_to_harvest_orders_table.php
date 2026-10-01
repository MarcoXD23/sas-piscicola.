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
        if (Schema::hasTable('harvest_orders')) {
            Schema::table('harvest_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('harvest_orders', 'customer_name')) {
                    $table->string('customer_name', 150)->nullable()->after('destination');
                }
                if (! Schema::hasColumn('harvest_orders', 'price_per_kg')) {
                    $table->decimal('price_per_kg', 12, 2)->nullable()->after('customer_name');
                }
                if (! Schema::hasColumn('harvest_orders', 'total_sale_amount')) {
                    $table->decimal('total_sale_amount', 14, 2)->nullable()->after('price_per_kg');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('harvest_orders')) {
            Schema::table('harvest_orders', function (Blueprint $table) {
                $table->dropColumn(['customer_name', 'price_per_kg', 'total_sale_amount']);
            });
        }
    }
};
