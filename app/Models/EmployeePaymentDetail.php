<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeePaymentDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id', 'base_salary', 'housing_allowance', 'transport_allowance',
        'medical_allowance', 'other_allowances', 'bonus', 'bank_name',
        'bank_account', 'bank_branch', 'payroll_group', 'payment_frequency',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'base_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'bonus' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTotalCompensationAttribute()
    {
        return $this->base_salary + $this->housing_allowance +
               $this->transport_allowance + $this->medical_allowance +
               $this->other_allowances + $this->bonus;
    }
}