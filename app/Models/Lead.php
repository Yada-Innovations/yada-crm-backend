<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Lead extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_name', 'contact_name', 'email', 'phone',
        'stage', 'estimated_value', 'currency',
        'assigned_to', 'client_id', 'notes',
    ];

    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function quotes() { return $this->hasMany(Quote::class); }
    public function demos() { return $this->hasMany(Demo::class); }
    public function tasks() { return $this->hasMany(Task::class); }
    public function deal() { return $this->hasOne(Deal::class); }
}