<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;

class JobGenerationService
{
    /**
     * Generate job ID based on engagement type and year
     * Format: {engagement_code}-{year_code}-{sequence}
     * Example: 1-01-001 (Audit for 2025, sequence 001)
     */
    public function generateJobId(string $engagementType): string
    {
        $engagementCode = $this->getEngagementCode($engagementType);
        $yearCode = $this->getYearCode();
        $sequence = $this->getNextSequence($engagementType);

        return "{$engagementCode}-{$yearCode}-{$sequence}";
    }

    /**
     * Get engagement type code based on the wireframe pattern
     */
    private function getEngagementCode(string $engagementType): string
    {
        return match($engagementType) {
            'audit' => '1',
            'accounting' => '2',
            'tax' => '3',
            'special_engagement' => '4',
            'others' => '5',
            default => '1' // Default to audit
        };
    }

    /**
     * Get year code (last 2 digits of current year)
     */
    private function getYearCode(): string
    {
        return str_pad(Carbon::now()->year % 100, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get next sequence number for the engagement type and year
     */
    private function getNextSequence(string $engagementType): string
    {
        $engagementCode = $this->getEngagementCode($engagementType);
        $yearCode = $this->getYearCode();
        $prefix = "{$engagementCode}-{$yearCode}-";

        // Find the highest sequence number for this engagement type and year
        $lastProject = Project::where('job_id', 'LIKE', "{$prefix}%")
            ->orderBy('job_id', 'desc')
            ->first();

        if (!$lastProject) {
            return '001'; // First project of this type for the year
        }

        // Extract sequence number and increment
        $lastSequence = substr($lastProject->job_id, -3);
        $nextSequence = intval($lastSequence) + 1;

        return str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Validate job ID format
     */
    public function isValidJobId(string $jobId): bool
    {
        // Pattern: {1-5}-{00-99}-{001-999}
        return preg_match('/^[1-5]-\d{2}-\d{3}$/', $jobId);
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

        return [
            'engagement_code' => $parts[0],
            'year_code' => $parts[1],
            'sequence' => $parts[2],
            'engagement_type' => $this->getEngagementTypeFromCode($parts[0]),
            'year' => 2000 + intval($parts[1])
        ];
    }

    /**
     * Get engagement type from code
     */
    private function getEngagementTypeFromCode(string $code): string
    {
        return match($code) {
            '1' => 'audit',
            '2' => 'accounting',
            '3' => 'tax',
            '4' => 'special_engagement',
            '5' => 'others',
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
}
