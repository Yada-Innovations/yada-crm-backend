<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Quote extends Model
{
    use HasUuids;

    protected $fillable = [
        'quote_number',
        'id',
        'client_id',
        'service_id',
        'features',
        'subtotal',
        'tax',
        'tax_rate',
        'total',
        'valid_until',
        'status',
        'notes',
        'supporting_document',
        'created_by',
    ];

    protected $casts = [
        'features' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'valid_until' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'tax_rate' => 16,
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ];

    /**
     * Get the client for this quote.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the service for this quote.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who created this quote.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by client.
     */
    public function scopeClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Calculate total from features.
     */
    public function calculateTotal(): void
    {
        $subtotal = 0;
        
        if ($this->features) {
            foreach ($this->features as $feature) {
                $subtotal += ($feature['quantity'] ?? 0) * ($feature['unit_price'] ?? 0);
            }
        }
        
        $this->subtotal = $subtotal;
        $this->tax = ($subtotal * $this->tax_rate) / 100;
        $this->total = $this->subtotal + $this->tax;
        
        $this->save();
    }
}