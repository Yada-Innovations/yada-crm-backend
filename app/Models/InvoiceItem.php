<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price', 'total'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
}