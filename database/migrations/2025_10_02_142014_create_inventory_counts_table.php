<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_counts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained('articles');
            $t->unsignedInteger('counted_stock');      // stock físico contado
            $t->date('counted_date');                  // día del conteo
            $t->foreignId('counted_by')->nullable()->constrained('users');
            $t->string('note', 255)->nullable();
            $t->timestamps();

            $t->index(['article_id', 'counted_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_counts');
    }
};
