<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_number', 'client_id', 'subtotal',
        'discount_pct', 'total', 'margin_pct',
        'status', 'etims_status', 'etims_code',
        'created_by', 'due_date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}