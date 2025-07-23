<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\DocumentUpload;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $client = auth()->user()->client;

        // Simple metrics for client dashboard
        $metrics = [
            'total_requests' => $client->pbcRequests()->count(),
            'pending_requests' => $client->pbcRequests()->where('status', 'pending')->count(),
            'in_progress_requests' => $client->pbcRequests()->where('status', 'in_progress')->count(),
            'completed_requests' => $client->pbcRequests()->where('status', 'completed')->count(),
            'overdue_requests' => $client->pbcRequests()
                ->where('due_date', '<', now())
                ->whereNotIn('status', ['completed'])
                ->count(),
            'total_documents' => DocumentUpload::whereHas('pbcRequestItem.pbcRequest', function($query) use ($client) {
                $query->where('client_id', $client->id);
            })->count(),
        ];

        // Recent requests (last 5)
        $recent_requests = $client->pbcRequests()
            ->with(['project', 'creator'])
            ->latest()
            ->limit(5)
            ->get();

        return view('client.dashboard', compact('metrics', 'recent_requests'));
    }

    public function progress()
    {
        $client = auth()->user()->client;

        $requests = $client->pbcRequests()
            ->with(['project', 'items'])
            ->latest()
            ->paginate(10);

        return view('client.progress', compact('requests'));
    }
}
