<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\DocumentUpload;
use App\Models\Client;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $client = $this->getOrCreateClient();

        if (!$client) {
            return view('client.dashboard-error');
        }

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
        $client = $this->getOrCreateClient();

        if (!$client) {
            return view('client.dashboard-error');
        }

        $requests = $client->pbcRequests()
            ->with(['project', 'items'])
            ->latest()
            ->paginate(10);

        return view('client.progress', compact('requests'));
    }

    /**
     * Get or create client relationship for the authenticated user
     */
    private function getOrCreateClient()
    {
        $user = auth()->user();

        if ($user->client) {
            return $user->client;
        }

        // Try to find an existing client record
        $existingClient = Client::where('user_id', $user->id)->first();
        if ($existingClient) {
            return $existingClient;
        }

        // Create new client record (for testing purposes)
        try {
            $client = Client::create([
                'user_id' => $user->id,
                'company_name' => $user->name . ' Company',
                'contact_person' => $user->name,
                'phone' => null,
                'address' => null,
                'created_by' => $user->id, // Self-created for now
            ]);

            // Refresh user relationship
            $user->load('client');

            return $client;
        } catch (\Exception $e) {
            \Log::error('Failed to create client for user ' . $user->id . ': ' . $e->getMessage());
            return null;
        }
    }
}
