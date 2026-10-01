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
        // 1. Tabla de roles del sistema
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 60)->unique();
                $table->string('name', 120);
                $table->string('descripcion')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tabla pivote role_user para soporte multi-rol por trabajador
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
            });
        }

        // 3. Tabla ingresos_alimento: Control logístico y físico de bodega sin costos monetarios
        if (! Schema::hasTable('ingresos_alimento')) {
            Schema::create('ingresos_alimento', function (Blueprint $table) {
                $table->id();
                $table->date('fecha_recepcion')->index();
                $table->string('proveedor', 150);
                $table->string('tipo_concentrado', 150)->index();
                $table->decimal('bultos_recibidos', 10, 2);
                $table->decimal('peso_bulto_kg', 8, 2)->default(40.00);
                $table->decimal('kilos_totales', 12, 2);
                $table->string('lote_fabrica', 100)->nullable();
                $table->foreignId('recibido_por')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // 4. Tabla actividades_trabajadores: Bitácora de trazabilidad operativa
        if (! Schema::hasTable('actividades_trabajadores')) {
            Schema::create('actividades_trabajadores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('rol_momento', 100);
                $table->string('tipo_accion', 100)->index();
                $table->text('descripcion');
                $table->foreignId('estanque_id')->nullable()->constrained('ponds')->nullOnDelete();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades_trabajadores');
        Schema::dropIfExists('ingresos_alimento');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
