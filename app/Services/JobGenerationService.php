<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Client;
use Carbon\Carbon;

class JobGenerationService
{
    /**
     * Generate job ID based on new format:
     * {CLIENT_INITIAL}-{YEAR_ENGAGED}-{SERIES}-{JOB_TYPE}-{YEAR_OF_JOB}
     * Example: ABC-22-001-A-24 (ABC client engaged in 2022, series 001, Audit, for 2024)
     */
    public function generateJobId(string $engagementType, int $clientId, ?int $jobYear = null): string
    {
        $client = Client::findOrFail($clientId);

        $clientInitial = $this->getClientInitial($client);
        $yearEngaged = $this->getClientEngagementYear($client);
        $series = $this->getNextSeries($clientId, $engagementType, $jobYear);
        $jobTypeCode = $this->getJobTypeCode($engagementType);
        $yearOfJob = $this->getJobYear($jobYear);

        return "{$clientInitial}-{$yearEngaged}-{$series}-{$jobTypeCode}-{$yearOfJob}";
    }

    /**
     * Get client 3-letter initial from company name
     */
    private function getClientInitial(Client $client): string
    {
        // Use the client model's method for consistency
        return $client->getClientInitial();
    }

    /**
     * Get the year client was first engaged with MTC
     */
    private function getClientEngagementYear(Client $client): string
    {
        // Use the year_engaged field if available
        if ($client->year_engaged) {
            $engagementYear = $client->year_engaged;
        } else {
            // Fallback: try to get from the earliest project
            $firstProject = Project::where('client_id', $client->id)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstProject) {
                $engagementYear = $firstProject->created_at->year;
            } else {
                // Fallback to client creation year
                $engagementYear = $client->created_at ? $client->created_at->year : Carbon::now()->year;
            }
        }

        return str_pad($engagementYear % 100, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get next series number for this client, engagement type, and job year
     */
    private function getNextSeries(int $clientId, string $engagementType, ?int $jobYear = null): string
    {
        $client = Client::findOrFail($clientId);
        $clientInitial = $this->getClientInitial($client);
        $yearEngaged = $this->getClientEngagementYear($client);
        $jobTypeCode = $this->getJobTypeCode($engagementType);
        $yearOfJob = $this->getJobYear($jobYear);

        // Find existing projects with same pattern (excluding series)
        $pattern = "{$clientInitial}-{$yearEngaged}-%{$jobTypeCode}-{$yearOfJob}";

        $lastProject = Project::where('job_id', 'LIKE', str_replace('%', '', $pattern) . '%')
            ->where('job_id', 'REGEXP', "^{$clientInitial}-{$yearEngaged}-[0-9]{3}-{$jobTypeCode}-{$yearOfJob}$")
            ->orderBy('job_id', 'desc')
            ->first();

        if (!$lastProject) {
            return '001'; // First project of this type for this client and year
        }

        // Extract series number and increment
        $jobParts = explode('-', $lastProject->job_id);
        if (count($jobParts) >= 3) {
            $lastSeries = intval($jobParts[2]);
            $nextSeries = $lastSeries + 1;
        } else {
            $nextSeries = 1;
        }

        return str_pad($nextSeries, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get job type code based on engagement type
     */
    private function getJobTypeCode(string $engagementType): string
    {
        return match($engagementType) {
            'audit' => 'A',
            'accounting' => 'AC',
            'tax' => 'T',
            'special_engagement' => 'S',
            'others' => 'O',
            default => 'A' // Default to audit
        };
    }

    /**
     * Get job year (year of the engagement/audit)
     */
    private function getJobYear(?int $jobYear = null): string
    {
        $year = $jobYear ?? Carbon::now()->year;
        return str_pad($year % 100, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Validate job ID format
     */
    public function isValidJobId(string $jobId): bool
    {
        // Pattern: ABC-22-001-A-24
        return preg_match('/^[A-Z]{3}-\d{2}-\d{3}-(A|AC|T|S|O)-\d{2}$/', $jobId);
    }

    /**
     * Parse job ID to get components
     */
    public function parseJobId(string $jobId): array
    {
        if (!$this->isValidJobId($jobId)) {
            return [];
        }

        $parts = explode('-', $jobId);

        if (count($parts) !== 5) {
            return [];
        }

        return [
            'client_initial' => $parts[0],
            'year_engaged' => $parts[1],
            'series' => $parts[2],
            'job_type_code' => $parts[3],
            'year_of_job' => $parts[4],
            'engagement_type' => $this->getEngagementTypeFromCode($parts[3]),
            'full_year_engaged' => 2000 + intval($parts[1]),
            'full_year_of_job' => 2000 + intval($parts[4])
        ];
    }

    /**
     * Get engagement type from job type code
     */
    private function getEngagementTypeFromCode(string $code): string
    {
        return match($code) {
            'A' => 'audit',
            'AC' => 'accounting',
            'T' => 'tax',
            'S' => 'special_engagement',
            'O' => 'others',
            default => 'audit'
        };
    }

    /**
     * Get display name for engagement type
     */
    public function getEngagementTypeDisplay(string $engagementType): string
    {
        return match($engagementType) {
            'audit' => 'Audit',
            'accounting' => 'Accounting',
            'tax' => 'Tax',
            'special_engagement' => 'Special Engagement',
            'others' => 'Others',
            default => 'Audit'
        };
    }

    /**
     * Generate job ID for existing client and engagement details
     */
    public function generateJobIdForProject(int $clientId, string $engagementType, ?Carbon $engagementPeriodStart = null): string
    {
        $jobYear = $engagementPeriodStart ? $engagementPeriodStart->year : Carbon::now()->year;
        return $this->generateJobId($engagementType, $clientId, $jobYear);
    }

    /**
     * Get suggested job ID based on client and engagement details
     */
    public function getSuggestedJobId(int $clientId, string $engagementType, ?string $engagementPeriodStart = null): string
    {
        $jobYear = null;

        if ($engagementPeriodStart) {
            try {
                $jobYear = Carbon::parse($engagementPeriodStart)->year;
            } catch (\Exception $e) {
                $jobYear = Carbon::now()->year;
            }
        }

        return $this->generateJobId($engagementType, $clientId, $jobYear);
    }

    /**
     * Check if job ID is unique
     */
    public function isJobIdUnique(string $jobId, ?int $excludeProjectId = null): bool
    {
        $query = Project::where('job_id', $jobId);

        if ($excludeProjectId) {
            $query->where('id', '!=', $excludeProjectId);
        }

        return !$query->exists();
    }

    /**
     * Generate unique job ID (with fallback if conflicts occur)
     */
    public function generateUniqueJobId(int $clientId, string $engagementType, ?int $jobYear = null, ?int $excludeProjectId = null): string
    {
        $baseJobId = $this->generateJobId($engagementType, $clientId, $jobYear);

        if ($this->isJobIdUnique($baseJobId, $excludeProjectId)) {
            return $baseJobId;
        }

        // If conflict, increment series until unique
        $client = Client::findOrFail($clientId);
        $clientInitial = $this->getClientInitial($client);
        $yearEngaged = $this->getClientEngagementYear($client);
        $jobTypeCode = $this->getJobTypeCode($engagementType);
        $yearOfJob = $this->getJobYear($jobYear);

        for ($series = 1; $series <= 999; $series++) {
            $seriesStr = str_pad($series, 3, '0', STR_PAD_LEFT);
            $jobId = "{$clientInitial}-{$yearEngaged}-{$seriesStr}-{$jobTypeCode}-{$yearOfJob}";

            if ($this->isJobIdUnique($jobId, $excludeProjectId)) {
                return $jobId;
            }
        }

        // If still no unique ID found, append timestamp
        return $baseJobId . '-' . time();
    }
}
