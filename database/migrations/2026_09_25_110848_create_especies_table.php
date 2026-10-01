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
        Schema::create('especies', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comun');
            $table->string('nombre_cientifico');
            $table->string('familia')->nullable();
            $table->string('clima')->default('cálido'); // cálido, templado, frío
            $table->string('foto_url')->nullable();
            $table->decimal('temperatura_min', 4, 1)->default(24.0);
            $table->decimal('temperatura_max', 4, 1)->default(32.0);
            $table->decimal('oxigeno_min_mg_l', 4, 2)->default(4.00);
            $table->decimal('ph_min', 4, 2)->default(6.50);
            $table->decimal('ph_max', 4, 2)->default(8.50);
            $table->string('densidad_tierra_m2')->nullable();
            $table->string('densidad_geomembrana_m3')->nullable();
            $table->string('proteina_iniciacion')->nullable();
            $table->string('proteina_levante')->nullable();
            $table->string('proteina_engorde')->nullable();
            $table->string('meses_cosecha_promedio')->nullable();
            $table->string('peso_comercial_gramos')->nullable();
            $table->text('rol_policultivo')->nullable();
            $table->longText('guia_manejo_cultivo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('especies');
    }
};
