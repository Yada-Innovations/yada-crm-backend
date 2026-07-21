<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PurchaseRequisition extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'requisition_number',
        'requested_by',
        'vendor_id',
        'department',
        'item_description',
        'quantity',
        'estimated_cost',
        'justification',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'requisition_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}