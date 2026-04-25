<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\Provider;

class Reports extends Component
{
    public $date;
    public $startDate;
    public $endDate;
    public $month;
    public $year;
    public $provider;
    public $providers = [];

    public function mount(){
        $this->providers = Provider::ordered();
    }

    public function reportDayli(){
        $this->validate([
            'date' => 'required|date',
        ]);

        $url = route('reports.dayli.export',['date' => $this->date]);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function reportCustom(){
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date',
        ]);

        $url = route('reports.custom.export',['startDate' => $this->startDate,'endDate' => $this->endDate]);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);

    }

    public function reportMonth(){
        $this->validate([
            'month' => 'required',
            'year' => 'required',
            'provider' => 'required',
        ]);

        $url = route('reports.month.export',['month' => $this->month,'year' => $this->year,'provider' => $this->provider]);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);

    }

    public
    function render()
    {
        return view('livewire.reports.reports');
    }
}
