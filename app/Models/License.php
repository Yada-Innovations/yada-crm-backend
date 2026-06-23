<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class License extends Model
{
    use HasUuids;

    protected $fillable = ['subscription_id', 'license_key', 'status', 'expires_at'];

    public function subscription() { return $this->belongsTo(Subscription::class); }
}