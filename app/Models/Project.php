<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Services\JobGenerationService;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',                    // Job ID field
        'name',
        'engagement_name',           // Separate engagement name
        'description',
        'client_id',
        'engagement_type',
        'engagement_period_start',
        'engagement_period_end',
        'engagement_partner_id',     // Direct reference
        'manager_id',               // Direct reference
        'contact_persons',
        'status',
        'start_date',
        'end_date',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'engagement_period_start' => 'date',
        'engagement_period_end' => 'date',
        'contact_persons' => 'array',
    ];

    // Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_CANCELLED = 'cancelled';

    const ENGAGEMENT_AUDIT = 'audit';
    const ENGAGEMENT_ACCOUNTING = 'accounting';
    const ENGAGEMENT_TAX = 'tax';
    const ENGAGEMENT_SPECIAL = 'special_engagement';
    const ENGAGEMENT_OTHERS = 'others';

    public static function getEngagementTypes()
    {
        return [
            self::ENGAGEMENT_AUDIT => 'Audit',
            self::ENGAGEMENT_ACCOUNTING => 'Accounting',
            self::ENGAGEMENT_TAX => 'Tax',
            self::ENGAGEMENT_SPECIAL => 'Special Engagement',
            self::ENGAGEMENT_OTHERS => 'Others',
        ];
    }

    // Updated Boot method to auto-generate job_id with new format
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->job_id) && !empty($project->engagement_type) && !empty($project->client_id)) {
                $jobService = app(\App\Services\JobGenerationService::class);

                // Get job year from engagement period or current year
                $jobYear = null;
                if ($project->engagement_period_start) {
                    $jobYear = $project->engagement_period_start->year;
                }

                $project->job_id = $jobService->generateUniqueJobId(
                    $project->client_id,
                    $project->engagement_type,
                    $jobYear
                );
            }
        });

        static::updating(function ($project) {
            // Regenerate job ID if key fields changed and job_id is empty
            if (empty($project->job_id) && $project->isDirty(['engagement_type', 'client_id', 'engagement_period_start'])) {
                $jobService = app(\App\Services\JobGenerationService::class);

                $jobYear = null;
                if ($project->engagement_period_start) {
                    $jobYear = $project->engagement_period_start->year;
                }

                $project->job_id = $jobService->generateUniqueJobId(
                    $project->client_id,
                    $project->engagement_type,
                    $jobYear,
                    $project->id
                );
            }
        });
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Direct relationships to engagement partner and manager
    public function engagementPartner()
    {
        return $this->belongsTo(User::class, 'engagement_partner_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Keep existing assignment relationships for backward compatibility
    public function assignments()
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'project_assignments')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class);
    }

    // Project team role relationships (keep for backward compatibility)
    public function engagementPartnerAssignment()
    {
        return $this->assignments()
                    ->where('role', ProjectAssignment::ROLE_ENGAGEMENT_PARTNER)
                    ->with('user')
                    ->first();
    }

    public function managerAssignment()
    {
        return $this->assignments()
                    ->where('role', ProjectAssignment::ROLE_MANAGER)
                    ->with('user')
                    ->first();
    }

    public function associate1()
    {
        return $this->assignments()
                    ->where('role', ProjectAssignment::ROLE_ASSOCIATE_1)
                    ->with('user')
                    ->first();
    }

    public function associate2()
    {
        return $this->assignments()
                    ->where('role', ProjectAssignment::ROLE_ASSOCIATE_2)
                    ->with('user')
                    ->first();
    }

    // Scopes for access control
    public function scopeForUser(Builder $query, User $user)
    {
        if ($user->isSystemAdmin()) {
            return $query; // System admin sees all projects
        }

        if ($user->isClient()) {
            // Clients see only their company's projects
            return $query->where('client_id', $user->client->id ?? 0);
        }

        // MTC staff see only projects they're assigned to
        return $query->whereHas('assignments', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    // Helper methods
    public function isUserAssigned(User $user)
    {
        return $this->assignments()->where('user_id', $user->id)->exists();
    }

    public function getUserProjectRole(User $user)
    {
        $assignment = $this->assignments()->where('user_id', $user->id)->first();
        return $assignment ? $assignment->role : null;
    }

    public function getEngagementTypeDisplayAttribute()
    {
        return self::getEngagementTypes()[$this->engagement_type] ?? ucfirst($this->engagement_type);
    }

    public function getTeamSummary()
    {
        $team = [];
        $assignments = $this->assignments()->with('user')->get();

        foreach ($assignments as $assignment) {
            $team[$assignment->role] = $assignment->user;
        }

        return $team;
    }

    public function canUserAccess(User $user)
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        if ($user->isClient()) {
            return $this->client_id === ($user->client->id ?? 0);
        }

        return $this->isUserAssigned($user);
    }

    // Updated Job ID related methods for new format
    public function getJobIdParts()
    {
        $jobService = app(\App\Services\JobGenerationService::class);
        return $jobService->parseJobId($this->job_id);
    }

    public function getJobDisplayName()
    {
        return "{$this->job_id} - {$this->engagement_name}";
    }

    public function getEngagementYear()
    {
        $parts = $this->getJobIdParts();
        return $parts['full_year_of_job'] ?? null;
    }

    public function getSequenceNumber()
    {
        $parts = $this->getJobIdParts();
        return $parts['series'] ?? null;
    }

    public function getClientInitialFromJobId()
    {
        $parts = $this->getJobIdParts();
        return $parts['client_initial'] ?? null;
    }

    public function getJobTypeFromJobId()
    {
        $parts = $this->getJobIdParts();
        return $parts['job_type_code'] ?? null;
    }

    public function getYearEngagedFromJobId()
    {
        $parts = $this->getJobIdParts();
        return $parts['full_year_engaged'] ?? null;
    }

    // Updated sync methods to maintain both direct and assignment relationships
    public function syncTeamAssignments()
    {
        // Sync direct relationships with assignment table
        if ($this->engagement_partner_id) {
            ProjectAssignment::updateOrCreate(
                ['project_id' => $this->id, 'role' => ProjectAssignment::ROLE_ENGAGEMENT_PARTNER],
                ['user_id' => $this->engagement_partner_id]
            );
        }

        if ($this->manager_id) {
            ProjectAssignment::updateOrCreate(
                ['project_id' => $this->id, 'role' => ProjectAssignment::ROLE_MANAGER],
                ['user_id' => $this->manager_id]
            );
        }
    }

    // Accessor for wireframe compatibility
    public function getJobCodeAttribute()
    {
        return $this->job_id;
    }

    // New accessor for formatted job display
    public function getFormattedJobIdAttribute()
    {
        return $this->job_id;
    }

    // Get job ID breakdown for display
    public function getJobIdBreakdownAttribute()
    {
        $parts = $this->getJobIdParts();

        if (empty($parts)) {
            return null;
        }

        return [
            'client_initial' => $parts['client_initial'],
            'year_engaged' => $parts['full_year_engaged'],
            'series' => $parts['series'],
            'job_type' => $this->getEngagementTypeDisplayAttribute(),
            'job_type_code' => $parts['job_type_code'],
            'year_of_job' => $parts['full_year_of_job'],
            'formatted' => $this->job_id
        ];
    }

    // Method to get suggested job ID
    public function getSuggestedJobId()
    {
        if (!$this->client_id || !$this->engagement_type) {
            return null;
        }

        $jobService = app(\App\Services\JobGenerationService::class);

        $jobYear = null;
        if ($this->engagement_period_start) {
            $jobYear = $this->engagement_period_start->year;
        }

        return $jobService->generateUniqueJobId(
            $this->client_id,
            $this->engagement_type,
            $jobYear,
            $this->id
        );
    }

    // Method to regenerate job ID
    public function regenerateJobId()
    {
        $this->job_id = $this->getSuggestedJobId();
        return $this->save();
    }
}
