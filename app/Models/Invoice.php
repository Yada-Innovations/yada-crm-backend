<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'invoice_number',
        'client_id',
        'quote_id',
        'subtotal',
        'tax',
        'tax_rate',
        'discount_pct',
        'total',
        'margin_pct',
        'status',
        'etims_status',
        'etims_code',
        'created_by',
        'due_date',
        'issue_date',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'total' => 'decimal:2',
        'margin_pct' => 'decimal:2',
        'due_date' => 'date',
        'issue_date' => 'date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function workDone() { return $this->hasMany(WorkDone::class); }
}