<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role'
    ];

    // Constants for project roles
    const ROLE_ENGAGEMENT_PARTNER = 'engagement_partner';
    const ROLE_MANAGER = 'manager';
    const ROLE_ASSOCIATE_1 = 'associate_1';
    const ROLE_ASSOCIATE_2 = 'associate_2';

    public static function getProjectRoles()
    {
        return [
            self::ROLE_ENGAGEMENT_PARTNER => 'Engagement Partner',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_ASSOCIATE_1 => 'Associate 1',
            self::ROLE_ASSOCIATE_2 => 'Associate 2',
        ];
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRoleDisplayNameAttribute()
    {
        return match($this->role) {
            self::ROLE_ENGAGEMENT_PARTNER => 'Engagement Partner',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_ASSOCIATE_1 => 'Associate 1',
            self::ROLE_ASSOCIATE_2 => 'Associate 2',
            default => ucfirst(str_replace('_', ' ', $this->role))
        };
    }
}
