<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'name',
        'document_number',
        'document_type',
        'address',
        'phone',
        'email'
    ];

    public function purchases(){
        return $this->hasMany(Purchase::class);
    }

    public function articles(){
        return $this->hasMany(Article::class);
    }

    public static function defaultId(): ?int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached ?: null;
        }
        $cached = (int) self::whereRaw('UPPER(name) = ?', ['SHIPERSALES'])->value('id');
        return $cached ?: null;
    }

    public static function ordered()
    {
        $defaultId = self::defaultId();
        $query = self::query();
        if ($defaultId) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$defaultId]);
        }
        return $query->orderBy('name')->get(['id', 'name']);
    }
}
