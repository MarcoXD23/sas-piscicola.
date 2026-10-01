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
        Schema::table('feeding_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('finca_id')->constrained()->nullOnDelete();
            $table->foreignId('work_schedule_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feeding_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_schedule_id');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
