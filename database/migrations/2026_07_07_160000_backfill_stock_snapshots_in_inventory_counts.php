<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill de fotos de stock para conteos previos al cambio (solo desde
     * 2026-07-01, fecha desde la que opera el módulo con historial confiable):
     * - warehouse_stock: se asume igual al stock físico contado ese día
     *   (tras un conteo el almacén se ajusta al físico).
     * - kardex_stock: reconstruido por fechas con las mismas reglas de la
     *   vista v_kardex_stock (compras status 1,2,3 / ventas status 1,2,3,4,5)
     *   usando los status actuales (mejor aproximación disponible).
     */
    public function up(): void
    {
        DB::statement("
            UPDATE inventory_counts
            SET warehouse_stock = counted_stock
            WHERE warehouse_stock IS NULL
              AND counted_date >= '2026-07-01'
        ");

        $pending = DB::table('inventory_counts')
            ->whereNull('kardex_stock')
            ->where('counted_date', '>=', '2026-07-01')
            ->select('article_id', 'counted_date')
            ->distinct()
            ->get();

        foreach ($pending as $row) {
            $purchases = DB::table('purchase_details as pd')
                ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
                ->where('pd.article_id', $row->article_id)
                ->whereIn('p.status', [1, 2, 3])
                ->whereNull('pd.deleted_at')
                ->whereNull('p.deleted_at')
                ->whereDate('p.created_at', '<=', $row->counted_date)
                ->sum('pd.quantity');

            $sales = DB::table('sale_details as sd')
                ->join('sales as s', 's.id', '=', 'sd.sale_id')
                ->where('sd.article_id', $row->article_id)
                ->whereIn('s.status', [1, 2, 3, 4, 5])
                ->whereNull('sd.deleted_at')
                ->whereNull('s.deleted_at')
                ->whereRaw('DATE(COALESCE(s.date, s.created_at)) <= ?', [$row->counted_date])
                ->sum('sd.quantity');

            DB::table('inventory_counts')
                ->where('article_id', $row->article_id)
                ->whereDate('counted_date', $row->counted_date)
                ->whereNull('kardex_stock')
                ->update(['kardex_stock' => (int) $purchases - (int) $sales]);
        }
    }

    public function down(): void
    {
        // Sin reversa: no se puede distinguir el backfill de fotos genuinas.
    }
};
