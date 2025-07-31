<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\DocumentUpload;
use App\Models\Project;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $client = auth()->user()->client;

        // Get only assigned projects for this client
        $assignedProjects = Project::where('client_id', $client->id)
            ->with(['assignments.user'])
            ->get();

        // Get PBC requests for assigned projects only
        $assignedProjectIds = $assignedProjects->pluck('id');

        $pbcRequests = PbcRequest::where('client_id', $client->id)
            ->whereIn('project_id', $assignedProjectIds)
            ->with(['items.documents', 'project'])
            ->get();

        // Calculate statistics based on assigned projects only
        $totalRequests = $pbcRequests->count();
        $completedRequests = $pbcRequests->where('status', 'completed')->count();
        $pendingRequests = $pbcRequests->where('status', 'pending')->count();
        $inProgressRequests = $pbcRequests->where('status', 'in_progress')->count();

        // Count overdue requests
        $overdueRequests = $pbcRequests->filter(function ($request) {
            return $request->due_date &&
                   $request->due_date->isPast() &&
                   $request->status !== 'completed';
        })->count();

        // Get recent requests from assigned projects
        $recentRequests = $pbcRequests->sortByDesc('created_at')->take(5);

        // Document statistics for assigned projects only
        $totalDocuments = DocumentUpload::whereHas('pbcRequestItem.pbcRequest', function($q) use ($client, $assignedProjectIds) {
            $q->where('client_id', $client->id)
              ->whereIn('project_id', $assignedProjectIds);
        })->count();

        $approvedDocuments = DocumentUpload::whereHas('pbcRequestItem.pbcRequest', function($q) use ($client, $assignedProjectIds) {
            $q->where('client_id', $client->id)
              ->whereIn('project_id', $assignedProjectIds);
        })->where('status', 'approved')->count();

        $pendingDocuments = DocumentUpload::whereHas('pbcRequestItem.pbcRequest', function($q) use ($client, $assignedProjectIds) {
            $q->where('client_id', $client->id)
              ->whereIn('project_id', $assignedProjectIds);
        })->where('status', 'uploaded')->count();

        // Prepare metrics array to match the view expectations
        $metrics = [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'in_progress_requests' => $inProgressRequests,
            'completed_requests' => $completedRequests,
            'overdue_requests' => $overdueRequests,
            'total_documents' => $totalDocuments,
            'approved_documents' => $approvedDocuments,
            'pending_documents' => $pendingDocuments,
        ];

        // Prepare recent requests for the view
        $recent_requests = $recentRequests;

        return view('client.dashboard', compact(
            'metrics',
            'recent_requests',
            'assignedProjects'
        ));
    }

    public function progress()
    {
        $client = auth()->user()->client;

        // Get assigned projects only
        $assignedProjectIds = Project::where('client_id', $client->id)->pluck('id');

        // Get PBC requests for assigned projects only - with pagination for the table
        $requests = PbcRequest::where('client_id', $client->id)
            ->whereIn('project_id', $assignedProjectIds)
            ->with(['items.documents', 'project'])
            ->latest()
            ->paginate(15);

        // Also get all requests for additional data analysis (without pagination)
        $allRequests = PbcRequest::where('client_id', $client->id)
            ->whereIn('project_id', $assignedProjectIds)
            ->with(['items.documents', 'project'])
            ->get();

        // Progress by request (filtered by assigned projects)
        $progressData = [];
        foreach ($allRequests as $request) {
            $progressData[] = [
                'title' => $request->title,
                'project' => $request->project->name,
                'progress' => $request->getProgressPercentage(),
                'status' => $request->status,
                'due_date' => $request->due_date,
                'total_items' => $request->items->count(),
                'completed_items' => $request->items->filter(function($item) {
                    return $item->getCurrentStatus() === 'approved';
                })->count()
            ];
        }

        // Category-wise progress (from assigned projects only)
        $categoryProgress = [];
        foreach ($allRequests as $request) {
            foreach ($request->items as $item) {
                $category = $item->category ?: 'General';
                if (!isset($categoryProgress[$category])) {
                    $categoryProgress[$category] = ['total' => 0, 'completed' => 0];
                }
                $categoryProgress[$category]['total']++;
                if ($item->getCurrentStatus() === 'approved') {
                    $categoryProgress[$category]['completed']++;
                }
            }
        }

        return view('client.progress', compact('requests', 'progressData', 'categoryProgress'));
    }
}
