<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index');
    }
    public function create()
    {
        return view('purchases.create');
    }
    public function show(string $id)
    {
        return view('purchases.show',compact('id'));
    }

    public function usa()
    {
        return view('purchases.usa');
    }
}
