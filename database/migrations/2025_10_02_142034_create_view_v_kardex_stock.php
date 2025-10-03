<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<SQL
CREATE OR REPLACE VIEW v_kardex_stock AS
SELECT a.id AS article_id,
       COALESCE(SUM(m.qty_signed), 0) AS kardex_stock
FROM articles a
LEFT JOIN (
  -- Entradas (compras) como positivo
  SELECT pd.article_id, pd.quantity AS qty_signed
  FROM purchase_details pd
  JOIN purchases p ON p.id = pd.purchase_id
  WHERE p.status IN (1,2,3)
    AND pd.deleted_at IS NULL
    AND p.deleted_at IS NULL

  UNION ALL

  -- Salidas (ventas) como negativo
  SELECT sd.article_id, -sd.quantity AS qty_signed
  FROM sale_details sd
  JOIN sales s ON s.id = sd.sale_id
  WHERE s.status IN (1,2,3)
    AND sd.deleted_at IS NULL
    AND s.deleted_at IS NULL
) m ON m.article_id = a.id
GROUP BY a.id
SQL
        );
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_kardex_stock');
    }
};
