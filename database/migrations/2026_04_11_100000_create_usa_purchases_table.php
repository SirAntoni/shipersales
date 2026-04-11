<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usa_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->string('carrier', 150)->nullable();
            $table->string('store', 100)->nullable();
            $table->string('order_number', 200)->nullable();
            $table->string('tracking', 200)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('description', 300)->nullable();
            $table->string('status', 50)->default('EMBARCADO');
            $table->date('arrival_date')->nullable();
            $table->text('comments')->nullable();
            $table->enum('type', ['CARGO', 'VIAJERO'])->default('CARGO');
            $table->year('year')->nullable();
            $table->boolean('processed')->default(false);
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->index('status');
            $table->index('type');
            $table->index('year');
            $table->index('processed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usa_purchases');
    }
};
