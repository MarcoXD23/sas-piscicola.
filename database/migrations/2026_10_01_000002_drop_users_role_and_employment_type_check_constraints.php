<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_employment_type_check');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En down() no hacer nada
    }
};

