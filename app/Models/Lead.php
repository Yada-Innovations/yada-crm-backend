<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Lead extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'client_id', // Add this
        'name',
        'email',
        'phone',
        'company',
        'title',
        'status',
        'source',
        'notes',
        'estimated_value',
        'address',
        'city',
        'country',
        'industry',
        'company_size',
        'website',
        'priority',
        'sales_stage',
        'assigned_to',
        'expected_close_date',
        'signature',
        'signed_at',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'signed_at' => 'datetime',
    ];

    // Relationship to client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}