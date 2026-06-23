<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeatureRequest extends Model
{
    use HasUuids;

    protected $fillable = ['title', 'description', 'submitted_by', 'status', 'votes'];

    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
}