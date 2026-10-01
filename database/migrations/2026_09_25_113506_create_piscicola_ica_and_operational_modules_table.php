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
        // 1. Ampliación de fincas con datos legales de certificación ICA
        Schema::table('fincas', function (Blueprint $table) {
            if (! Schema::hasColumn('fincas', 'nit')) {
                $table->string('nit', 50)->nullable()->after('codigo');
            }
            if (! Schema::hasColumn('fincas', 'departamento')) {
                $table->string('departamento', 100)->default('Tolima')->after('ubicacion');
            }
            if (! Schema::hasColumn('fincas', 'municipio')) {
                $table->string('municipio', 100)->default('Espinal')->after('departamento');
            }
            if (! Schema::hasColumn('fincas', 'responsable_tecnico')) {
                $table->string('responsable_tecnico', 150)->nullable()->after('municipio');
            }
            if (! Schema::hasColumn('fincas', 'registro_ica')) {
                $table->string('registro_ica', 100)->nullable()->after('responsable_tecnico');
            }
        });

        // 2. Ampliación de estanques con lote y alevinera de origen para trazabilidad ICA
        Schema::table('ponds', function (Blueprint $table) {
            if (! Schema::hasColumn('ponds', 'numero_lote')) {
                $table->string('numero_lote', 80)->nullable()->after('code');
            }
            if (! Schema::hasColumn('ponds', 'alevinera_origen')) {
                $table->string('alevinera_origen', 150)->nullable()->after('numero_lote');
            }
        });

        // 3. Tabla: Traslados de Peces y Desdobles de Densidad
        if (! Schema::hasTable('traslados_peces')) {
            Schema::create('traslados_peces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('estanque_origen_id')->constrained('ponds')->cascadeOnDelete();
                $table->foreignId('estanque_destino_id')->constrained('ponds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha')->index();
                $table->unsignedInteger('cantidad_peces_trasladados');
                $table->decimal('peso_promedio_gramos', 8, 2);
                $table->unsignedInteger('merma_traslado_peces')->default(0);
                $table->string('motivo', 50)->default('desdoble_densidad'); // desdoble_densidad, cambio_etapa, limpieza_estanque
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
            });
        }

        // 4. Tabla: Registro Sanitario, Tratamientos y Tiempo de Retiro
        if (! Schema::hasTable('tratamientos_sanitarios')) {
            Schema::create('tratamientos_sanitarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('estanque_id')->constrained('ponds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha_aplicacion')->index();
                $table->string('tipo_tratamiento', 50); // bano_sal, encalado, medicamento_veterinario, desinfectante
                $table->string('producto', 150);
                $table->string('dosis_aplicada', 100);
                $table->unsignedInteger('dias_tiempo_retiro')->default(0);
                $table->date('fecha_fin_retiro')->index();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'estanque_id']);
                $table->index(['estanque_id', 'fecha_fin_retiro']);
            });
        }

        // 5. Tabla: Registros Diarios de Calidad de Agua (BPAP / ICA)
        if (! Schema::hasTable('registros_calidad_agua')) {
            Schema::create('registros_calidad_agua', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('estanque_id')->constrained('ponds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha')->index();
                $table->string('hora', 25)->default('05:00 AM');
                $table->decimal('oxigeno_mg_l', 4, 2);
                $table->decimal('temperatura_c', 4, 1);
                $table->decimal('ph', 4, 2);
                $table->decimal('disco_secchi_cm', 5, 2)->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
                $table->index(['estanque_id', 'fecha']);
            });
        }

        // 6. Tabla: Bitácora de Mortalidad y Disposición (BPAP / ICA)
        if (! Schema::hasTable('registros_mortalidad')) {
            Schema::create('registros_mortalidad', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('estanque_id')->constrained('ponds')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('fecha')->index();
                $table->unsignedInteger('cantidad_peces');
                $table->string('causa_probable', 100)->default('desconocida'); // asfixia, manipulacion, enfermedad, depredador, desconocida
                $table->string('metodo_disposicion', 50)->default('compostaje'); // compostaje, fosa, entierro_cal
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
                $table->index(['estanque_id', 'fecha']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_mortalidad');
        Schema::dropIfExists('registros_calidad_agua');
        Schema::dropIfExists('tratamientos_sanitarios');
        Schema::dropIfExists('traslados_peces');

        Schema::table('ponds', function (Blueprint $table) {
            $table->dropColumn(['numero_lote', 'alevinera_origen']);
        });

        Schema::table('fincas', function (Blueprint $table) {
            $table->dropColumn(['nit', 'departamento', 'municipio', 'responsable_tecnico', 'registro_ica']);
        });
    }
};
