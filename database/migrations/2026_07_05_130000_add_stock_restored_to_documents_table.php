<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // true cuando la nota de crédito repuso stock al emitirse
            // (permite revertirlo con exactitud si la NC se anula)
            $table->boolean('stock_restored')->default(false)->after('affected_document_id');
        });

        // Backfill: las NC emitidas desde el 2026-07-05 (feature de stock) repusieron stock
        DB::table('documents')
            ->whereNotNull('affected_document_id')
            ->where('created_at', '>=', '2026-07-05 00:00:00')
            ->update(['stock_restored' => true]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('stock_restored');
        });
    }
};
