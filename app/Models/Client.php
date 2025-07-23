<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_name', 'contact_person',
        'phone', 'address', 'created_by'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_clients')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class);
    }
}
