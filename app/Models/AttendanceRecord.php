<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
        'hours_worked',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'hours_worked' => 'decimal:2',
    ];

    /**
     * Automatically include the related user in JSON output (see getUserAttribute below),
     * so API responses keep the same "user" shape the frontend already expects.
     */
    protected $appends = ['user'];

    // ── Relationships ──

    /**
     * The employee this attendance record belongs to.
     * attendance_records.employee_id references employees.id.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /**
     * Convenience accessor: expose the User behind this record's Employee.
     *
     * NOTE: Named as an accessor (getUserAttribute), NOT as an Eloquent relationship
     * method, so it's safe to eager-load 'employee.user' and still read $record->user
     * without any risk of Spatie-style relation/accessor collisions.
     */
    public function getUserAttribute()
    {
        return $this->employee?->user;
    }

    // ── Helper Methods ──

    /**
     * Calculate worked hours
     */
    public function calculateWorkedHours()
    {
        if ($this->check_in && $this->check_out) {
            $diff = $this->check_out->diffInMinutes($this->check_in);
            $hours = $diff / 60;
            $this->hours_worked = round($hours, 2);
            $this->save();
            return $this->hours_worked;
        }
        return 0;
    }

    /**
     * Check if user is currently checked in (no check out)
     */
    public function isCheckedIn()
    {
        return $this->check_in && !$this->check_out;
    }

    // ── Scopes ──

    /**
     * Scope for today's attendance
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', now()->toDateString());
    }

    /**
     * Scope for a specific month
     */
    public function scopeMonth($query, $month, $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    /**
     * Scope for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for a specific employee (by employees.id)
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope for active users only (via employee -> user)
     */
    public function scopeActiveUsers($query)
    {
        return $query->whereHas('employee.user', function ($q) {
            $q->where('status', 'active');
        });
    }

    /**
     * Scope for present status
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope for absent status
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    /**
     * Scope for late status
     */
    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }
}