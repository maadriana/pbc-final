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
        // Get dashboard metrics
        $metrics = [
            'total_users' => User::count(),
            'total_clients' => Client::count(),
            'total_projects' => Project::count(),
            'active_requests' => PbcRequest::whereIn('status', ['pending', 'in_progress'])->count(),
            'pending_documents' => DocumentUpload::where('status', 'uploaded')->count(),
            'completed_requests' => PbcRequest::where('status', 'completed')->count(),
        ];

        // Get recent activities
        $recent_requests = PbcRequest::with(['client', 'project'])
            ->latest()
            ->limit(5)
            ->get();

        $pending_reviews = DocumentUpload::with(['pbcRequestItem.pbcRequest.client'])
            ->where('status', 'uploaded')
            ->latest()
            ->limit(5)
            ->get();

        // Get overdue requests
        $overdue_requests = PbcRequest::with(['client', 'project'])
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
        $requests = PbcRequest::with(['client', 'project', 'items'])
            ->latest()
            ->paginate(15);

        $stats = [
            'total_requests' => PbcRequest::count(),
            'pending' => PbcRequest::where('status', 'pending')->count(),
            'in_progress' => PbcRequest::where('status', 'in_progress')->count(),
            'completed' => PbcRequest::where('status', 'completed')->count(),
            'overdue' => PbcRequest::where('due_date', '<', now())
                ->whereNotIn('status', ['completed'])
                ->count(),
        ];

        return view('admin.progress', compact('requests', 'stats'));
    }
}
