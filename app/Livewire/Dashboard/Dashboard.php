<?php

namespace App\Livewire\Dashboard;

use App\Models\Department;
use App\Models\Purchase;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;
use Carbon\CarbonPeriod;

class Dashboard extends Component
{

    public $month;
    public $year;
    public $provider;
    public $providers;
    public $category;
    public $categories;
    public $department;
    public $district;
    public $districts;
    public $departments;
    public $top10Products = [];
    public $margenGananciasProveedor = [];
    public $margenGananciasCategory = [];

    public $exchange;
    public $filterChart = "Ganancia";

    public function mount()
    {
        $providers = DB::table('providers')->select('id', 'name')->get();
        $categories = DB::table('categories')->select('id', 'name')->get();
        $departments = DB::table('departments')->select('id', 'name')->get();
        $this->providers = $providers;
        $this->categories = $categories;
        $this->departments = $departments;
        $this->month = Carbon::now()->format('m');
        $this->year = Carbon::now()->format('Y');
        $this->exchange = Purchase::exchangeRate();

    }


    public function getCantidadVentas(): int{
        return Cache::remember('ventas_hoy_count', 60, function () {
            return Sale::whereDate('created_at', Carbon::today())->whereIn('status',[1,2,3])->count();
        });
    }

    public function getTotalVentasHoy(): float
    {
        return Cache::remember('total_ventas_hoy', 60, function () {
            return (float) Sale::whereDate('created_at', Carbon::today())
                ->whereIn('status',[1,2,3])
                ->sum('total');
        });
    }

    public function updatingDepartment($department)
    {
        $departments = Department::with('districts')->find($department);
        $this->districts = $departments->districts;
        $this->department = $department;
        $top10Products = $this->top10Products();
        $this->dispatch('dashboard-report', $top10Products);
    }

    public function updatedCategory($category)
    {
        $this->category = $category;
        $top10Products = $this->top10Products();
        $this->dispatch('dashboard-report', $top10Products);
    }

    public function updatedMonth($month)
    {
        $this->month = $month;
        $top10Products = $this->top10Products();
        $this->dispatch('dashboard-report', $top10Products);
    }

    public function updatedYear($year)
    {
        $this->year = $year;
        $top10Products = $this->top10Products();
        $this->dispatch('dashboard-report', $top10Products);
    }

    private function getSalesChartData(): array
    {
        $end   = Carbon::now();
        $start = $end->copy()->subDays(6)->startOfDay();

        // 1) Obtenemos por día: suma de total y cantidad de ventas
        $raw = Sale::whereBetween('date', [$start, $end])
            ->whereIn('status',[1,2,3])
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy(DB::raw('DATE(date)'), 'ASC')
            ->get([
                DB::raw('DATE(date)       AS sale_date'),
                DB::raw('SUM(total)        AS total_sum'),
                DB::raw('COUNT(*)          AS total_count'),
            ]);

        // Mapear por fecha
        $totals = $raw->pluck('total_sum', 'sale_date')->toArray();
        $counts = $raw->pluck('total_count', 'sale_date')->toArray();

        // 2) Generar período de 7 días
        $period = CarbonPeriod::create($start, $end);
        $labels = [];
        $dataTotals = [];
        $dataCounts = [];

        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $labels[]       = $day;
            $dataTotals[]   = isset($totals[$day]) ? (float) $totals[$day] : 0.00;
            $dataCounts[]   = isset($counts[$day]) ? (int) $counts[$day] : 0;
        }

        return [
            'labels'      => $labels,
            'totals'      => $dataTotals,
            'counts'      => $dataCounts,
        ];
    }

    public function top10Products()
    {

        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $articles = DB::table('articles')
            ->join('sale_details', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'articles.id',
                'articles.title',
                // Suma la cantidad vendida
                DB::raw('SUM(sale_details.quantity) as total_qty'),
                // Realiza la operación por cada registro de sale_details
                DB::raw('SUM(sale_details.price - (articles.purchase_price * '. Purchase::exchangeRate() .')) as total')
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro por provider (suponiendo que es en articles, columna provider_id)
            ->when($provider, function ($query) use ($provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro por category en sale_details
            ->when($category, function ($query) use ($category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro por department en clients
            ->when($department, function ($query) use ($department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro por district en clients
            ->when($district, function ($query) use ($district) {
                return $query->where('clients.district_id', $district);
            })
            ->groupBy('articles.id', 'articles.title', 'articles.purchase_price')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $this->top10Products = $articles;
    }

    public function margenGananciaProveedor()
    {

        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $ganancias = DB::table('sale_details')
            // Unir la tabla articles para obtener el provider_id y, si fuese necesario, otros datos
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('providers', 'articles.provider_id', '=', 'providers.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'articles.provider_id as provider_id',
                'providers.name as provider_name',
                // Calcula la ganancia total aplicando la fórmula y el tipo de cambio
                DB::raw("SUM(sale_details.price - (articles.purchase_price * $this->exchange)) as total_ganancia"),
                // Calcula el margen promedio en porcentaje
                DB::raw("AVG((sale_details.price - (articles.purchase_price * $this->exchange)) / sale_details.price * 100) as margen_promedio")
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro opcional por proveedor (provider_id de la tabla articles)
            ->when($provider, function ($query, $provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro opcional por categoría en sale_details
            ->when($category, function ($query, $category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro opcional por departamento en clients
            ->when($department, function ($query, $department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro opcional por distrito en clients
            ->when($district, function ($query, $district) {
                return $query->where('clients.district_id', $district);
            })
            ->groupBy('articles.provider_id')
            ->get();

        return $ganancias;
    }

    public function margenGananciaContacto()
    {

        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $contacts = DB::table('sale_details')
            // Unir la tabla articles para obtener el provider_id y, si fuese necesario, otros datos
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('contacts', 'sales.contact_id', '=', 'contacts.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'contacts.name as contact_name',
                // Calcula la ganancia total aplicando la fórmula y el tipo de cambio
                DB::raw("SUM(sale_details.quantity) as total_qty"),
                // Realiza la operación por cada registro de sale_details
                DB::raw("SUM(sale_details.price - (articles.purchase_price * $this->exchange)) as total")
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro opcional por proveedor (provider_id de la tabla articles)
            ->when($provider, function ($query, $provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro opcional por categoría en sale_details
            ->when($category, function ($query, $category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro opcional por departamento en clients
            ->when($department, function ($query, $department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro opcional por distrito en clients
            ->when($district, function ($query, $district) {
                return $query->where('clients.district_id', $district);
            })
            ->groupBy('sales.contact_id')
            ->get();

        return $contacts;
    }

    public function margenGananciasCategory()
    {
        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $contacts = DB::table('sale_details')
            // Unir la tabla articles para obtener el provider_id y, si fuese necesario, otros datos
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
                'categories.name as category_name',
                // Calcula la ganancia total aplicando la fórmula y el tipo de cambio
                DB::raw('SUM(sale_details.quantity) as total_qty'),
                // Realiza la operación por cada registro de sale_details
                DB::raw("SUM(sale_details.price - (articles.purchase_price * $this->exchange)) as total")
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro opcional por proveedor (provider_id de la tabla articles)
            ->when($provider, function ($query, $provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro opcional por categoría en sale_details
            ->when($category, function ($query, $category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro opcional por departamento en clients
            ->when($department, function ($query, $department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro opcional por distrito en clients
            ->when($district, function ($query, $district) {
                return $query->where('clients.district_id', $district);
            })
            ->groupBy('sale_details.category_id','categories.name')
            ->get();

        return $contacts;
    }

    public function margenGananciasDepartment()
    {
        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $departments = DB::table('sale_details')
            // Unir la tabla articles para obtener el provider_id y, si fuese necesario, otros datos
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->join('departments', 'clients.department_id', '=', 'departments.id')
            ->select(
                'departments.name as department_name',
                // Calcula la ganancia total aplicando la fórmula y el tipo de cambio
                DB::raw('SUM(sale_details.quantity) as total_qty'),
                // Realiza la operación por cada registro de sale_details
                DB::raw("SUM(sale_details.price - (articles.purchase_price * $this->exchange)) as total")
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro opcional por proveedor (provider_id de la tabla articles)
            ->when($provider, function ($query, $provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro opcional por categoría en sale_details
            ->when($category, function ($query, $category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro opcional por departamento en clients
            ->when($department, function ($query, $department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro opcional por distrito en clients
            ->when($district, function ($query, $district) {
                return $query->where('clients.district_id', $district);
            })
            ->groupBy('clients.department_id')
            ->having('total_qty', '>', 0)
            ->get();

        return $departments;
    }

    public function margenGananciasDistrict()
    {
        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $districts = DB::table('sale_details')
            // Unir la tabla articles para obtener el provider_id y, si fuese necesario, otros datos
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->join('districts', 'clients.district_id', '=', 'districts.id')
            ->select(
                'districts.name as district_name',
                // Calcula la ganancia total aplicando la fórmula y el tipo de cambio
                DB::raw('SUM(sale_details.quantity) as total_qty'),
                // Realiza la operación por cada registro de sale_details
                DB::raw("SUM(sale_details.price - (articles.purchase_price * $this->exchange)) as total")
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro opcional por proveedor (provider_id de la tabla articles)
            ->when($provider, function ($query, $provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro opcional por categoría en sale_details
            ->when($category, function ($query, $category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro opcional por departamento en clients
            ->when($department, function ($query, $department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro opcional por distrito en clients
            ->when($district, function ($query, $district) {
                return $query->where('clients.district_id', $district);
            })
            ->groupBy('clients.district_id')
            ->having('total_qty', '>', 0)
            ->get();

        return $districts;
    }


    public function gananciaVentasTotal()
    {
        $month = $this->month;
        $year = $this->year;
        $provider = $this->provider;
        $category = $this->category;
        $department = $this->department;
        $district = $this->district;


        $total = DB::table('sale_details')
            // Unir la tabla articles para obtener el provider_id y, si fuese necesario, otros datos
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->select(
            // Calcula la ganancia total aplicando la fórmula y el tipo de cambio
                DB::raw("SUM(sale_details.price - (articles.purchase_price * $this->exchange)) as total_ganancias"),
                // Realiza la operación por cada registro de sale_details
                DB::raw('SUM(sale_details.price) as total_ventas')
            )
            ->whereIn('sales.status',[1,2,3])
            ->whereYear('sale_details.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sale_details.created_at', $month);
            })
            // Filtro opcional por proveedor (provider_id de la tabla articles)
            ->when($provider, function ($query, $provider) {
                return $query->where('articles.provider_id', $provider);
            })
            // Filtro opcional por categoría en sale_details
            ->when($category, function ($query, $category) {
                return $query->where('sale_details.category_id', $category);
            })
            // Filtro opcional por departamento en clients
            ->when($department, function ($query, $department) {
                return $query->where('clients.department_id', $department);
            })
            // Filtro opcional por distrito en clients
            ->when($district, function ($query, $district) {
                return $query->where('clients.district_id', $district);
            })
            ->get();

        return $total;
    }

    public function margenGananciasAnualesMensuales()
    {
        $provider   = $this->provider;
        $category   = $this->category;
        $department = $this->department;
        $district   = $this->district;
        $exchange   = $this->exchange;

        // 1) Obtenemos año, mes_nombre y suma de ganancias
        $raw       = DB::table('sale_details')
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales',   'sale_details.sale_id',   '=', 'sales.id')
            ->join('clients',  'sales.client_id',        '=', 'clients.id')
            ->whereIn('sales.status', [1,2,3])
            // filtros opcionales
            ->when($provider,   fn($q) => $q->where('articles.provider_id',    $provider))
            ->when($category,   fn($q) => $q->where('sale_details.category_id', $category))
            ->when($department, fn($q) => $q->where('clients.department_id',    $department))
            ->when($district,   fn($q) => $q->where('clients.district_id',      $district))
            // seleccionamos año, mes en español y la ganancia total
            ->select([
                DB::raw('YEAR(sale_details.created_at) AS year'),
                DB::raw("
                ELT(
                    MONTH(sale_details.created_at),
                    'enero','febrero','marzo','abril','mayo','junio',
                    'julio','agosto','septiembre','octubre','noviembre','diciembre'
                ) AS month
            "),
                DB::raw(
                    'SUM(sale_details.price - (articles.purchase_price * ' . $exchange . '))'
                    . ' AS total'
                ),
            ])
            // agrupamos por año y mes numérico para mantener orden
            ->groupBy(DB::raw('YEAR(sale_details.created_at)'))
            ->groupBy(DB::raw('MONTH(sale_details.created_at)'))
            ->orderBy(DB::raw('YEAR(sale_details.created_at)'), 'desc')
            ->orderBy(DB::raw('MONTH(sale_details.created_at)'), 'asc')
            ->whereYear('sale_details.created_at', '>=', 2024)
            ->get();

        // 2) Reagrupamos la colección en PHP: [ 2025 => [ 'enero'=>123, … ], 2024 => […], … ]
        $resultado = $raw
            ->groupBy('year')
            ->map(function ($items) {
                // convertimos cada grupo en [ 'enero' => total, ... ]
                return $items->pluck('total', 'month');
            });

        return $resultado;
    }

    public function cantidadAnualMensual()
    {
        $provider   = $this->provider;
        $category   = $this->category;
        $department = $this->department;
        $district   = $this->district;

        // 1) Ejecutamos la consulta SQL
        $raw = DB::table('sale_details')
            ->join('articles', 'sale_details.article_id', '=', 'articles.id')
            ->join('sales',   'sale_details.sale_id',   '=', 'sales.id')
            ->join('clients',  'sales.client_id',        '=', 'clients.id')
            ->whereIn('sales.status', [1,2,3])
            // filtros opcionales
            ->when($provider,   fn($q) => $q->where('articles.provider_id',    $provider))
            ->when($category,   fn($q) => $q->where('sale_details.category_id', $category))
            ->when($department, fn($q) => $q->where('clients.department_id',    $department))
            ->when($district,   fn($q) => $q->where('clients.district_id',      $district))
            ->whereYear('sale_details.created_at', '>=', 2024)
            // seleccionamos año, mes en español y la suma de quantity
            ->select([
                DB::raw('YEAR(sale_details.created_at) AS year'),
                DB::raw("
                ELT(
                    MONTH(sale_details.created_at),
                    'enero','febrero','marzo','abril','mayo','junio',
                    'julio','agosto','septiembre','octubre','noviembre','diciembre'
                ) AS month
            "),
                DB::raw('SUM(sale_details.quantity) AS total_qty'),
            ])
            // agrupamos y ordenamos para mantener years desc y months asc
            ->groupBy(DB::raw('YEAR(sale_details.created_at)'))
            ->groupBy(DB::raw('MONTH(sale_details.created_at)'))
            ->orderBy(DB::raw('YEAR(sale_details.created_at)'), 'desc')
            ->orderBy(DB::raw('MONTH(sale_details.created_at)'), 'asc')
            ->get();

        // 2) Reagrupamos en PHP para devolver { year: { month: total, … }, … }
        $data = $raw
            ->groupBy('year')
            ->map(function ($items) {
                // cada subcolección a [ 'enero'=>25, 'febrero'=>30, … ]
                return $items->pluck('total_qty', 'month');
            });

        // Si lo deseas como array puro:
        // return $data->toArray();
        return $data;
    }

    public function top10ProductsByProfit(): array
    {
        $month      = $this->month;
        $year       = $this->year;
        $provider   = $this->provider;
        $category   = $this->category;
        $department = $this->department;
        $district   = $this->district;

        $rate = Purchase::exchangeRate();

        // 1) Traer top 10 por utilidad + ventas por producto (ingreso bruto)
        $rows = DB::table('articles as a')
            ->join('sale_details as sd', 'sd.article_id', '=', 'a.id')
            ->join('sales as s', 'sd.sale_id', '=', 's.id')
            ->join('clients as c', 's.client_id', '=', 'c.id')
            ->selectRaw("
            a.id,
            a.title,
            SUM( (sd.price - (a.purchase_price * ?)) * sd.quantity ) as total_profit,
            SUM( sd.price * sd.quantity ) as product_sales
        ", [$rate])
            ->whereIn('s.status', [1,2,3])
            ->whereYear('sd.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sd.created_at', $month);
            })
            ->when($provider,   fn($q) => $q->where('a.provider_id', $provider))
            ->when($category,   fn($q) => $q->where('sd.category_id', $category))
            ->when($department, fn($q) => $q->where('c.department_id', $department))
            ->when($district,   fn($q) => $q->where('c.district_id', $district))
            ->groupBy('a.id', 'a.title')
            ->orderByDesc('total_profit')
            ->limit(10)
            ->get();

        // 2) Ventas totales del período (mismos filtros)
        $totalSales = DB::table('sale_details as sd')
            ->join('sales as s', 'sd.sale_id', '=', 's.id')
            ->join('clients as c', 's.client_id', '=', 'c.id')
            ->when($category,   fn($q) => $q->where('sd.category_id', $category))
            ->when($department, fn($q) => $q->where('c.department_id', $department))
            ->when($district,   fn($q) => $q->where('c.district_id', $district))
            ->whereIn('s.status', [1,2,3])
            ->whereYear('sd.created_at', $year)
            ->when($month, function ($query, $month) {
                return $query->whereMonth('sd.created_at', $month);
            })
            ->sum(DB::raw('sd.price * sd.quantity'));

        // 3) Armar datos para Chart.js
        $labels  = $rows->pluck('title')->values()->all();
        $profits = $rows->pluck('total_profit')->map(fn($v) => (float) $v)->values()->all();
        $sales   = $rows->pluck('product_sales')->map(fn($v) => (float) $v)->values()->all();

        // Margen % por producto usando:
        // (Precio venta − (Precio compra * TC)) / Precio venta * 100
        // A nivel agregado: total_profit / product_sales * 100
        $percents = [];
        foreach ($profits as $index => $profit) {
            $sale = $sales[$index] ?? 0.0;
            $percents[] = $sale > 0
                ? round(($profit / $sale) * 100, 2)
                : 0.0;
        }

        return [
            'labels'      => $labels,           // nombres de productos
            'totals'      => $profits,          // utilidad por producto
            'sales'       => $sales,            // ventas brutas por producto
            'percents'    => $percents,         // margen % por producto (ya con tu fórmula)
            'total_sales' => (float) $totalSales,
        ];
    }


    public function render()
    {

        $this->top10Products();
        $gananciasProveedores = $this->margenGananciaProveedor();
        $gananciasContacto = $this->margenGananciaContacto();
        $gananciasCategory = $this->margenGananciasCategory();
        $gananciasDepartment = $this->margenGananciasDepartment();
        $gananciasDistrict = $this->margenGananciasDistrict();
        $gananciasVentasTotal = $this->gananciaVentasTotal();
        $getSalesChartData = $this->getSalesChartData();
        $charRevenueAndAmount = ($this->filterChart == "Ganancia") ? $this->margenGananciasAnualesMensuales():$this->cantidadAnualMensual();


        $this->dispatch('dashboard-report', [
            $gananciasProveedores,
            $gananciasContacto,
            $gananciasCategory,
            $gananciasDepartment,
            $gananciasDistrict,
            $gananciasVentasTotal,
            $getSalesChartData,
            $charRevenueAndAmount,
            $this->top10ProductsByProfit()
        ]);

        return view('livewire.dashboard.dashboard');
    }
}
