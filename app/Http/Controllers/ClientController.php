<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ClientController extends Controller // implements hasMiddleware
{
//    public static function middleware(): array
//    {
//        return [
//            new Middleware(\Spatie\Permission\Middleware\PermissionMiddleware::using('update'), only:['show']),
//        ];
//    }
    public function index()
    {
        return view('clients.index');
    }
    public function create()
    {
        return view('clients.create');
    }
    public function show(string $id)
    {
        return view('clients.show',compact('id') );
    }
}
