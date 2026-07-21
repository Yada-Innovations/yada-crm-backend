<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkOrder extends Model
{
    use HasUuids;

    protected $table = 'work_done';

    protected $fillable = [
        'id',
        'client_id',
        'quote_id',
        'lead_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'start_date',
        'end_date',
        'completion_date',
        'estimated_hours',
        'actual_hours',
        'amount',
        'tax_rate',
        'total_amount',
        'invoice_id',
        'notes',
        'assigned_to',
        'created_by',
        'technical_review_date',
        'technical_review_notes',
        'technical_review_approved',
        'book_of_technical_reviews_date',
        'book_of_technical_reviews_reference',
        'technical_reviewer',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completion_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'technical_review_date' => 'date',
        'technical_review_approved' => 'boolean',
        'book_of_technical_reviews_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'priority' => 'medium',
        'type' => 'development',
        'tax_rate' => 16,
        'amount' => 0,
        'total_amount' => 0,
        'estimated_hours' => 0,
        'actual_hours' => 0,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}