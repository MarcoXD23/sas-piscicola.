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
        // 1. Agregar columna tipo_estanque a la tabla ponds si no existe
        if (Schema::hasTable('ponds') && ! Schema::hasColumn('ponds', 'tipo_estanque')) {
            Schema::table('ponds', function (Blueprint $table) {
                $table->string('tipo_estanque', 100)->default('Tierra')->after('name')->nullable();
            });
        }

        // 2. Crear tabla agenda_turnos
        if (! Schema::hasTable('agenda_turnos')) {
            Schema::create('agenda_turnos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->default(1)->constrained('fincas')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->date('fecha')->index();
                $table->enum('tipo_dia', ['semana', 'sabado', 'domingo'])->default('semana')->index();
                $table->enum('rol_asignado', ['alimentador', 'seguridad_noche'])->default('alimentador')->index();
                $table->string('estado', 50)->default('programado');
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'fecha', 'rol_asignado'], 'uq_agenda_turnos_user_fecha_rol');
                $table->index(['finca_id', 'fecha', 'rol_asignado'], 'idx_turnos_finca_fecha_rol');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_turnos');

        if (Schema::hasTable('ponds') && Schema::hasColumn('ponds', 'tipo_estanque')) {
            Schema::table('ponds', function (Blueprint $table) {
                $table->dropColumn('tipo_estanque');
            });
        }
    }
};
