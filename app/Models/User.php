<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Client;
use App\Models\Project;
use App\Models\DocumentUpload;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Role helper methods
    public function isAdmin()
    {
        return $this->role === 'system_admin';
    }

    public function isClient()
    {
        return $this->role === 'client';
    }

    // Relationships
    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function createdClients()
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(DocumentUpload::class, 'uploaded_by');
    }
    public function getRoleDisplayAttribute()
{
    return $this->role === 'system_admin' ? 'System Admin' : 'Client';
}
}
