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
        // 1. Ampliación de tratamientos_sanitarios para normativa y retiros ICA
        if (Schema::hasTable('tratamientos_sanitarios')) {
            Schema::table('tratamientos_sanitarios', function (Blueprint $table) {
                if (! Schema::hasColumn('tratamientos_sanitarios', 'lote_id')) {
                    $table->string('lote_id', 80)->nullable()->after('finca_id');
                }
                if (! Schema::hasColumn('tratamientos_sanitarios', 'principio_activo')) {
                    $table->string('principio_activo', 150)->nullable()->after('producto');
                }
                if (! Schema::hasColumn('tratamientos_sanitarios', 'dosis')) {
                    $table->string('dosis', 100)->nullable()->after('principio_activo');
                }
                if (! Schema::hasColumn('tratamientos_sanitarios', 'tiempo_retiro_dias')) {
                    $table->unsignedInteger('tiempo_retiro_dias')->default(0)->after('dosis');
                }
                if (! Schema::hasColumn('tratamientos_sanitarios', 'fecha_habil_cosecha')) {
                    $table->date('fecha_habil_cosecha')->nullable()->index()->after('tiempo_retiro_dias');
                }
                if (! Schema::hasColumn('tratamientos_sanitarios', 'responsable')) {
                    $table->string('responsable', 150)->nullable()->after('user_id');
                }
            });
        }

        // 1.1 Ampliación de harvest_orders con datos de liquidación comercial
        if (Schema::hasTable('harvest_orders')) {
            Schema::table('harvest_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('harvest_orders', 'customer_name')) {
                    $table->string('customer_name', 150)->nullable()->after('destination');
                }
                if (! Schema::hasColumn('harvest_orders', 'price_per_kg')) {
                    $table->decimal('price_per_kg', 12, 2)->nullable()->after('customer_name');
                }
                if (! Schema::hasColumn('harvest_orders', 'total_sale_amount')) {
                    $table->decimal('total_sale_amount', 14, 2)->nullable()->after('price_per_kg');
                }
            });
        }

        // 2. Tabla inventario_alimento (Bodega y Kilos de Concentrado)
        if (! Schema::hasTable('inventario_alimento')) {
            Schema::create('inventario_alimento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->string('tipo_concentrado', 150); // Ej: Iniciación 45%, Levante 34%, Engorde 24%
                $table->decimal('proteina_porcentaje', 5, 2)->nullable();
                $table->decimal('stock_actual_kg', 12, 2)->default(0);
                $table->decimal('stock_minimo_alerta_kg', 12, 2)->default(100);
                $table->decimal('costo_unitario', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['finca_id', 'tipo_concentrado']);
            });
        }

        // 3. Tabla ventas (Módulo de Ventas / Comercialización y Liquidación de Cosechas)
        if (! Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->string('lote_id', 80)->nullable()->index();
                $table->foreignId('estanque_id')->nullable()->constrained('ponds')->nullOnDelete();
                $table->foreignId('harvest_order_id')->nullable()->constrained('harvest_orders')->nullOnDelete();
                $table->string('cliente', 150);
                $table->decimal('kg_vendidos', 12, 2);
                $table->decimal('precio_por_kg', 12, 2);
                $table->decimal('total_venta', 14, 2);
                $table->string('forma_pago', 50)->default('contado'); // contado, credito, transferencia, consignacion
                $table->date('fecha')->index();
                $table->unsignedBigInteger('caja_id')->nullable()->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notas')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'fecha']);
            });
        }

        // 4. Módulo de Personal y Nómina (Temporal, Destajo y Jornal)
        if (! Schema::hasTable('personal_temporal')) {
            Schema::create('personal_temporal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->string('nombre', 150);
                $table->string('documento', 50)->nullable();
                $table->string('telefono', 50)->nullable();
                $table->string('tipo_pago', 30)->default('jornal'); // destajo, jornal
                $table->decimal('tarifa', 12, 2)->default(0); // Tarifa por día (jornal) o por kg/labor (destajo)
                $table->decimal('deducciones', 12, 2)->default(0);
                $table->string('estado', 30)->default('activo'); // activo, inactivo
                $table->timestamps();

                $table->index(['finca_id', 'estado']);
            });
        }

        if (! Schema::hasTable('liquidaciones_semanales')) {
            Schema::create('liquidaciones_semanales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('personal_temporal_id')->nullable()->constrained('personal_temporal')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('corte_sabado')->index();
                $table->string('tipo_pago', 30)->default('jornal'); // destajo, jornal
                $table->decimal('unidades_trabajadas', 10, 2)->default(0); // Días laborados o kg cosechados
                $table->decimal('tarifa', 12, 2)->default(0);
                $table->decimal('total_bruto', 14, 2)->default(0);
                $table->decimal('deducciones', 14, 2)->default(0);
                $table->decimal('total_neto', 14, 2)->default(0);
                $table->string('estado', 30)->default('liquidado'); // liquidado, pagado
                $table->foreignId('liquidado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'corte_sabado']);
            });
        }

        // 5. Capa de Negocio SaaS (Planes de Suscripción y Suscripciones de Fincas)
        if (! Schema::hasTable('planes_suscripcion')) {
            Schema::create('planes_suscripcion', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100); // Básico, Profesional, Agro-Industrial
                $table->string('slug', 100)->unique();
                $table->text('descripcion')->nullable();
                $table->unsignedInteger('max_estanques')->default(10);
                $table->unsignedInteger('max_usuarios')->default(5);
                $table->decimal('precio_mensual', 12, 2)->default(0);
                $table->boolean('soporte_offline_pwa')->default(true);
                $table->boolean('telemetria_iot')->default(false);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('suscripciones')) {
            Schema::create('suscripciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->constrained('fincas')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('planes_suscripcion')->cascadeOnDelete();
                $table->string('estado', 40)->default('activa'); // activa, pendiente_pago, suspendida, cancelada
                $table->date('fecha_inicio');
                $table->date('fecha_vencimiento')->index();
                $table->timestamp('ultimo_pago_at')->nullable();
                $table->decimal('monto_ultimo_pago', 12, 2)->nullable();
                $table->string('referencia_pago', 100)->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'estado']);
            });
        }

        // 6. Registro de Sincronización Offline (Idempotencia y Trazabilidad LWW)
        if (! Schema::hasTable('offline_sync_logs')) {
            Schema::create('offline_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->index();
                $table->string('client_uuid', 64)->unique();
                $table->string('table_name', 60);
                $table->string('action', 30)->default('create'); // create, update, delete
                $table->timestamp('device_timestamp');
                $table->timestamp('processed_at');
                $table->string('status', 30)->default('synced'); // synced, ignored_lww, error
                $table->json('payload');
                $table->timestamps();

                $table->index(['finca_id', 'processed_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_sync_logs');
        Schema::dropIfExists('suscripciones');
        Schema::dropIfExists('planes_suscripcion');
        Schema::dropIfExists('liquidaciones_semanales');
        Schema::dropIfExists('personal_temporal');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('inventario_alimento');

        if (Schema::hasTable('tratamientos_sanitarios')) {
            Schema::table('tratamientos_sanitarios', function (Blueprint $table) {
                $table->dropColumn([
                    'lote_id',
                    'principio_activo',
                    'dosis',
                    'tiempo_retiro_dias',
                    'fecha_habil_cosecha',
                    'responsable',
                ]);
            });
        }
    }
};
