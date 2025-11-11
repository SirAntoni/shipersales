<?php

namespace App\Http\Controllers;

use App\Fakers\Ecommerce;
use App\Fakers\Transactions;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        return view('dashboard.index');
    }

    public function inventory(){
        return view('dashboard.inventory');
    }
}
