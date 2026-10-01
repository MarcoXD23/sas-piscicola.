<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de Tenants (Fincas Piscícolas)
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('codigo')->unique();
                $table->string('nit')->nullable();
                $table->string('ubicacion')->nullable();
                $table->json('configuraciones')->nullable();
                $table->timestamps();
            });
        }

        // Si existe la tabla fincas y tiene registros, sincronizar con tenants
        if (Schema::hasTable('fincas') && Schema::hasTable('tenants')) {
            $fincas = DB::table('fincas')->get();
            foreach ($fincas as $f) {
                DB::table('tenants')->updateOrInsert(
                    ['id' => $f->id],
                    [
                        'nombre' => $f->nombre ?? 'Finca '.$f->id,
                        'codigo' => $f->codigo ?? 'FIN-'.$f->id,
                        'nit' => $f->nit ?? null,
                        'ubicacion' => $f->ubicacion ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 2. Modificaciones en tabla users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('users', 'rol')) {
                $table->string('rol')->default('operario_alimentador')->after('tenant_id');
            }
            if (! Schema::hasColumn('users', 'roles_asignados')) {
                $table->json('roles_asignados')->nullable()->after('rol');
            }
            if (! Schema::hasColumn('users', 'aprobado')) {
                $table->boolean('aprobado')->default(true)->after('roles_asignados');
            }
            if (! Schema::hasColumn('users', 'aprobado_at')) {
                $table->timestamp('aprobado_at')->nullable()->after('aprobado');
            }
        });

        // Sincronizar tenant_id con finca_id y rol con role en users si existen
        if (Schema::hasColumn('users', 'finca_id') && Schema::hasColumn('users', 'tenant_id')) {
            DB::statement('UPDATE users SET tenant_id = finca_id WHERE tenant_id IS NULL AND finca_id IS NOT NULL');
        }
        if (Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'rol')) {
            DB::statement('UPDATE users SET rol = role WHERE (rol IS NULL OR rol = \'operario_alimentador\') AND role IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'aprobado_at')) {
                $table->dropColumn('aprobado_at');
            }
            if (Schema::hasColumn('users', 'aprobado')) {
                $table->dropColumn('aprobado');
            }
            if (Schema::hasColumn('users', 'roles_asignados')) {
                $table->dropColumn('roles_asignados');
            }
            if (Schema::hasColumn('users', 'rol')) {
                $table->dropColumn('rol');
            }
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });

        Schema::dropIfExists('tenants');
    }
};
