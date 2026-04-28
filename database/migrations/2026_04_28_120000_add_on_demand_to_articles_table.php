<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('on_demand')->default(false);
            $table->index(['status', 'on_demand'], 'idx_articles_status_on_demand');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('idx_articles_status_on_demand');
            $table->dropColumn('on_demand');
        });
    }
};
