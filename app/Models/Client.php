<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'year_engaged',
        'contact_person',
        'phone',
        'address',
        'created_by'
    ];

    protected $casts = [
        'year_engaged' => 'integer',
    ];

    // Boot method to auto-populate year_engaged
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($client) {
            if (empty($client->year_engaged)) {
                $client->year_engaged = Carbon::now()->year;
            }
        });
    }

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
        return $this->hasMany(Project::class);
    }

    public function pbcRequests()
    {
        return $this->hasMany(PbcRequest::class);
    }

    // NEW: Job ID generation support methods
    /**
     * Get client 3-letter initial from company name
     */
    public function getClientInitial(): string
    {
        $companyName = strtoupper(trim($this->company_name));

        // Remove common business suffixes and words
        $cleanName = preg_replace('/\b(INC|CORP|CORPORATION|LLC|LTD|LIMITED|CO|COMPANY|ENTERPRISES|INTERNATIONAL|GROUP|HOLDINGS)\b/i', '', $companyName);
        $cleanName = preg_replace('/[^A-Z0-9\s]/', '', $cleanName);
        $cleanName = trim($cleanName);

        // Split into words and get initials
        $words = array_filter(explode(' ', $cleanName));

        if (empty($words)) {
            // Fallback: use first 3 characters of original name
            return substr(preg_replace('/[^A-Z0-9]/', '', $companyName), 0, 3) ?: 'ABC';
        }

        if (count($words) >= 3) {
            // Use first letter of first 3 words
            return substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
        } elseif (count($words) == 2) {
            // Use first letter of first word, first 2 letters of second word
            return substr($words[0], 0, 1) . substr($words[1], 0, 2);
        } else {
            // Single word: use first 3 letters
            return substr($words[0], 0, 3);
        }
    }

    /**
     * Get the year client was first engaged (for job ID generation)
     */
    public function getEngagementYear(): int
    {
        if ($this->year_engaged) {
            return $this->year_engaged;
        }

        // Fallback: get from earliest project
        $firstProject = $this->projects()->orderBy('created_at', 'asc')->first();
        if ($firstProject) {
            return $firstProject->created_at->year;
        }

        // Final fallback: client creation year
        return $this->created_at ? $this->created_at->year : Carbon::now()->year;
    }

    /**
     * Get short year format for job ID (last 2 digits)
     */
    public function getEngagementYearShort(): string
    {
        return str_pad($this->getEngagementYear() % 100, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Update year engaged based on earliest project
     */
    public function updateYearEngagedFromProjects(): bool
    {
        $firstProject = $this->projects()->orderBy('created_at', 'asc')->first();

        if ($firstProject) {
            $this->year_engaged = $firstProject->created_at->year;
            return $this->save();
        }

        return false;
    }

    // Helper methods for wireframe compatibility
    public function getOngoingProjectsCount()
    {
        return $this->projects()->whereIn('status', ['active', 'on_hold'])->count();
    }

    public function getCompletedProjectsCount()
    {
        return $this->projects()->where('status', 'completed')->count();
    }

    public function getActivePbcRequestsCount()
    {
        return $this->pbcRequests()->whereIn('status', ['pending', 'in_progress'])->count();
    }

    public function getPendingPbcRequestsCount()
    {
        return $this->pbcRequests()->where('status', 'pending')->count();
    }

    public function getSubmittedPbcRequestsCount()
    {
        return $this->pbcRequests()->where('status', 'in_progress')->count();
    }

    // NEW: Get all projects count
    public function getTotalProjectsCount()
    {
        return $this->projects()->count();
    }

    // NEW: Get projects by engagement type
    public function getProjectsByEngagementType(string $engagementType)
    {
        return $this->projects()->where('engagement_type', $engagementType);
    }

    public function getAuditProjectsCount()
    {
        return $this->getProjectsByEngagementType('audit')->count();
    }

    public function getAccountingProjectsCount()
    {
        return $this->getProjectsByEngagementType('accounting')->count();
    }

    public function getTaxProjectsCount()
    {
        return $this->getProjectsByEngagementType('tax')->count();
    }

    // Display helpers
    public function getFormattedAddressAttribute()
    {
        return $this->address ?: 'Address not provided';
    }

    public function getContactEmailAttribute()
    {
        return $this->user->email ?? '';
    }

    public function getContactPhoneAttribute()
    {
        return $this->phone ?: 'Phone not provided';
    }

    // NEW: Company information accessors for consistency
    public function getCompanyDisplayNameAttribute()
    {
        return $this->company_name;
    }

    public function getClientCodeAttribute()
    {
        return $this->getClientInitial();
    }

    // NEW: Engagement summary for display
    public function getEngagementSummaryAttribute()
    {
        return [
            'year_engaged' => $this->getEngagementYear(),
            'client_initial' => $this->getClientInitial(),
            'total_projects' => $this->getTotalProjectsCount(),
            'ongoing_projects' => $this->getOngoingProjectsCount(),
            'completed_projects' => $this->getCompletedProjectsCount(),
        ];
    }

    // NEW: Get recent projects (for display)
    public function getRecentProjects(int $limit = 5)
    {
        return $this->projects()
            ->with(['engagementPartner', 'manager'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // NEW: Get active projects with team information
    public function getActiveProjectsWithTeam()
    {
        return $this->projects()
            ->with(['engagementPartner', 'manager', 'assignments.user'])
            ->whereIn('status', ['active', 'on_hold'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    // NEW: Check if client has projects of specific type
    public function hasProjectsOfType(string $engagementType): bool
    {
        return $this->projects()->where('engagement_type', $engagementType)->exists();
    }

    // NEW: Get next suggested job ID for new project
    public function getNextSuggestedJobId(string $engagementType, ?int $jobYear = null): string
    {
        $jobGenerationService = app(\App\Services\JobGenerationService::class);
        return $jobGenerationService->generateUniqueJobId($this->id, $engagementType, $jobYear);
    }

    // NEW: Scope for clients with active projects
    public function scopeWithActiveProjects($query)
    {
        return $query->whereHas('projects', function ($q) {
            $q->whereIn('status', ['active', 'on_hold']);
        });
    }

    // NEW: Scope for clients engaged in specific year
    public function scopeEngagedInYear($query, int $year)
    {
        return $query->where('year_engaged', $year);
    }

    // NEW: Method to validate and update client initial if needed
    public function validateAndUpdateClientInitial(): bool
    {
        $currentInitial = $this->getClientInitial();

        // Check if any projects use this client's job IDs
        $projectsWithJobIds = $this->projects()->whereNotNull('job_id')->get();

        if ($projectsWithJobIds->isEmpty()) {
            // No existing job IDs, safe to update
            return true;
        }

        // Check if current initial matches existing job IDs
        foreach ($projectsWithJobIds as $project) {
            $jobService = app(\App\Services\JobGenerationService::class);
            $jobParts = $jobService->parseJobId($project->job_id);

            if (!empty($jobParts) && $jobParts['client_initial'] !== $currentInitial) {
                // Mismatch found - might need manual intervention
                \Log::warning("Client initial mismatch for client {$this->id}: expected {$currentInitial}, found {$jobParts['client_initial']} in job {$project->job_id}");
                return false;
            }
        }

        return true;
    }

    // Legacy support - keeping these for backward compatibility
    public function getSecRegistrationAttribute()
    {
        // This would come from additional client fields if needed
        return 'ABC-000-0000'; // Placeholder
    }

    public function getTaxIdAttribute()
    {
        // This would come from additional client fields if needed
        return '000-000-001'; // Placeholder
    }
}
