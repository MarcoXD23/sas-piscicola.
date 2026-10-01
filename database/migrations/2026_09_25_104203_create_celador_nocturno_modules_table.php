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
        // 1. Bitácora de Rondas Nocturnas
        if (! Schema::hasTable('bitacoras_nocturnas')) {
            Schema::create('bitacoras_nocturnas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha')->index();
                $table->string('hora_ronda', 25); // Checkpoints ej: 10:00 PM, 1:00 AM, 3:30 AM, 5:00 AM
                $table->foreignId('estanque_id')->nullable()->constrained('ponds')->nullOnDelete();
                $table->string('estado', 50)->default('normal'); // normal, anomalia, fuga_monje, depredador
                $table->string('nivel_agua_monje', 50)->default('optimo'); // optimo, bajo, rebose, fuga
                $table->string('estado_mallas', 50)->default('bueno'); // bueno, danada, ajustada
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
            });
        }

        // 2. Control de Aireadores y Fuentes de Energía
        if (! Schema::hasTable('control_aireadores')) {
            Schema::create('control_aireadores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('estanque_id')->constrained('ponds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha')->index();
                $table->dateTime('hora_encendido');
                $table->dateTime('hora_apagado')->nullable();
                $table->decimal('total_horas', 6, 2)->nullable();
                $table->string('fuente_energia', 50)->default('red_electrica'); // red_electrica, planta_emergencia
                $table->boolean('corte_luz')->default(false);
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
                $table->index(['estanque_id', 'hora_apagado']);
            });
        }

        // 3. Turnos Nocturnos y Despacho de Pescado a Celadores
        if (! Schema::hasTable('turnos_nocturnos')) {
            Schema::create('turnos_nocturnos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha')->index();
                $table->dateTime('hora_entrada');
                $table->dateTime('hora_salida')->nullable();
                $table->decimal('pescado_kilos_llevados', 8, 2)->default(0);
                $table->decimal('descuento_pescado', 12, 2)->default(0); // Kilos * 7000
                $table->string('estado', 50)->default('en_turno'); // en_turno, finalizado
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos_nocturnos');
        Schema::dropIfExists('control_aireadores');
        Schema::dropIfExists('bitacoras_nocturnas');
    }
};
