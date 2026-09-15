<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una compra USA puede importarse al stock en varias llegadas. Hasta ahora
 * la fila se marcaba "procesada" con la primera importacion, aunque fuera
 * parcial, y el saldo quedaba sin forma de importarse. imported_quantity
 * acumula lo importado y processed pasa a significar "importado completo".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('usa_purchases', function (Blueprint $table) {
            $table->unsignedInteger('imported_quantity')->default(0)->after('quantity');
        });

        // Relleno: lo realmente importado son los detalles vivos (no quitados)
        // de la compra local para ese articulo, sin pasar de lo pedido. Una
        // fila procesada sin compra o sin articulo se da por completa.
        DB::statement(<<<SQL
UPDATE usa_purchases u
LEFT JOIN (
    SELECT purchase_id, article_id, SUM(quantity) AS qty
    FROM purchase_details
    WHERE deleted_at IS NULL
    GROUP BY purchase_id, article_id
) d ON d.purchase_id = u.purchase_id AND d.article_id = u.article_id
SET u.imported_quantity = CASE
    WHEN u.processed = 0 THEN 0
    WHEN u.purchase_id IS NULL OR u.article_id IS NULL THEN u.quantity
    ELSE LEAST(u.quantity, COALESCE(d.qty, 0))
END
SQL);

        DB::statement('UPDATE usa_purchases SET processed = (imported_quantity >= quantity) WHERE quantity > 0');
    }

    public function down(): void
    {
        Schema::table('usa_purchases', function (Blueprint $table) {
            $table->dropColumn('imported_quantity');
        });
    }
};
