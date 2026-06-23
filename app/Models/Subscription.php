<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id', 'plan_id', 'seats_used',
        'status', 'starts_at', 'ends_at', 'renewal_alert_sent',
    ];

    protected $casts = ['renewal_alert_sent' => 'boolean'];

    public function client() { return $this->belongsTo(Client::class); }
    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'plan_id'); }
    public function licenses() { return $this->hasMany(License::class); }
}