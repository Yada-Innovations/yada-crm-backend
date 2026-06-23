<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'price', 'currency', 'max_seats', 'billing_cycle', 'features', 'active',
    ];

    protected $casts = ['features' => 'array', 'active' => 'boolean'];

    public function subscriptions() { return $this->hasMany(Subscription::class, 'plan_id'); }
}