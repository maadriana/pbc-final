<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\PbcRequest;
use App\Models\DocumentUpload;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get accessible project IDs for filtering
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);

        // UPDATED: Calculate metrics to match wireframe exactly
        $metrics = $this->calculateWireframeMetrics($user, $accessibleProjectIds);

        // Get recent activities filtered by accessible projects
        $recent_requests = PbcRequest::with(['client', 'project'])
            ->whereIn('project_id', $accessibleProjectIds)
            ->latest()
            ->limit(5)
            ->get();

        $pending_reviews = DocumentUpload::with(['pbcRequestItem.pbcRequest.client', 'pbcRequestItem.pbcRequest.project'])
            ->whereHas('pbcRequestItem.pbcRequest', function($q) use ($accessibleProjectIds) {
                $q->whereIn('project_id', $accessibleProjectIds);
            })
            ->where('status', 'uploaded')
            ->latest()
            ->limit(5)
            ->get();

        // Get overdue requests from accessible projects
        $overdue_requests = PbcRequest::with(['client', 'project'])
            ->whereIn('project_id', $accessibleProjectIds)
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['completed'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'metrics',
            'recent_requests',
            'pending_reviews',
            'overdue_requests'
        ));
    }

    public function progress()
    {
        $user = auth()->user();
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);

        // Filter requests by accessible projects
        $requests = PbcRequest::with(['client', 'project', 'items'])
            ->whereIn('project_id', $accessibleProjectIds)
            ->latest()
            ->paginate(15);

        // FIXED: Calculate progress stats matching wireframe - fixed the key names
        $stats = [
            'total_requests' => PbcRequest::whereIn('project_id', $accessibleProjectIds)->count(), // FIXED: added 's'
            'pending' => PbcRequest::whereIn('project_id', $accessibleProjectIds)
                ->where('status', 'pending')->count(),
            'pending_review' => PbcRequest::whereIn('project_id', $accessibleProjectIds)
                ->where('status', 'in_progress')->count(),
            'overdue' => PbcRequest::whereIn('project_id', $accessibleProjectIds)
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['completed'])
                ->count(),
            'completed' => PbcRequest::whereIn('project_id', $accessibleProjectIds)
                ->where('status', 'completed')->count(),
        ];

        return view('admin.progress', compact('requests', 'stats'));
    }

    /**
     * NEW: Calculate metrics to match wireframe exactly
     */
    private function calculateWireframeMetrics(User $user, $accessibleProjectIds)
    {
        if ($user->isSystemAdmin()) {
            // System admin sees all metrics
            return [
                'total_users' => User::count(),
                'total_clients' => Client::count(),
                'total_projects' => Project::count(),
                'active_requests' => PbcRequest::whereIn('status', ['pending', 'in_progress'])->count(),
                'pending_documents' => DocumentUpload::where('status', 'uploaded')->count(),
                'completed_requests' => PbcRequest::where('status', 'completed')->count(),
            ];
        }

        // For other admin roles, filter by accessible projects
        return [
            'total_users' => $this->getTeamMembersCount($user),
            'total_clients' => $this->getAccessibleClientsCount($user),
            'total_projects' => $accessibleProjectIds->count(),
            'active_requests' => PbcRequest::whereIn('project_id', $accessibleProjectIds)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
            'pending_documents' => DocumentUpload::whereHas('pbcRequestItem.pbcRequest', function($q) use ($accessibleProjectIds) {
                $q->whereIn('project_id', $accessibleProjectIds);
            })->where('status', 'uploaded')->count(),
            'completed_requests' => PbcRequest::whereIn('project_id', $accessibleProjectIds)
                ->where('status', 'completed')
                ->count(),
        ];
    }

    /**
     * Get accessible project IDs for the given user
     */
    private function getAccessibleProjectIds(User $user)
    {
        if ($user->isSystemAdmin()) {
            return Project::pluck('id');
        }

        // Use the existing scope from Project model
        return Project::forUser($user)->pluck('id');
    }

    /**
     * Get count of accessible clients for the user
     */
    private function getAccessibleClientsCount(User $user)
    {
        if ($user->isSystemAdmin()) {
            return Client::count();
        }

        $accessibleProjectIds = $this->getAccessibleProjectIds($user);
        return Client::whereHas('projects', function($q) use ($accessibleProjectIds) {
            $q->whereIn('projects.id', $accessibleProjectIds);
        })->distinct()->count();
    }

    /**
     * Get count of team members for non-admin users
     */
    private function getTeamMembersCount(User $user)
    {
        if ($user->isSystemAdmin()) {
            return User::count();
        }

        // Get unique users from projects the current user is assigned to
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);
        return User::whereHas('assignedProjects', function($q) use ($accessibleProjectIds) {
            $q->whereIn('project_id', $accessibleProjectIds);
        })->distinct()->count();
    }

    /**
     * NEW: Get dashboard data for wireframe format
     */
    public function getDashboardData()
    {
        $user = auth()->user();
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);

        // Get recent uploaded requests for MTC Staff Dashboard
        $recentUploadedRequests = PbcRequest::with(['client', 'items'])
            ->whereIn('project_id', $accessibleProjectIds)
            ->whereHas('items.documents')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'client_name' => $request->client->company_name,
                    'engagement_type' => $request->project->engagement_type ?? 'audit',
                    'engagement_name' => $request->project->engagement_name ?? $request->title,
                    'request_description' => $request->title,
                    'uploaded_by' => $request->items->flatMap->documents->first()->uploader->name ?? '',
                    'status' => $request->status,
                ];
            });

        // Get recent PBC requests for Client Dashboard
        $recentPbcRequests = PbcRequest::with(['client'])
            ->whereIn('project_id', $accessibleProjectIds)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'request_description' => $request->title,
                    'requested_by' => $request->creator->name ?? '',
                    'date_requested' => $request->created_at->format('d/m/Y'),
                    'due_date' => $request->due_date?->format('d/m/Y'),
                    'status' => $request->status,
                ];
            });

        return [
            'recent_uploaded_requests' => $recentUploadedRequests,
            'recent_pbc_requests' => $recentPbcRequests,
        ];
    }
}
