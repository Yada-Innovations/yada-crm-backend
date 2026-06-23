<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'email', 'phone', 'company',
        'industry', 'status', 'account_manager_id',
    ];

    public function accountManager() {
        return $this->belongsTo(User::class, 'account_manager_id');
    }
    public function leads() { return $this->hasMany(Lead::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function tickets() { return $this->hasMany(Ticket::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
}