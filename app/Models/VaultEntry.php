<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VaultEntry extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'category',
        'client_name',
        'website_url',
        'vps_ip',
        'vps_port',
        'ssh_username',
        'username',
        'password',
        'api_key',
        'notes',
        'extra_fields',
        'created_by',
    ];

    // password and api_key are automatically encrypted/decrypted using APP_KEY
    protected $casts = [
        'password' => 'encrypted',
        'api_key' => 'encrypted',
        'extra_fields' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
              ->orWhere('client_name', 'LIKE', "%{$search}%")
              ->orWhere('website_url', 'LIKE', "%{$search}%");
        });
    }
}