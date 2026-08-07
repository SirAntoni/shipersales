<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * purchase_details ya tiene deleted_at (softDeletes) desde su migracion
     * original, pero el modelo nunca uso el trait. Ahora un detalle eliminado
     * se conserva para mostrarlo tachado en la compra, y hace falta saber
     * quien lo quito.
     */
    public function up(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_details', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                $table->index('deleted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_details', 'deleted_by')) {
                $table->dropIndex(['deleted_by']);
                $table->dropColumn('deleted_by');
            }
        });
    }
};
