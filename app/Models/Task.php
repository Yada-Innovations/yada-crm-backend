<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Task extends Model
{
    use HasUuids;

    protected $fillable = [
        'title', 'description', 'lead_id',
        'assigned_to', 'created_by', 'stage',
        'due_date', 'completed',
    ];

    protected $casts = ['completed' => 'boolean'];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}