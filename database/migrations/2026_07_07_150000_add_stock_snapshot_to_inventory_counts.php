<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_counts', function (Blueprint $table) {
            // Foto del stock al momento de guardar el conteo: permite revisar
            // un inventario pasado con los valores de ese día, no los actuales
            $table->integer('warehouse_stock')->nullable()->after('counted_stock');
            $table->integer('kardex_stock')->nullable()->after('warehouse_stock');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->dropColumn(['warehouse_stock', 'kardex_stock']);
        });
    }
};
