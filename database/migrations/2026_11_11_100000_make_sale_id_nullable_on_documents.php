<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los comprobantes emitidos en el portal SOL de SUNAT (series E001/EB01) se
 * registran desde el Excel de SIRE (sire:registrar-portal) y algunos no
 * corresponden a ninguna venta del sistema: sale_id tiene que admitir null.
 * La llave foranea a sales se mantiene.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Falla si ya hay comprobantes sin venta: hay que enlazarlos o borrarlos antes.
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
        });
    }
};
