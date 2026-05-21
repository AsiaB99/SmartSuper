<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_externos', function (Blueprint $table): void {
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->string('mapeo_estado', 20)->default('pendiente');
            $table->decimal('sugerencia_score', 6, 4)->nullable();
            $table->json('sugerencia_snapshot')->nullable();

            $table->index('producto_id');
            $table->index(['mapeo_estado', 'fuente']);
        });
    }

    public function down(): void
    {
        Schema::table('productos_externos', function (Blueprint $table): void {
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['mapeo_estado', 'fuente']);
            $table->dropColumn([
                'producto_id',
                'mapeo_estado',
                'sugerencia_score',
                'sugerencia_snapshot',
            ]);
        });
    }
};
