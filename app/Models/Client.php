<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'company',
        'industry',
        'status',
        'address',
        'city',
        'state',
        'country',
        'account_manager_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    public function accountManager()
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}