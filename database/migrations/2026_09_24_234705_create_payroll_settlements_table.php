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
        Schema::create('payroll_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->date('week_start_date'); // Lunes de la semana
            $table->date('cutoff_date')->index(); // Sábado de corte programado
            $table->date('settlement_date'); // Fecha de liquidación
            $table->unsignedInteger('total_jornales_count')->default(0);
            $table->unsignedInteger('total_workers_count')->default(0);
            $table->decimal('total_gross_amount', 12, 2)->default(0);
            $table->decimal('total_deductions_amount', 12, 2)->default(0);
            $table->decimal('total_net_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['borrador', 'liquidada', 'pagada'])->default('liquidada');
            $table->foreignId('settled_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['finca_id', 'cutoff_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settlements');
    }
};
