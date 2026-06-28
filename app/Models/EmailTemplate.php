<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmailTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'subject', 'body', 'variables', 'category', 'active', 'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'active' => 'boolean'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}