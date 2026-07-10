<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name', // Make sure this is here
        'email',
        'phone',
        'company',
        'industry',
        'status',
        'address',
        'city',
        'country',
        'notes',
        'account_manager_id',
    ];
}