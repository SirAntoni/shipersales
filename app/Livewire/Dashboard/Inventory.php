<?php

namespace App\Livewire\Dashboard;

use App\Models\Department;
use App\Models\Purchase;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class Inventory extends Component
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

    public $userId = '';        // filtro de usuario ('' = todos)
    public $users = [];         // lista para el select
    public $userSessions = [];  // data para la tabla

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
        $this->users = DB::table('users')->select('id','name')->orderBy('name')->get();

    }

    public function loadUserSessions(): void
    {
        $this->userSessions = DB::table('inventory_sessions as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->when($this->userId !== '' && $this->userId !== null, fn($q) => $q->where('s.user_id', $this->userId))
            ->when($this->year,  fn($q) => $q->whereYear('s.count_date', $this->year))
            ->when($this->month, fn($q) => $q->whereMonth('s.count_date', $this->month))
            ->select([
                's.id',
                'u.name as user_name',
                's.count_date',
                DB::raw('COALESCE(s.duration_sec, 0) as duration_sec'),
                's.started_at',
                's.finished_at',
                's.note',
            ])
            ->orderByDesc('s.count_date')
            ->orderByDesc('s.id')
            ->get()
            ->toArray();
    }

    public function updatedUserId($val)
    {
        $this->userId = $val;
    }
    public function updatedMonth($month)
    {
        $this->month = $month;
    }

    public function updatedYear($year)
    {
        $this->year = $year;
    }


    public function updatingDepartment($department)
    {
        $departments = Department::with('districts')->find($department);
        $this->districts = $departments->districts;
        $this->department = $department;
    }

    public function updatedCategory($category)
    {
        $this->category = $category;
    }

    public function inventoryMonthlyAvgByUser(int $year, int $month): array
    {
        // Rango del mes [start, end)
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = (clone $start)->addMonth()->startOfDay();

        $sql = <<<SQL
WITH RECURSIVE d AS (
    SELECT DATE(?) AS dt
    UNION ALL
    SELECT DATE_ADD(dt, INTERVAL 1 DAY)
    FROM d
    WHERE DATE_ADD(dt, INTERVAL 1 DAY) < DATE(?)
),
workdays AS (
    -- Excluir domingos (1 = domingo en MySQL)
    SELECT dt FROM d WHERE DAYOFWEEK(dt) <> 1
),
workday_count AS (
    SELECT COUNT(*) AS n FROM workdays
),
perday AS (
    -- Máxima duración por usuario y día, solo dentro del mes y sin domingos
    SELECT s.user_id,
           s.count_date,
           MAX(s.duration_sec) AS dur_sec
    FROM inventory_sessions s
    WHERE s.count_date >= DATE(?) AND s.count_date < DATE(?)
      AND DAYOFWEEK(s.count_date) <> 1
    GROUP BY s.user_id, s.count_date
),
peruser AS (
    -- Suma de duraciones del mes (sin domingos); si un día no llenó, luego se "compensa" al dividir entre n workdays
    SELECT p.user_id,
           COALESCE(SUM(p.dur_sec), 0) AS sum_sec
    FROM perday p
    GROUP BY p.user_id
)
SELECT u.name,
       -- Forzar división decimal para no caer en división entera
       (CAST(peruser.sum_sec AS DECIMAL(18,6)) / workday_count.n) AS avg_sec
FROM peruser
JOIN users u ON u.id = peruser.user_id
CROSS JOIN workday_count
ORDER BY avg_sec DESC
SQL;

        $rows = DB::select($sql, [
            $start->toDateString(), // d: inicio
            $end->toDateString(),   // d: fin
            $start->toDateString(), // perday: inicio
            $end->toDateString(),   // perday: fin
        ]);

        $labels = [];
        $times  = []; // minutos (si prefieres segundos, usa (float)$r->avg_sec)
        foreach ($rows as $r) {
            $labels[] = $r->name;
            $times[]  = round(((float)$r->avg_sec) / 60, 2);
        }

        return ['labels' => $labels, 'times' => $times];
    }


    public function render()
    {
        $inventoryMonthlyAvgByUser = $this->inventoryMonthlyAvgByUser($this->year, $this->month);
        $this->loadUserSessions();

        // Chart
        $this->dispatch('dashboard-report', [ $inventoryMonthlyAvgByUser ]);

        return view('livewire.dashboard.inventory');
    }
}
