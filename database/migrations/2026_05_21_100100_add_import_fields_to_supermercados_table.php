<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supermercados', function (Blueprint $table) {
            $table->foreignId('id_cadena')
                ->nullable()
                ->after('id')
                ->constrained('cadenas_supermercados')
                ->nullOnDelete();
            $table->string('fuente', 40)->nullable()->after('longitud');
            $table->string('external_id', 120)->nullable()->after('fuente');
            $table->string('osm_type', 20)->nullable()->after('external_id');
            $table->string('marca', 120)->nullable()->after('osm_type');
            $table->string('operador', 120)->nullable()->after('marca');
            $table->boolean('activo')->default(true)->after('operador');
            $table->timestamp('ultima_vista_en')->nullable()->after('activo');

            $table->unique(['fuente', 'external_id']);
            $table->index(['activo', 'id_cadena']);
        });
    }

    public function down(): void
    {
        Schema::table('supermercados', function (Blueprint $table) {
            $table->dropUnique(['fuente', 'external_id']);
            $table->dropIndex(['activo', 'id_cadena']);
            $table->dropConstrainedForeignId('id_cadena');
            $table->dropColumn([
                'fuente',
                'external_id',
                'osm_type',
                'marca',
                'operador',
                'activo',
                'ultima_vista_en',
            ]);
        });
    }
};
