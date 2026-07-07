<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
        });

        Schema::table('quotation_details', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id')->nullable()->change();
            $table->json('custom_product')->nullable()->after('article_id');
            $table->foreign('article_id')->references('id')->on('articles');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
            $table->dropColumn('custom_product');
        });

        Schema::table('quotation_details', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id')->nullable(false)->change();
            $table->foreign('article_id')->references('id')->on('articles');
        });
    }
};
