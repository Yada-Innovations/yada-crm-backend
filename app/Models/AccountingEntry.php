<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AccountingEntry extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'category',
        'description',
        'amount',
        'entry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    public function scopeCost($query)
    {
        return $query->where('type', 'cost');
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }
}