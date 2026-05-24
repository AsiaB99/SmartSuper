<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios_cadena', function (Blueprint $table) {
            $table->foreignId('id_producto')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->foreignId('id_cadena')
                ->constrained('cadenas_supermercados')
                ->cascadeOnDelete();
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_unidad', 10, 2)->nullable();
            $table->string('unidad_ref', 20)->nullable();
            $table->timestamp('fecha_actualizacion')
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->primary(['id_producto', 'id_cadena']);
            $table->index('fecha_actualizacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_cadena');
    }
};
