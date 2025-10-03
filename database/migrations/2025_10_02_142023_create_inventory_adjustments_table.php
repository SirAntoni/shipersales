<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained('articles');
            $t->integer('old_stock')->nullable();
            $t->integer('new_stock')->nullable();
            $t->integer('delta')->nullable();          // new - old
            $t->string('reason', 120)->default('Conteo físico');
            $t->string('source', 60)->nullable();      // p.ej. 'inventory_module'
            $t->foreignId('created_by')->nullable()->constrained('users');
            $t->timestamps();

            $t->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
