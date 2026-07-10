<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeAgreement extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',  // Changed from employee_id
        'type',
        'title',
        'description',
        'signed_date',
        'status',
    ];

    protected $casts = [
        'signed_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // For backward compatibility
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}