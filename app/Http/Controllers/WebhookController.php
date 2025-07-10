<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;

class WebhookController extends Controller
{
    public function handle(Request $request){

        Log::info("query" . json_encode($request->query()));
        Log::info("all " . json_encode($request->all()));
        Log::info('resource: ' . $request->input('resource'));
        return response()->json($request->all());

    }
}
