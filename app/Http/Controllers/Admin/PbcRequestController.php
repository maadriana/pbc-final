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

    // DEBUG: Log the templates being loaded
    \Log::info('Templates loaded for create form:', [
        'templates' => $templates->map(function($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'items_count' => $t->templateItems->count()
            ];
        })->toArray()
    ]);

    // Also dump to screen for debugging (remove after testing)
    foreach($templates as $template) {
        echo "Template ID: {$template->id}, Name: {$template->name}, Items: {$template->templateItems->count()}<br>";
    }

    return view('admin.pbc-requests.create', compact('templates', 'clients', 'projects'));
}
    public function store(StorePbcRequestRequest $request)
{
    // Debug the incoming request
    \Log::info('PBC Request Store - Items received:', [
        'items_count' => count($request->items ?? []),
        'items' => $request->items
    ]);

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

        // Create request items - FIXED VERSION
        if ($request->items && is_array($request->items)) {
            foreach ($request->items as $index => $item) {
                if (isset($item['particulars']) && !empty(trim($item['particulars']))) {
                    PbcRequestItem::create([
                        'pbc_request_id' => $pbcRequest->id,
                        'category' => $item['category'] ?? null,
                        'particulars' => trim($item['particulars']),
                        'date_requested' => $item['date_requested'] ?? now()->toDateString(),
                        'is_required' => isset($item['is_required']) ? (bool)$item['is_required'] : true,
                        'remarks' => $item['remarks'] ?? null,
                        'order_index' => $index,
                    ]);
                }
            }
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
            'admin_notes' => 'nullable|string|max:500',
            'document_id' => 'required|exists:document_uploads,id'
        ]);

        $document = DocumentUpload::findOrFail($request->document_id);

        // Verify document belongs to this item
        if ($document->pbc_request_item_id !== $item->id) {
            abort(403, 'Document does not belong to this item.');
        }

        $status = $request->action === 'approve' ? 'approved' : 'rejected';

        DB::transaction(function () use ($item, $document, $status, $request) {
            // Update the specific document
            $document->update([
                'status' => $status,
                'admin_notes' => $request->admin_notes,
                'approved_at' => $status === 'approved' ? now() : null,
                'approved_by' => auth()->id(),
            ]);

            // Update item status and track approved document
            if ($status === 'approved') {
                $item->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);
            } else {
                // If rejecting, check if there are other approved documents
                $hasApprovedDocs = $item->documents()->where('status', 'approved')->exists();

                if (!$hasApprovedDocs) {
                    // No approved documents, update item status based on remaining documents
                    $hasUploadedDocs = $item->documents()->where('status', 'uploaded')->exists();

                    $item->update([
                        'status' => $hasUploadedDocs ? 'uploaded' : 'rejected',
                        'reviewed_at' => $hasUploadedDocs ? $item->reviewed_at : now(),
                        'reviewed_by' => auth()->id(),
                    ]);
                }
            }
        });

        // Check if all items are completed
        $this->updateRequestStatus($pbcRequest);

        $action = $status === 'approved' ? 'approved' : 'rejected';
        return redirect()
            ->route('admin.pbc-requests.show', $pbcRequest)
            ->with('success', "Document '{$document->original_filename}' {$action} successfully.");
    }

    // Update overall request status based on items - FIXED VERSION
    private function updateRequestStatus(PbcRequest $pbcRequest)
    {
        $totalItems = $pbcRequest->items()->count();

        if ($totalItems === 0) {
            return;
        }

        // Get all items and check their dynamic status
        $items = $pbcRequest->items()->with('documents')->get();
        $approvedItems = $items->filter(function ($item) {
            return $item->getCurrentStatus() === 'approved';
        })->count();

        // Check if all items are approved
        if ($approvedItems === $totalItems) {
            $pbcRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } else {
            // Check if any items have been worked on
            $hasActivity = $items->filter(function ($item) {
                return in_array($item->getCurrentStatus(), ['uploaded', 'approved', 'rejected']);
            })->count() > 0;

            if ($hasActivity && $pbcRequest->status === 'pending') {
                $pbcRequest->update([
                    'status' => 'in_progress',
                ]);
            }
        }
    }
}
