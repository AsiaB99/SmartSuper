<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->string('codigo_barras', 32)->nullable()->after('id_seccion');
            $table->decimal('cantidad_envase', 10, 3)->nullable()->after('formato');
            $table->string('unidad_envase', 20)->nullable()->after('cantidad_envase');
            $table->string('fuente_datos', 50)->nullable()->after('imagen');
            $table->unique('codigo_barras');
        });

        Schema::table('venden', function (Blueprint $table): void {
            $table->string('moneda', 3)->default('EUR')->after('unidad_ref');
            $table->string('fuente_precio', 50)->nullable()->after('moneda');
            $table->string('url_origen', 2048)->nullable()->after('fuente_precio');
            $table->date('fecha_precio')->nullable()->after('url_origen');
        });
    }

    public function down(): void
    {
        Schema::table('venden', function (Blueprint $table): void {
            $table->dropColumn(['moneda', 'fuente_precio', 'url_origen', 'fecha_precio']);
        });

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique(['codigo_barras']);
            $table->dropColumn(['codigo_barras', 'cantidad_envase', 'unidad_envase', 'fuente_datos']);
        });
    }
};
