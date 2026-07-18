<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_change_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained('articles');
            $t->integer('old_stock');
            $t->integer('new_stock');
            $t->integer('delta');
            $t->string('context', 190)->nullable();   // archivo:linea del codigo que hizo el cambio
            $t->string('via', 60)->nullable();        // http path o comando de consola
            $t->foreignId('user_id')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_change_logs');
    }
};
