<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('inventory_sessions', function (Blueprint $t) {
            $t->id();
            $t->date('count_date');
            $t->unsignedBigInteger('user_id');
            $t->timestamp('started_at');
            $t->timestamp('finished_at')->nullable();
            $t->unsignedInteger('duration_sec')->nullable();
            $t->unsignedInteger('total_rows');
            $t->unsignedInteger('completed_rows')->default(0);
            $t->string('note')->nullable();
            $t->timestamps();

            $t->index(['count_date', 'user_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('inventory_sessions');
    }
};
