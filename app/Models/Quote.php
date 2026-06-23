<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Quote extends Model
{
    use HasUuids;

    protected $fillable = [
        'lead_id', 'base_amount', 'discount_pct',
        'final_amount', 'margin_pct', 'status',
        'created_by', 'valid_until',
    ];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}