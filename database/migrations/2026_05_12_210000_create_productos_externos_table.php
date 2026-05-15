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
        Schema::create('productos_externos', function (Blueprint $table) {
            $table->id();
            $table->string('fuente', 50);
            $table->string('external_id', 100);
            $table->string('nombre', 255)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('formato', 50)->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->decimal('precio_anterior', 10, 2)->nullable();
            $table->decimal('precio_unidad', 10, 2)->nullable();
            $table->string('unidad_ref', 20)->nullable();
            $table->string('tamano', 80)->nullable();
            $table->string('imagen', 255)->nullable();
            $table->string('url_producto', 255)->nullable();
            $table->boolean('disponible')->default(true);
            $table->string('codigo_postal', 10)->nullable();
            $table->string('warehouse_id', 30)->nullable();
            $table->string('categoria_id', 50)->nullable();
            $table->string('categoria_nombre', 100)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fecha_importacion')->nullable();

            $table->unique(
                ['fuente', 'external_id', 'codigo_postal', 'warehouse_id'],
                'productos_externos_fuente_external_context_unique'
            );
            $table->index(['fuente', 'codigo_postal', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_externos');
    }
};
