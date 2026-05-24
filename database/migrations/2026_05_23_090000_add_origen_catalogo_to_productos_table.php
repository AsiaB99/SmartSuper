<?php

use App\Models\Producto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->string('origen_catalogo', 20)
                ->default(Producto::ORIGEN_MANUAL)
                ->after('imagen');

            $table->index('origen_catalogo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropIndex(['origen_catalogo']);
            $table->dropColumn('origen_catalogo');
        });
    }
};
