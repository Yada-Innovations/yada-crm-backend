<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'uuid',
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'position',
        'department',
        'employee_id',
        'employment_type',
        'hire_date',
        'termination_date',
        'address',
        'city',
        'state',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'profile_picture',
        'status',
        'timezone',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Boot method to auto-generate UUID and employee_id ──
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Generate UUID if not set
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }

            // Generate employee_id if not set
            if (empty($user->employee_id)) {
                $lastEmployee = static::whereNotNull('employee_id')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastEmployee && $lastEmployee->employee_id) {
                    $lastNumber = (int) substr($lastEmployee->employee_id, 4);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $user->employee_id = 'EMP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });

        // ── Auto-create payment details after user is created ──
        static::created(function ($user) {
            // Only create payment details if user doesn't have them
            if (!EmployeePaymentDetail::where('user_id', $user->id)->exists()) {
                EmployeePaymentDetail::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'base_salary' => 0,
                    'housing_allowance' => 0,
                    'transport_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowances' => 0,
                    'bonus' => 0,
                    'payment_frequency' => 'monthly',
                ]);
            }
        });
    }

    // ── Relationships ──

    /**
     * Get the employee record associated with this user (backward compatibility)
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    /**
     * Get payment details for this user
     */
    public function paymentDetail()
    {
        return $this->hasOne(EmployeePaymentDetail::class, 'user_id', 'id');
    }

    /**
     * Get attendance records for this user.
     *
     * NOTE: attendance_records.employee_id references employees.id, NOT users.id.
     * There is no direct FK from attendance_records back to users, so this must
     * go through the Employee model: User -> Employee (employees.user_id) ->
     * AttendanceRecord (attendance_records.employee_id).
     */
    public function attendance()
    {
        return $this->hasManyThrough(
            AttendanceRecord::class,
            Employee::class,
            'user_id',      // FK on employees table referencing users.id
            'employee_id',  // FK on attendance_records table referencing employees.id
            'id',           // local key on users
            'id'            // local key on employees
        );
    }

    /**
     * Get leave requests for this user
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id', 'id');
    }

    /**
     * Get agreements for this user
     */
    public function agreements()
    {
        return $this->hasMany(EmployeeAgreement::class, 'user_id', 'id');
    }

    // ── Accessors ──

    /**
     * Get the user's full name
     */
    public function getFullNameAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return $this->name ?? 'Unknown User';
    }

    /**
     * Get the user's display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->full_name;
    }

    /**
     * Get user initials for avatar
     */
    public function getInitialsAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return strtoupper($this->first_name[0] . $this->last_name[0]);
        }
        if ($this->name) {
            $parts = explode(' ', $this->name);
            if (count($parts) >= 2) {
                return strtoupper($parts[0][0] . $parts[1][0]);
            }
            return strtoupper(substr($this->name, 0, 2));
        }
        return 'U';
    }

    /**
     * Get today's attendance record
     */
    public function getTodayAttendanceAttribute()
    {
        return $this->attendance()
            ->whereDate('date', now()->toDateString())
            ->first();
    }

    /**
     * Check if user is checked in today
     */
    public function getIsCheckedInAttribute()
    {
        $today = $this->today_attendance;
        return $today && $today->check_in && !$today->check_out;
    }

    /**
     * Check if user has employee status
     */
    public function getIsEmployeeAttribute()
    {
        return !is_null($this->employee_id);
    }

    /**
     * Get the user's role name
     */
    public function getRoleAttribute()
    {
        $role = $this->getRoleNames()->first();
        return $role ?? 'No Role';
    }

    /**
     * Get all permission names as a flat array of strings.
     *
     * NOTE: This is intentionally named `permission_names` (NOT `permissions`).
     * Spatie's HasRoles/HasPermissions traits already define a real `permissions()`
     * BelongsToMany relationship on this model. Naming this accessor
     * `getPermissionsAttribute()` shadows that relationship, so any internal
     * Spatie call to `$this->permissions` (e.g. inside hasDirectPermission())
     * would resolve to this accessor's array instead of the actual Collection,
     * causing "Call to a member function contains() on array" errors.
     *
     * Access via: $user->permission_names
     */
    public function getPermissionNamesAttribute()
    {
        try {
            return $this->getAllPermissions()->pluck('name')->toArray();
        } catch (\Exception $e) {
            // If there's an error, return the roles as permissions
            return $this->getRoleNames()->toArray();
        }
    }

    /**
     * Get user's full name with fallback (overrides name attribute)
     */
    public function getNameAttribute($value)
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return $value ?? 'Unknown User';
    }

    /**
     * Get the user's email or fallback
     */
    public function getEmailAttribute($value)
    {
        return $value ?? 'no-email@example.com';
    }

    /**
     * Get the user's employee ID or fallback
     */
    public function getEmployeeIdAttribute($value)
    {
        return $value ?? 'Not Assigned';
    }

    // ── Scopes ──

    /**
     * Scope a query to only include active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include employees (users with employee_id)
     */
    public function scopeEmployees($query)
    {
        return $query->whereNotNull('employee_id');
    }

    /**
     * Scope a query to only include users with a specific role
     */
    public function scopeWithRole($query, $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope a query to only include users by department
     */
    public function scopeInDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope a query to search users
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('first_name', 'LIKE', "%{$search}%")
              ->orWhere('last_name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('employee_id', 'LIKE', "%{$search}%");
        });
    }

    // ── Helper Methods ──

    /**
     * Check if user is an employee
     */
    public function isEmployee()
    {
        return !is_null($this->employee_id);
    }

    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Sync user roles and permissions
     */
    public function syncRolesAndPermissions($roleName = null, $permissions = [])
    {
        if ($roleName) {
            $this->syncRoles([$roleName]);
        }

        if (!empty($permissions)) {
            $this->syncPermissions($permissions);
        }

        return $this;
    }

    /**
     * Check if user has any role
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has all roles
     */
    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        $roleCount = $this->roles()->whereIn('name', $roles)->count();
        return $roleCount === count($roles);
    }

    /**
     * Get user's full name
     */
    public function getFullName()
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return $this->name ?? 'Unknown User';
    }

    /**
     * Get user's short name (first name only)
     */
    public function getShortName()
    {
        if ($this->first_name) {
            return $this->first_name;
        }
        $parts = explode(' ', $this->name ?? '');
        return $parts[0] ?? 'User';
    }

    /**
     * Get user's email
     */
    public function getEmail()
    {
        return $this->email ?? 'no-email@example.com';
    }

    /**
     * Check if user has a valid employee ID
     */
    public function hasEmployeeId()
    {
        return !is_null($this->employee_id) && $this->employee_id !== 'Not Assigned';
    }

    /**
     * Get the user's status badge color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'active' => '#10B981',
            'on_leave' => '#F59E0B',
            'terminated' => '#EF4444',
            'suspended' => '#F97316',
            'inactive' => '#6B7280',
        ];
        return $colors[$this->status] ?? '#6B7280';
    }

    /**
     * Get the user's status badge background
     */
    public function getStatusBgAttribute()
    {
        $colors = [
            'active' => '#D1FAE5',
            'on_leave' => '#FEF3C7',
            'terminated' => '#FEE2E2',
            'suspended' => '#FFEDD5',
            'inactive' => '#F3F4F6',
        ];
        return $colors[$this->status] ?? '#F3F4F6';
    }
}