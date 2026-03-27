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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre_usuario', 50)->nullable()->after('name');
            $table->enum('rol', ['admin', 'cliente'])->default('cliente')->after('password');
            $table->decimal('latitud', 10, 8)->nullable()->after('rol');
            $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nombre_usuario', 'rol', 'latitud', 'longitud']);
        });
    }
};
