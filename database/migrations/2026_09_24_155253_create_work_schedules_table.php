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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('schedule_date')->index();
            $table->string('shift_type'); // ej: 'fin_de_semana', 'festivo', 'bloque_alimentacion', 'diurno', 'nocturno'
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('programado'); // 'programado', 'completado', 'cancelado'
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices compuestos útiles para consultas de turnos
            $table->index(['finca_id', 'schedule_date']);
            $table->index(['user_id', 'schedule_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
