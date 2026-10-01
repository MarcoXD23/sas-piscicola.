<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $views = [
            'mortalidades' => 'registros_mortalidad',
            'tareas' => 'admin_tasks',
            'horas_extras' => 'overtime_records',
            'permisos' => 'leave_requests',
            'pescado_empleados' => 'fish_credits',
        ];

        foreach ($views as $view => $table) {
            if (Schema::hasTable($table)) {
                DB::statement("CREATE OR REPLACE VIEW {$view} AS SELECT * FROM {$table}");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $views = ['mortalidades', 'tareas', 'horas_extras', 'permisos', 'pescado_empleados'];

        foreach ($views as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view}");
        }
    }
};