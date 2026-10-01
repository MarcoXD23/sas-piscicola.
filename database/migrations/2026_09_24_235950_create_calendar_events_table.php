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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->index();
            $table->string('title');
            $table->enum('event_type', [
                'pesca_cosecha',
                'llegada_alevinos',
                'llegada_alimento',
                'visita_general',
            ])->index();
            $table->date('event_date')->index();
            $table->time('event_time')->nullable();
            $table->enum('status', [
                'programado',
                'en_progreso',
                'completado',
                'cancelado',
            ])->default('programado')->index();

            // 1. Pesca / Cosecha
            $table->foreignId('pond_id')->nullable()->constrained('ponds')->nullOnDelete();
            $table->decimal('estimated_kg', 10, 2)->nullable();

            // 2. Llegada de Alevinos
            $table->unsignedInteger('fingerlings_quantity')->nullable();
            $table->string('stage', 100)->nullable(); // e.g. reversión, alevinaje, ceba

            // 3. Llegada de Alimento
            $table->string('feed_type', 150)->nullable(); // e.g. Mojarra 38%, Iniciación
            $table->unsignedInteger('feed_bags_count')->nullable(); // bultos
            $table->decimal('feed_weight_kg', 10, 2)->nullable(); // peso total kg/toneladas

            // 4. Visita o Asunto General
            $table->text('inspection_notes')->nullable();

            // Notas generales
            $table->text('notes')->nullable();

            // Responsables y auditoría
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('last_modified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Sincronización Offline (Entornos Rurales)
            $table->string('client_uuid', 100)->nullable()->index();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->index(['finca_id', 'event_date']);
            $table->index(['finca_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
