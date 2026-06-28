<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'link', 'read', 'data'
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}