<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'invoice_number',
        'client_id',
        'work_order_id',
        'lead_id',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'tax_rate',
        'total',
        'status',
        'notes',
        'discount_pct',
        'margin_pct',
        'etims_code',
        'etims_status',
        'created_by',
        // 'updated_by', // COMMENTED OUT - column doesn't exist in database
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'margin_pct' => 'decimal:2',
    ];

    /**
     * Get the client that owns the invoice
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the work order that owns the invoice
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkDone::class, 'work_order_id');
    }

    /**
     * Get the lead that owns the invoice
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who created the invoice
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // COMMENTED OUT - column doesn't exist in database
    // public function updater(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'updated_by');
    // }

    /**
     * Get all payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all invoice items
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the total paid amount for this invoice
     */
    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Get the remaining balance for this invoice
     */
    public function getBalanceAttribute(): float
    {
        return (float) $this->total - $this->paid_amount;
    }

    /**
     * Get the payment status for this invoice
     */
    public function getPaymentStatusAttribute(): string
    {
        $balance = $this->balance;
        if ($balance <= 0) {
            return 'paid';
        }
        if ($balance < $this->total) {
            return 'partial';
        }
        return 'unpaid';
    }

    /**
     * Get the payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $statuses = [
            'paid' => 'Fully Paid',
            'partial' => 'Partial Payment',
            'unpaid' => 'Unpaid',
        ];
        return $statuses[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Get the payment status color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        $colors = [
            'paid' => '#10B981',
            'partial' => '#F59E0B',
            'unpaid' => '#EF4444',
        ];
        return $colors[$this->payment_status] ?? '#6B7280';
    }

    /**
     * Get the payment status background color
     */
    public function getPaymentStatusBgAttribute(): string
    {
        $colors = [
            'paid' => '#D1FAE5',
            'partial' => '#FEF3C7',
            'unpaid' => '#FEE2E2',
        ];
        return $colors[$this->payment_status] ?? '#F3F4F6';
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if invoice has partial payment
     */
    public function hasPartialPayment(): bool
    {
        return $this->payment_status === 'partial';
    }

    /**
     * Check if invoice is unpaid
     */
    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->isFullyPaid()) {
            return false;
        }
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date->isPast();
    }

    /**
     * Scope a query to only include paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'overdue']);
    }

    /**
     * Scope a query to only include overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function($q) {
                $q->whereIn('status', ['sent', 'partial'])
                  ->whereDate('due_date', '<', now());
            });
    }

    /**
     * Get formatted total with currency
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'KES ' . number_format((float) $this->total, 2);
    }

    /**
     * Get formatted paid amount with currency
     */
    public function getFormattedPaidAttribute(): string
    {
        return 'KES ' . number_format($this->paid_amount, 2);
    }

    /**
     * Get formatted balance with currency
     */
    public function getFormattedBalanceAttribute(): string
    {
        return 'KES ' . number_format($this->balance, 2);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Generate UUID if not set
            if (empty($invoice->id)) {
                $invoice->id = (string) Str::uuid();
            }
            
            // Set created_by if not set - try to get authenticated user
            if (empty($invoice->created_by)) {
                try {
                    if (Auth::check()) {
                        $invoice->created_by = Auth::id();
                    }
                } catch (\Exception $e) {
                    // Auth not available, skip
                }
            }
            
            // Generate invoice number if not set
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumberStatic();
            }
        });

        // COMMENTED OUT - updated_by column doesn't exist in database
        // static::updating(function ($invoice) {
        //     // Set updated_by if user is authenticated
        //     try {
        //         if (Auth::check()) {
        //             $invoice->updated_by = Auth::id();
        //         }
        //     } catch (\Exception $e) {
        //         // Auth not available, skip
        //     }
        // });
    }

    /**
     * Generate invoice number (static version for use in boot)
     * Format: INV-YYYYMMDD-0001
     * Example: INV-20260705-0001, INV-20260705-0002, etc.
     */
    protected static function generateInvoiceNumberStatic(): string
    {
        $prefix = 'INV';
        $date = date('Ymd'); // YYYYMMDD format
        $today = today();
        
        try {
            // Count invoices created today to get the sequence number
            $count = static::whereDate('created_at', $today)
                ->count() + 1;
        } catch (\Exception $e) {
            $count = 1;
        }
        
        // Pad the count to 4 digits (0001, 0002, etc.)
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        return sprintf('%s-%s-%s', $prefix, $date, $sequence);
    }

    /**
     * Generate invoice number (instance version)
     */
    public function generateInvoiceNumber(): string
    {
        return static::generateInvoiceNumberStatic();
    }
}