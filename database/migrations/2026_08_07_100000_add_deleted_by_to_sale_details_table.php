<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * sale_details ya tiene deleted_at (softDeletes) desde su migracion original,
     * pero el modelo nunca uso el trait, asi que los borrados eran fisicos.
     * Ahora el detalle eliminado se conserva para mostrarlo deshabilitado en la
     * venta, y necesitamos saber quien lo elimino.
     */
    public function up(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_details', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->index('deleted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            if (Schema::hasColumn('sale_details', 'deleted_by')) {
                $table->dropIndex(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
        });
    }
};
