<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usa_purchases', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('description');
            $table->unsignedBigInteger('article_id')->nullable()->after('sku');
            $table->foreign('article_id')->references('id')->on('articles')->nullOnDelete();
            $table->index('sku');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::table('usa_purchases', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['article_id']);
            $table->dropColumn(['sku', 'article_id']);
        });
    }
};
