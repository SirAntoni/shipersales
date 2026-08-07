<?php

namespace App\Http\Controllers;

use App\Exports\DocumentsExport;
use App\Exports\ReportArticlesExport;
use App\Exports\ReportOnDemandProductsExport;
use App\Exports\ReportCommissionExport;
use App\Exports\ReportDayliExport;
use App\Exports\ReportCustomExport;
use App\Exports\ReportMonthExport;
use App\Livewire\Documents\TableDocuments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(){
        return view('reports.index');
    }

    public function dayli(Request $request){

        $request->validate([
            'date' => 'required',
        ]);

        return Excel::download(new ReportDayliExport($request->query('date')), 'reporte-diario-'. $request->query('date') . '.xlsx');
    }

    public function custom(Request $request){

        $request->validate([
            'startDate' => 'required',
            'endDate' => 'required',
        ]);

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        return Excel::download(new ReportCustomExport($startDate,$endDate), 'reporte-custom-'. $startDate . '-'.$endDate .'.xlsx');
    }

    public function month(Request $request){

        $request->validate([
            'month' => 'required',
            'year' => 'required',
            'provider' =>  'required'
        ]);

        $month = $request->query('month');
        $monthName = Carbon::createFromFormat('!m', $month)
            ->locale('es')
            ->isoFormat('MMMM');
        $year = $request->query('year');
        $provider = $request->query('provider');

        return Excel::download(new ReportMonthExport($month,$year,$provider), 'reporte-mensual-'. $monthName . '-'.$year .'.xlsx');
    }

    public function articles(){
        return Excel::download(new ReportArticlesExport(), 'reporte-articles.xlsx');
    }

    public function onDemandProducts(){
        return Excel::download(new ReportOnDemandProductsExport(), 'reporte-productos-a-pedido.xlsx');
    }

    public function commissions(Request $request){

        $request->validate([
            'month' => 'required',
            'year' => 'required'
        ]);

        $month = $request->query('month');
        $monthName = Carbon::createFromFormat('!m', $month)
            ->locale('es')
            ->isoFormat('MMMM');
        $year = $request->query('year');

        return Excel::download(new ReportCommissionExport($month,$year), 'reporte-comisiones-'. $monthName . '-'.$year .'.xlsx');

    }

    public function documents(Request $request){

        // Todos los filtros son opcionales (son los mismos de /documents), pero
        // llegan por query string: sin validar, un mes tipeado a mano tumbaba
        // la descarga con un 500.
        // Nada de la regla 'integer' aqui: rechaza los meses con cero delante
        // ("07" no es un entero valido para filter_var) y dejaria fuera de enero
        // a setiembre. El 'string' con bail ademas frena los ?anio[]=x.
        $datos = $request->validate([
            'search'      => 'nullable|string|max:100',
            'statusSunat' => ['nullable', 'bail', 'string', Rule::in(['aceptado', 'pendiente', 'rechazado', 'anulado'])],
            'anio'        => ['nullable', 'bail', 'string', 'regex:/^\d{4}$/'],
            'mes'         => ['nullable', 'bail', 'string', Rule::in(array_keys(TableDocuments::MESES))],
        ]);

        $anio = $datos['anio'] ?? null;
        $mes = $datos['mes'] ?? null;

        $periodo = str_replace(' ', '-', TableDocuments::etiquetaPeriodo($anio, $mes));

        return Excel::download(new DocumentsExport(
            $datos['search'] ?? null,
            $datos['statusSunat'] ?? null,
            $anio,
            $mes
        ), 'reporte-comprobantes-'. $periodo .'.xlsx');

    }
}
