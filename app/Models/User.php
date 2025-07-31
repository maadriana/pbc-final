<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Role constants
    const ROLE_SYSTEM_ADMIN = 'system_admin';
    const ROLE_ENGAGEMENT_PARTNER = 'engagement_partner';
    const ROLE_MANAGER = 'manager';
    const ROLE_ASSOCIATE = 'associate';
    const ROLE_CLIENT = 'client';

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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

    public function createdPbcRequests()
    {
        return $this->hasMany(PbcRequest::class, 'created_by');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(DocumentUpload::class, 'uploaded_by');
    }

    public function approvedDocuments()
    {
        return $this->hasMany(DocumentUpload::class, 'approved_by');
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    // Role checking methods
    public function isSystemAdmin()
    {
        return $this->role === self::ROLE_SYSTEM_ADMIN;
    }

    public function isEngagementPartner()
    {
        return $this->role === self::ROLE_ENGAGEMENT_PARTNER;
    }

    public function isManager()
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isAssociate()
    {
        return $this->role === self::ROLE_ASSOCIATE;
    }

    public function isClient()
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isAdmin()
    {
        return in_array($this->role, [
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_ENGAGEMENT_PARTNER,
            self::ROLE_MANAGER,
            self::ROLE_ASSOCIATE
        ]);
    }

    // Permission methods based on the document requirements
    public function canCreateUsers()
    {
        return $this->role === self::ROLE_SYSTEM_ADMIN;
    }

    public function canManageClients()
    {
        return in_array($this->role, [
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_ENGAGEMENT_PARTNER,
            self::ROLE_MANAGER
        ]);
    }

    public function canCreatePbcRequests()
    {
        return in_array($this->role, [
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_ENGAGEMENT_PARTNER,
            self::ROLE_MANAGER,
            self::ROLE_ASSOCIATE
        ]);
    }

    public function canReviewDocuments()
    {
        return in_array($this->role, [
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_ENGAGEMENT_PARTNER,
            self::ROLE_MANAGER,
            self::ROLE_ASSOCIATE
        ]);
    }

    public function canDeleteApprovedDocuments()
    {
        return in_array($this->role, [
            self::ROLE_SYSTEM_ADMIN,
            self::ROLE_ENGAGEMENT_PARTNER,
            self::ROLE_MANAGER,
            self::ROLE_ASSOCIATE
        ]);
    }

    public function canUploadDocuments()
    {
        return true; // All roles can upload documents
    }

    public function canCreateProjects()
{
    return in_array($this->role, [
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_ENGAGEMENT_PARTNER,
        self::ROLE_MANAGER
    ]);
}

    public function getRoleDisplayName()
    {
        return match($this->role) {
            self::ROLE_SYSTEM_ADMIN => 'System Admin',
            self::ROLE_ENGAGEMENT_PARTNER => 'Engagement Partner',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_ASSOCIATE => 'Associate',
            self::ROLE_CLIENT => 'Client',
            default => 'Unknown'
        };
    }

    public function projectAssignments()
{
    return $this->hasMany(ProjectAssignment::class);
}

public function assignedProjects()
{
    return $this->belongsToMany(Project::class, 'project_assignments')
                ->withPivot('role')
                ->withTimestamps();
}

public function getAccessibleProjects()
{
    if ($this->isSystemAdmin()) {
        return Project::all();
    }

    if ($this->isClient()) {
        return Project::where('client_id', $this->client->id ?? 0)->get();
    }

    return $this->assignedProjects;
}

public function getAccessibleClients()
{
    if ($this->isSystemAdmin()) {
        return Client::all();
    }

    if ($this->isClient()) {
        return collect([$this->client])->filter();
    }

    // MTC staff see only clients from their assigned projects
    $projectIds = $this->assignedProjects->pluck('id');
    $clientIds = Project::whereIn('id', $projectIds)
                       ->whereNotNull('client_id')
                       ->pluck('client_id')
                       ->unique();

    return Client::whereIn('id', $clientIds)->get();
}

public function canAccessProject(Project $project)
{
    return $project->canUserAccess($this);
}

public function canAccessClient(Client $client)
{
    if ($this->isSystemAdmin()) {
        return true;
    }

    if ($this->isClient()) {
        return $this->client_id === $client->id;
    }

    // MTC staff can access client if they're assigned to any of the client's projects
    return $this->assignedProjects()
                ->where('client_id', $client->id)
                ->exists();
}
}
