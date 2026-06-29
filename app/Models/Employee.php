<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'employee_number', 'first_name', 'last_name', 'email',
        'phone', 'position', 'department', 'employment_type', 'hire_date',
        'termination_date', 'status', 'profile_picture', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function paymentDetail()
    {
        return $this->hasOne(EmployeePaymentDetail::class);
    }

    public function agreements()
    {
        return $this->hasMany(EmployeeAgreement::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}