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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_seccion')
                ->constrained('secciones')
                ->restrictOnDelete();
            $table->string('nombre_producto', 100);
            $table->string('marca', 50)->nullable();
            $table->string('formato', 50)->nullable();
            $table->string('imagen', 255)->nullable();

            $table->index(['id_seccion', 'nombre_producto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
