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
        Schema::table('sales', function (Blueprint $table) {
            $table->index('status', 'idx_sales_status');
            $table->index('created_at', 'idx_sales_created_at');
            $table->index('number', 'idx_sales_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_status');
            $table->dropIndex('idx_sales_created_at');
            $table->dropIndex('idx_sales_number');
        });
    }
};
