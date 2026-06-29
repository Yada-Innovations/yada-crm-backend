<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeAgreement extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id', 'type', 'title', 'description', 'signed_date',
        'expiry_date', 'file_path', 'status', 'signed_by', 'notes',
    ];

    protected $casts = [
        'signed_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}