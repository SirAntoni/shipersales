<?php

namespace App\Http\Controllers;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class OnDemandProductController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            new Middleware(\Spatie\Permission\Middleware\PermissionMiddleware::using('articles.create'), only:['create']),
            new Middleware(\Spatie\Permission\Middleware\PermissionMiddleware::using('update'), only:['show']),
        ];
    }

    public function index()
    {
        return view('on-demand-products.index');
    }
    public function create()
    {
        return view('on-demand-products.create');
    }
    public function show(string $id)
    {
        return view('on-demand-products.show',compact('id'));
    }
}
