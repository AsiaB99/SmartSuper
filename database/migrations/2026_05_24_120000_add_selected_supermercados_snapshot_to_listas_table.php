<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            $table->json('supermercados_recomendados_snapshot')
                ->nullable()
                ->after('id_supermercado_elegido');
        });
    }

    public function down(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            $table->dropColumn('supermercados_recomendados_snapshot');
        });
    }
};
