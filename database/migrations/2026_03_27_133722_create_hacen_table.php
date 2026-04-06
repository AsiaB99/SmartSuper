<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No-op intencional: la tabla ya se crea en 2026_03_27_133717_create_hacen_table.php.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op intencional: el rollback de la migración original elimina la tabla.
    }
};
