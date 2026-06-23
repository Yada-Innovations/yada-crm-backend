<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id', 'amount', 'method', 'reference', 'paid_at', 'recorded_by',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}