<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function index(){
        return view('kardex.index');
    }
}
