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
        // 1. Ampliación de Users para soportar roles y tipos de vinculación
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('trabajador')->change();
            $table->string('employment_type', 50)->default('fijo')->change();
            if (! Schema::hasColumn('users', 'accumulated_fish_credit')) {
                $table->decimal('accumulated_fish_credit', 12, 2)->default(0)->after('employment_type');
            }
        });

        // 2. Ampliación de Lagos (Ponds)
        Schema::table('ponds', function (Blueprint $table) {
            if (! Schema::hasColumn('ponds', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('ponds', 'fingerlings_stocked')) {
                $table->unsignedInteger('fingerlings_stocked')->default(0)->after('code');
            }
            if (! Schema::hasColumn('ponds', 'stocked_at')) {
                $table->date('stocked_at')->nullable()->after('fingerlings_stocked');
            }
            if (! Schema::hasColumn('ponds', 'status')) {
                $table->string('status', 50)->default('activo')->after('biomass');
            }
        });

        // 3. Muestreos Sabatinos de Lagos
        if (! Schema::hasTable('pond_samplings')) {
            Schema::create('pond_samplings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('pond_id')->constrained('ponds')->cascadeOnDelete();
                $table->date('sampling_date')->index();
                $table->unsignedInteger('sampled_fish_count');
                $table->decimal('sample_total_weight_kg', 10, 3);
                $table->decimal('average_weight_g', 10, 2);
                $table->unsignedInteger('small_count')->default(0);
                $table->unsignedInteger('medium_count')->default(0);
                $table->unsignedInteger('large_count')->default(0);
                $table->unsignedInteger('commercial_count')->default(0);
                $table->decimal('small_percent', 5, 2)->default(0);
                $table->decimal('medium_percent', 5, 2)->default(0);
                $table->decimal('large_percent', 5, 2)->default(0);
                $table->decimal('commercial_percent', 5, 2)->default(0);
                $table->decimal('biomass_estimate_kg', 12, 2)->nullable();
                $table->foreignId('registered_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'sampling_date']);
            });
        }

        // 4. Asistencia a Pesca (Lunes / Martes festivo)
        if (! Schema::hasTable('fishing_attendances')) {
            Schema::create('fishing_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('attendance_date')->index();
                $table->boolean('attended')->default(true);
                $table->string('day_of_week', 20)->default('lunes');
                $table->boolean('is_holiday_catchup')->default(false);
                $table->string('role_in_harvest', 100)->nullable();
                $table->decimal('daily_wage', 10, 2)->default(0);
                $table->foreignId('registered_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'attendance_date']);
            });
        }

        // 5. Descuento de Pescado Fiado
        if (! Schema::hasTable('fish_credits')) {
            Schema::create('fish_credits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('credit_date')->index();
                $table->decimal('kilos', 8, 2);
                $table->decimal('price_per_kg', 10, 2)->default(7000.00);
                $table->decimal('total_amount', 12, 2);
                $table->string('status', 50)->default('pendiente')->index(); // pendiente, descontado_sabado, acumulado_mensual, pagado
                $table->foreignId('payroll_settlement_id')->nullable()->constrained('payroll_settlements')->nullOnDelete();
                $table->foreignId('registered_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'credit_date']);
            });
        }

        // 6. Ampliación de Feed Inventories y Movimientos de Bodega
        Schema::table('feed_inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('feed_inventories', 'min_stock_alert_kg')) {
                $table->decimal('min_stock_alert_kg', 10, 2)->default(100.00)->after('quantity_kg');
            }
        });

        if (! Schema::hasTable('warehouse_movements')) {
            Schema::create('warehouse_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('feed_inventory_id')->constrained('feed_inventories')->cascadeOnDelete();
                $table->string('movement_type', 50)->index(); // entrada, salida, ajuste
                $table->decimal('quantity_kg', 10, 2);
                $table->decimal('bags_count', 8, 2)->default(0);
                $table->decimal('unit_cost', 12, 2)->nullable();
                $table->date('movement_date')->index();
                $table->string('reference', 150)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['finca_id', 'movement_date']);
            });
        }

        // 7. Turnos Rotativos Semanales para 4 Trabajadores
        if (! Schema::hasTable('rotative_schedules')) {
            Schema::create('rotative_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('week_start_date')->index();
                $table->date('week_end_date')->index();
                $table->string('shift_type', 50)->index(); // lunes_a_viernes, fin_de_semana, guardia_nocturna
                $table->text('notes')->nullable();
                $table->foreignId('assigned_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['finca_id', 'week_start_date']);
            });
        }

        // 8. Ampliación de Feeding Logs para apetito y bultos
        Schema::table('feeding_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('feeding_logs', 'appetite_level')) {
                $table->string('appetite_level', 50)->default('bueno')->after('amount_kg');
            }
            if (! Schema::hasColumn('feeding_logs', 'bags_fed')) {
                $table->decimal('bags_fed', 6, 2)->nullable()->after('appetite_level');
            }
        });

        // 9. Ampliación de Harvest Orders para tara de canastillas, peso neto y comprador
        Schema::table('harvest_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('harvest_orders', 'basket_tare_kg')) {
                $table->decimal('basket_tare_kg', 8, 2)->default(2.00)->after('baskets_count');
            }
            if (! Schema::hasColumn('harvest_orders', 'net_weight_kg')) {
                $table->decimal('net_weight_kg', 10, 2)->nullable()->after('basket_tare_kg');
            }
            if (! Schema::hasColumn('harvest_orders', 'buyer_name')) {
                $table->string('buyer_name', 150)->nullable()->after('destination');
            }
        });

        // 10. Ventas directas a visitantes con efectivo recibido
        Schema::table('fish_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('fish_sales', 'cash_received')) {
                $table->decimal('cash_received', 12, 2)->nullable()->after('price_per_kg');
            }
        });

        // 11. Asignación de Tareas por el Administrador
        if (! Schema::hasTable('admin_tasks')) {
            Schema::create('admin_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->foreignId('assigned_to_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('priority', 50)->default('media'); // baja, media, alta, urgente
                $table->string('status', 50)->default('pendiente')->index(); // pendiente, en_progreso, completada, cancelada
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'status']);
            });
        }

        // 12. Solicitudes de Permisos Laborales
        if (! Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('start_date')->index();
                $table->date('end_date');
                $table->text('reason');
                $table->string('status', 50)->default('pendiente')->index(); // pendiente, aprobado, rechazado
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('response_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['finca_id', 'status']);
            });
        }

        // 13. Horas Extras con justificación y ocasión
        if (! Schema::hasTable('overtime_records')) {
            Schema::create('overtime_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finca_id')->index();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('record_date')->index();
                $table->decimal('hours', 4, 2);
                $table->string('occasion', 191);
                $table->text('justification');
                $table->string('status', 50)->default('pendiente')->index(); // pendiente, aprobado, rechazado
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['finca_id', 'record_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('admin_tasks');
        Schema::dropIfExists('rotative_schedules');
        Schema::dropIfExists('warehouse_movements');
        Schema::dropIfExists('fish_credits');
        Schema::dropIfExists('fishing_attendances');
        Schema::dropIfExists('pond_samplings');

        Schema::table('fish_sales', function (Blueprint $table) {
            $table->dropColumn(['cash_received']);
        });

        Schema::table('harvest_orders', function (Blueprint $table) {
            $table->dropColumn(['basket_tare_kg', 'net_weight_kg', 'buyer_name']);
        });

        Schema::table('feeding_logs', function (Blueprint $table) {
            $table->dropColumn(['appetite_level', 'bags_fed']);
        });

        Schema::table('feed_inventories', function (Blueprint $table) {
            $table->dropColumn(['min_stock_alert_kg']);
        });

        Schema::table('ponds', function (Blueprint $table) {
            $table->dropColumn(['code', 'fingerlings_stocked', 'stocked_at', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accumulated_fish_credit']);
        });
    }
};
