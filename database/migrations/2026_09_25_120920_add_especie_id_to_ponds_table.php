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
        Schema::table('ponds', function (Blueprint $table) {
            $table->foreignId('especie_id')
                ->nullable()
                ->after('finca_id')
                ->constrained('especies')
                ->nullOnDelete();
        });

        // Vincular estanques existentes según su nombre
        if (Schema::hasTable('especies') && Schema::hasTable('ponds')) {
            $mojarraRoja = DB::table('especies')->where('nombre_comun', 'like', '%Roja%')->value('id');
            $cachama = DB::table('especies')->where('nombre_comun', 'like', '%Cachama%')->value('id');
            $mojarraNegra = DB::table('especies')->where('nombre_comun', 'like', '%Negra%')->value('id');

            if ($mojarraRoja) {
                DB::table('ponds')->where('name', 'like', '%Roja%')->update(['especie_id' => $mojarraRoja]);
            }
            if ($cachama) {
                DB::table('ponds')->where('name', 'like', '%Cachama%')->update(['especie_id' => $cachama]);
            }
            if ($mojarraNegra) {
                DB::table('ponds')->whereNull('especie_id')->update(['especie_id' => $mojarraNegra]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ponds', function (Blueprint $table) {
            $table->dropForeign(['especie_id']);
            $table->dropColumn('especie_id');
        });
    }
};
