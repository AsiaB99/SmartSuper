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
        Schema::create('tienen', function (Blueprint $table) {
            $table->foreignId('id_usuario')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('id_despensa')
                ->constrained('despensas')
                ->cascadeOnDelete();
            $table->enum('permiso_despensa', ['owner', 'editor', 'viewer'])->default('owner');

            $table->primary(['id_usuario', 'id_despensa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tienen');
    }
};
