<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'start_date', 'end_date',
        'status', 'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'project_clients')
                    ->withPivot('assigned_by', 'assigned_at')
                    ->withTimestamps();
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class);
    }
}
