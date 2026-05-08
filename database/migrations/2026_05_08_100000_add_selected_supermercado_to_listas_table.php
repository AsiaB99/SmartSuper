<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            $table->foreignId('id_supermercado_elegido')
                ->nullable()
                ->after('estado')
                ->constrained('supermercados')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_supermercado_elegido');
        });
    }
};
