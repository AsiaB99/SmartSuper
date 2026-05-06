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
        Schema::create('formadas', function (Blueprint $table) {
            $table->foreignId('id_lista')
                ->constrained('listas')
                ->cascadeOnDelete();
            $table->foreignId('id_producto')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->boolean('marcado')->default(false);

            $table->primary(['id_lista', 'id_producto']);
            $table->index('marcado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formadas');
    }
};
