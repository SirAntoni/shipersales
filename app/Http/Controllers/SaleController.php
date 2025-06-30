<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SaleController extends Controller implements hasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(\Spatie\Permission\Middleware\PermissionMiddleware::using('update'), only:['show']),
        ];
    }

    public function index()
    {
        return view('sales.index');
    }

    public function create()
    {
        return view('sales.create');
    }

    public function show(string $id)
    {
        return view('sales.show',compact('id'));
    }
    public function pdf($id){
        $sale = Sale::find($id);
        $pdf = Pdf::loadView('pdf.invoice',compact('sale'))->setPaper('A4', 'portrait')->setOption('defaultFont', 'DejaVu Sans');
        return $pdf->stream('invoice-' . sprintf('%06d', $id) .'.pdf');
    }

    public function commissions()
    {
        return view('sales.commissions');
    }
}
