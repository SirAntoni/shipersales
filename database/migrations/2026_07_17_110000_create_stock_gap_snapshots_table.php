<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_gap_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained('articles');
            $t->integer('gap');              // stock - kardex
            $t->integer('transit');          // unidades en compras abiertas (status 2/3)
            $t->boolean('changed')->default(false); // el gap neto cambio respecto al chequeo anterior
            $t->date('checked_date');
            $t->timestamp('created_at')->useCurrent();

            $t->unique(['article_id', 'checked_date']);
            $t->index(['checked_date', 'changed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_gap_snapshots');
    }
};
