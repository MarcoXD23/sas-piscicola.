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
        Schema::create('daily_labors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->foreignId('payroll_settlement_id')->nullable()->constrained('payroll_settlements')->nullOnDelete();
            $table->string('worker_name');
            $table->string('worker_id_card')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('employment_type', ['fijo', 'temporal'])->default('temporal')->index();
            $table->foreignId('pond_id')->nullable()->constrained('ponds')->nullOnDelete();
            $table->date('work_date')->index();
            $table->enum('labor_type', ['rayadores', 'lavado_estanques', 'pesca', 'empaque', 'mantenimiento', 'otro']);
            $table->decimal('daily_wage', 10, 2)->default(0); // Valor del jornal pactado (ej: 60000)
            $table->decimal('hours_worked', 4, 2)->default(8.0);
            $table->enum('payment_status', ['pendiente', 'liquidado'])->default('pendiente');
            $table->foreignId('registered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['finca_id', 'work_date']);
            $table->index(['finca_id', 'payment_status']);
            $table->index(['finca_id', 'employment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_labors');
    }
};
