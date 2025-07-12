<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleMarketplace;
use App\Models\Client;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;
use App\Services\MercadoLibreService;

class WebhookController extends Controller
{

    private $mercadoLibreService;

    public function __construct(MercadoLibreService $mercadoLibreService)
    {
        $this->mercadoLibreService = $mercadoLibreService;
    }

    public function handle(Request $request)
    {

        if(!$request->input('resource'))  return response()->json('campos inválidos');

        $zone  = 'America/Lima';
        $today = Carbon::today($zone)->toDateString();

        try {
            $order = $this->mercadoLibreService->getOrder($request->input('resource'));
        } catch (\Throwable $e) {
            Log::error('Error consultando ML', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al consultar MercadoLibre'], 502);
        }

        $dateML = Carbon::parse($order['date_created'])->setTimezone($zone)->toDateString();
        if ($dateML !== $today) {
            Log::warning("Orden {$order['id']} abortada: fecha incorrecta", ['date_ml' => $dateML]);
            return response()->json(['message' => 'La venta no es de hoy']);
        }
        if (Sale::where('number', $order['id'])->exists()) {
            Log::warning("Orden {$order['id']} abortada: ya existe");
            return response()->json(['message' => 'Ya existe la venta']);
        }
        if (! $this->hasApprovedStatus($order['payments'])) {
            Log::warning("Orden {$order['id']} abortada: no pagada");
            return response()->json(['message' => 'La venta no ha sido pagada']);
        }

        $codes    = collect($order['order_items'])->pluck('item.id')->all();
        $found    = ArticleMarketplace::whereIn('code', $codes)->pluck('code')->all();
        $missing  = array_diff($codes, $found);

        if (! empty($missing)) {
            Log::warning("Orden {$order['id']} abortada: faltan artículos", ['missing' => $missing]);
            return response()->json([
                'message'       => 'No se encontraron todos los artículos necesarios.',
                'missing_codes' => array_values($missing),
            ], 422);
        }

        try {
            $sale = \DB::transaction(function() use ($order, $zone) {
                $sale = Sale::create([
                    'number'            => $order['id'],
                    'date'              => Carbon::now()->toDateString(),
                    'subtotal'          => $order['total_amount'],
                    'tax'               => 0,
                    'total'             => $order['total_amount'],
                    'delivery'          => 0,
                    'delivery_fee'      => 0,
                    'client_id'         => 1,//10951,
                    'user_id'           => 1,
                    'contact_id'        => 3,
                    'payment_method_id' => 4,
                    'status'            => Sale::SALE_PENDING,
                    'webhook_imported' => true
                ]);

                foreach ($order['order_items'] as $item) {
                    $mp  = ArticleMarketplace::firstWhere('code', $item['item']['id']);
                    $art = Article::findOrFail($mp->article_id);

                    $sale->saleDetails()->create([
                        'price'       => $item['unit_price'],
                        'quantity'    => $item['quantity'],
                        'tax'         => 0,
                        'total'       => $item['unit_price'] * $item['quantity'],
                        'article_id'  => $mp->article_id,
                        'category_id' => $art->category_id,
                        'brand_id'    => $art->brand_id,
                        'subtotal'    => $item['unit_price'] * $item['quantity'],
                    ]);

                    $art->decrement('stock', $item['quantity']);
                }

                return $sale;
            });
        } catch (\Throwable $e) {
            Log::error('Error guardando venta completa', [
                'order_id' => $order['id'],
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Error al guardar la venta']);
        }

        Log::info("Orden {$order['id']} guardada con éxito");
        return response()->json($sale);

    }

    private function hasApprovedStatus(array $payments): bool
    {
        return collect($payments)
            ->pluck('status')
            ->contains('approved');
    }

}
