<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\PbcTemplate;
use App\Models\Client;
use App\Models\Project;
use App\Models\DocumentUpload;
use App\Http\Requests\StorePbcRequestRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PbcRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PbcRequest::with(['client', 'project', 'creator']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('company_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('project', function($projectQuery) use ($search) {
                      $projectQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $requests = $query->latest()->paginate(15);
        $clients = Client::orderBy('company_name')->get();

        return view('admin.pbc-requests.index', compact('requests', 'clients'));
    }

    public function create()
    {
        $templates = PbcTemplate::where('is_active', true)->orderBy('name')->get();
        $clients = Client::with('user')->orderBy('company_name')->get();
        $projects = Project::where('status', 'active')->orderBy('name')->get();

        return view('admin.pbc-requests.create', compact('templates', 'clients', 'projects'));
    }

    public function store(StorePbcRequestRequest $request)
    {
        DB::transaction(function () use ($request) {
            // Create PBC request
            $pbcRequest = PbcRequest::create([
                'template_id' => $request->template_id,
                'client_id' => $request->client_id,
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'header_info' => $request->header_info,
                'due_date' => $request->due_date,
                'created_by' => auth()->id(),
            ]);

            // Create request items
            foreach ($request->items as $index => $item) {
                PbcRequestItem::create([
                    'pbc_request_id' => $pbcRequest->id,
                    'category' => $item['category'] ?? null,
                    'particulars' => $item['particulars'],
                    'date_requested' => $item['date_requested'] ?? now()->toDateString(),
                    'is_required' => $item['is_required'] ?? true,
                    'remarks' => $item['remarks'] ?? null,
                    'order_index' => $index,
                ]);
            }
        });

        return redirect()
            ->route('admin.pbc-requests.index')
            ->with('success', 'PBC Request created successfully.');
    }

    public function show(PbcRequest $pbcRequest)
    {
        $pbcRequest->load([
            'client.user',
            'project',
            'creator',
            'template',
            'items' => function($query) {
                $query->orderBy('order_index');
            },
            'items.documents.uploader'
        ]);

        return view('admin.pbc-requests.show', compact('pbcRequest'));
    }

    public function edit(PbcRequest $pbcRequest)
    {
        // Only allow editing if not sent yet
        if ($pbcRequest->sent_at) {
            return redirect()
                ->route('admin.pbc-requests.show', $pbcRequest)
                ->with('error', 'Cannot edit request that has been sent to client.');
        }

        $pbcRequest->load(['items' => function($query) {
            $query->orderBy('order_index');
        }]);

        $templates = PbcTemplate::where('is_active', true)->orderBy('name')->get();
        $clients = Client::with('user')->orderBy('company_name')->get();
        $projects = Project::where('status', 'active')->orderBy('name')->get();

        return view('admin.pbc-requests.edit', compact('pbcRequest', 'templates', 'clients', 'projects'));
    }

    public function update(StorePbcRequestRequest $request, PbcRequest $pbcRequest)
    {
        // Only allow updating if not sent yet
        if ($pbcRequest->sent_at) {
            return redirect()
                ->route('admin.pbc-requests.show', $pbcRequest)
                ->with('error', 'Cannot update request that has been sent to client.');
        }

        DB::transaction(function () use ($request, $pbcRequest) {
            // Update PBC request
            $pbcRequest->update([
                'template_id' => $request->template_id,
                'client_id' => $request->client_id,
                'project_id' => $request->project_id,
                'title' => $request->title,
                'description' => $request->description,
                'header_info' => $request->header_info,
                'due_date' => $request->due_date,
            ]);

            // Delete existing items and recreate
            $pbcRequest->items()->delete();

            // Create new request items
            foreach ($request->items as $index => $item) {
                PbcRequestItem::create([
                    'pbc_request_id' => $pbcRequest->id,
                    'category' => $item['category'] ?? null,
                    'particulars' => $item['particulars'],
                    'date_requested' => $item['date_requested'] ?? now()->toDateString(),
                    'is_required' => $item['is_required'] ?? true,
                    'remarks' => $item['remarks'] ?? null,
                    'order_index' => $index,
                ]);
            }
        });

        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', 'PBC Request updated successfully.');
    }

    public function destroy(PbcRequest $pbcRequest)
    {
        // Check if request has uploaded documents
        $hasDocuments = $pbcRequest->items()
            ->whereHas('documents')
            ->exists();

        if ($hasDocuments) {
            return redirect()
                ->route('admin.pbc-requests.index')
                ->with('error', 'Cannot delete request with uploaded documents.');
        }

        $pbcRequest->delete();

        return redirect()
            ->route('admin.pbc-requests.index')
            ->with('success', 'PBC Request deleted successfully.');
    }

    // Send request to client
    public function send(PbcRequest $pbcRequest)
    {
        if ($pbcRequest->sent_at) {
            return redirect()
                ->route('admin.pbc-requests.show', $pbcRequest)
                ->with('error', 'Request has already been sent to client.');
        }

        $pbcRequest->update([
            'sent_at' => now(),
            'status' => 'in_progress',
        ]);

        // Here you could send an email notification to the client
        // Mail::to($pbcRequest->client->user->email)->send(new PbcRequestSent($pbcRequest));

        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', 'PBC Request sent to client successfully.');
    }

    // Review uploaded item
    public function reviewItem(Request $request, PbcRequest $pbcRequest, PbcRequestItem $item)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        $status = $request->action === 'approve' ? 'approved' : 'rejected';

        DB::transaction(function () use ($item, $status, $request) {
            // Update item status
            $item->update([
                'status' => $status,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'remarks' => $request->admin_notes ?? $item->remarks,
            ]);

            // Update document status
            $item->documents()->update([
                'status' => $status,
                'admin_notes' => $request->admin_notes,
                'approved_at' => $status === 'approved' ? now() : null,
                'approved_by' => auth()->id(),
            ]);
        });

        // Check if all items are completed
        $this->updateRequestStatus($pbcRequest);

        $action = $status === 'approved' ? 'approved' : 'rejected';
        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', "Document {$action} successfully.");
    }

    // Update overall request status based on items
    private function updateRequestStatus(PbcRequest $pbcRequest)
    {
        $totalItems = $pbcRequest->items()->count();
        $approvedItems = $pbcRequest->items()->where('status', 'approved')->count();

        if ($approvedItems === $totalItems) {
            $pbcRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
