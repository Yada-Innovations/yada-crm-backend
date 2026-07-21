<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ticket extends Model
{
    use HasUuids;

    protected $fillable = [
        'ticket_number','subject', 'description', 'client_id',
        'assigned_to', 'created_by', 'status', 'priority',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function comments() { return $this->hasMany(TicketComment::class); }
}