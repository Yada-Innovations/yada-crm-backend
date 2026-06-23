<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Demo extends Model
{
    use HasUuids;

    protected $fillable = [
        'lead_id', 'scheduled_at', 'status', 'notes', 'conducted_by',
    ];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function conductor() { return $this->belongsTo(User::class, 'conducted_by'); }
}