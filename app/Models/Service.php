<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Service extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'description',
        'price',
        'tax_rate',
        'profit_margin',
        'category',
        'duration',
        'delivery_time',
        'features',
        'status',
        'is_available',
        'requires_consultation',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'is_available' => 'boolean',
        'requires_consultation' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'is_available' => true,
        'requires_consultation' => false,
        'tax_rate' => 16,
        'profit_margin' => 50,
        'price' => 0,
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('status', 'active');
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->price + ($this->price * $this->tax_rate / 100);
    }

    public function getMinSellingPriceAttribute(): float
    {
        return $this->price * (1 + $this->profit_margin / 100);
    }
}