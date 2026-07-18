<?php

namespace App\Livewire\Kardex;

use App\Models\Article;
use Livewire\Component;
use App\Models\PurchaseDetail;
use App\Models\SaleDetail;
use Illuminate\Support\Facades\DB;
class Kardex extends Component
{

    public $kardex = [];
    public $article;


    public function searchArticles($query)
    {
        return Article::query()
            ->active()
            ->where(fn($q) => $q
                ->where('title', 'like', '%'.$query.'%')
                ->orWhere('sku',   'like', '%'.$query.'%')
            )
            ->limit(10)
            ->get(['id', 'title','sku'])
            ->map(fn($c) => [
                'value' => $c->id,
                'text'  => $c->title . ' - ' . $c->sku,
            ])
            ->toArray();
    }

    public function updatedArticle($id)
    {
        $this->article = $id;
        $this->getKardex();
    }

    public function getKardex()
    {
        if (!$this->article) {
            $this->kardex = [];
            return;
        }

        // COMPRAS (entradas) -> excluir solo canceladas (status = 0)
        $purchaseMovements = \App\Models\PurchaseDetail::query()
            ->selectRaw("
            purchase_details.id AS detail_id,
            'purchase'          AS src,
            purchase_details.article_id,
            articles.title      AS article_name,
            providers.name      AS provider_name,
            NULL                AS contact_name,
            NULL                AS client_name,
            users.name          AS user_name,
            purchases.`number`  AS `number`,
            purchases.created_at AS fecha,
            'entrada'           AS tipo,
            purchase_details.quantity AS cantidad,
            purchases.document AS document,
            purchases.passenger AS passenger
        ")
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->join('articles',  'articles.id',  '=', 'purchase_details.article_id')
            ->join('users',     'users.id',     '=', 'purchases.user_id')
            ->leftJoin('providers', 'providers.id', '=', 'purchases.provider_id')
            ->where('purchases.status', '<>', 0)             // 👈 solo filtra estado 0
            ->where('purchase_details.article_id', $this->article);

        // VENTAS (salidas) -> excluir solo canceladas (status = 0)
        $saleMovements = \App\Models\SaleDetail::query()
            ->selectRaw("
            sale_details.id     AS detail_id,
            'sale'              AS src,
            sale_details.article_id,
            articles.title      AS article_name,
            NULL                AS provider_name,
            contacts.name       AS contact_name,
            clients.name        AS client_name,
            users.name          AS user_name,
            sales.`number`      AS `number`,
            sales.created_at AS fecha,
            'salida'            AS tipo,
            sale_details.quantity AS cantidad,
             NULL                AS document,
             NULL                AS passenger
        ")
            ->join('sales',   'sales.id',   '=', 'sale_details.sale_id')
            ->join('users',   'users.id',   '=', 'sales.user_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'sales.contact_id')
            ->leftJoin('clients',  'clients.id',  '=', 'sales.client_id')
            ->join('articles', 'articles.id', '=', 'sale_details.article_id')
            ->where('sales.status', '<>', 0)                 // 👈 solo filtra estado 0
            ->where('sale_details.article_id', $this->article);

        // AJUSTES DE INVENTARIO (conteo físico / regularizaciones)
        $adjustmentMovements = \DB::table('inventory_adjustments as ia')
            ->selectRaw("
            ia.id               AS detail_id,
            'adjustment'        AS src,
            ia.article_id,
            articles.title      AS article_name,
            NULL                AS provider_name,
            NULL                AS contact_name,
            NULL                AS client_name,
            users.name          AS user_name,
            CONCAT('Ajuste — ', ia.reason) AS `number`,
            ia.created_at       AS fecha,
            CASE WHEN ia.delta >= 0 THEN 'entrada' ELSE 'salida' END AS tipo,
            ABS(ia.delta)       AS cantidad,
            CONCAT('Ajuste — ', ia.reason) AS document,
            NULL                AS passenger
        ")
            ->join('articles', 'articles.id', '=', 'ia.article_id')
            ->leftJoin('users', 'users.id', '=', 'ia.created_by')
            ->where('ia.delta', '<>', 0)
            ->where('ia.article_id', $this->article);

        // UNION ALL (no perder movimientos válidos)
        $movements = $purchaseMovements->unionAll($saleMovements)->unionAll($adjustmentMovements);

        // Saldo acumulado en SQL (orden estable por fecha, fuente y detail_id)
        $rows = \DB::query()
            ->fromSub($movements, 'm')
            ->selectRaw("
            m.*,
            CASE WHEN m.tipo='entrada' THEN m.cantidad ELSE -m.cantidad END AS signed_qty,
            SUM(CASE WHEN m.tipo='entrada' THEN m.cantidad ELSE -m.cantidad END)
              OVER (ORDER BY m.fecha, m.src, m.detail_id) AS saldo
        ")
            // Mostrar lo más reciente primero
            ->orderByDesc('m.fecha')
            ->orderByDesc('m.src')
            ->orderByDesc('m.detail_id')
            ->get();

        $this->kardex = $rows;
    }





    public function render()
    {
        return view('livewire.kardex.kardex');
    }
}
