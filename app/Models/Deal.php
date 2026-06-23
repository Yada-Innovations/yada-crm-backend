<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Deal extends Model
{
    use HasUuids;

    protected $fillable = [
        'lead_id', 'client_id', 'value', 'status', 'closed_at', 'closed_by',
    ];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function closer() { return $this->belongsTo(User::class, 'closed_by'); }
}