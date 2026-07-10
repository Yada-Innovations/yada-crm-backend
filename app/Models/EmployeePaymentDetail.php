<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeePaymentDetail extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id', // Already using user_id
        'base_salary',
        'housing_allowance',
        'transport_allowance',
        'medical_allowance',
        'other_allowances',
        'bonus',
        'bank_name',
        'bank_account',
        'bank_branch',
        'payment_frequency',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'bonus' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}