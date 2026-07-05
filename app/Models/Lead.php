<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Lead extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'contact_name',
        'company_name',
        'email',
        'phone',
        'title',
        'status',
        'priority',
        'sales_stage',
        'score',
        'estimated_value',
        'currency',
        'expected_close_date',
        'notes',
        'source',
        'industry',
        'company_size',
        'website',
        'address',
        'city',
        'state',
        'country',
        'assigned_to',
        'client_id',  // ← Make sure this is here
        'signature',
        'signed_at',
        'disqualification_reason',
        'disqualified_at',
        'disqualified_by',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'score' => 'integer',
        'expected_close_date' => 'date',
        'signed_at' => 'datetime',
        'disqualified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'new',
        'priority' => 'medium',
        'sales_stage' => 'prospecting',
        'score' => 0,
        'currency' => 'KES',
        'country' => 'Kenya',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}