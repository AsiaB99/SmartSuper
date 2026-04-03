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
        Schema::create('almacena', function (Blueprint $table) {
            $table->foreignId('id_despensa')
                ->constrained('despensas')
                ->cascadeOnDelete();
            $table->foreignId('id_producto')
                ->constrained('productos')
                ->cascadeOnDelete();
            $table->unsignedInteger('stock')->default(0);

            $table->primary(['id_despensa', 'id_producto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacena');
    }
};
