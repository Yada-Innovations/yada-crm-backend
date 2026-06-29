<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payroll extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id', 'period', 'period_start', 'period_end',
        'basic_salary', 'housing_allowance', 'transport_allowance',
        'medical_allowance', 'other_allowances', 'bonus',
        'gross_pay', 'tax_paye', 'nssf_employee', 'nssf_employer',
        'ahl', 'other_deductions', 'net_pay', 'employer_cost',
        'status', 'notes', 'created_by', 'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'basic_salary' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'tax_paye' => 'decimal:2',
        'nssf_employee' => 'decimal:2',
        'nssf_employer' => 'decimal:2',
        'ahl' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'employer_cost' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}