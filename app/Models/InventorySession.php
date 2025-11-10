<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySession extends Model
{
    protected $fillable = [
        'count_date','user_id','started_at','finished_at',
        'duration_sec','total_rows','completed_rows','note'
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}
