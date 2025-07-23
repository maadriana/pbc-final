<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentUpload;
use App\Models\PbcRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentUpload::with([
            'pbcRequestItem.pbcRequest.client',
            'pbcRequestItem.pbcRequest.project',
            'uploader',
            'approver'
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhereHas('pbcRequestItem.pbcRequest.client', function($clientQuery) use ($search) {
                      $clientQuery->where('company_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('pbcRequestItem.pbcRequest', function($requestQuery) use ($search) {
                      $requestQuery->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->whereHas('pbcRequestItem.pbcRequest', function($q) use ($request) {
                $q->where('client_id', $request->client_id);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $documents = $query->latest()->paginate(20);
        $clients = Client::orderBy('company_name')->get();

        $stats = [
            'total_documents' => DocumentUpload::count(),
            'pending_review' => DocumentUpload::where('status', 'uploaded')->count(),
            'approved' => DocumentUpload::where('status', 'approved')->count(),
            'rejected' => DocumentUpload::where('status', 'rejected')->count(),
        ];

        return view('admin.documents.index', compact('documents', 'clients', 'stats'));
    }

    public function show(DocumentUpload $document)
    {
        $document->load([
            'pbcRequestItem.pbcRequest.client.user',
            'pbcRequestItem.pbcRequest.project',
            'uploader',
            'approver'
        ]);

        return view('admin.documents.show', compact('document'));
    }

    public function approve(Request $request, DocumentUpload $document)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:500'
        ]);

        $document->update([
            'status' => 'approved',
            'admin_notes' => $request->admin_notes,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Update the related PBC request item
        $document->pbcRequestItem->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'remarks' => $request->admin_notes ?? $document->pbcRequestItem->remarks,
        ]);

        // Check if all items in the request are completed
        $this->updateRequestStatus($document->pbcRequestItem->pbcRequest);

        return redirect()
            ->route('admin.documents.show', $document)
            ->with('success', 'Document approved successfully.');
    }

    public function reject(Request $request, DocumentUpload $document)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:500'
        ]);

        $document->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'approved_at' => null,
            'approved_by' => auth()->id(),
        ]);

        // Update the related PBC request item
        $document->pbcRequestItem->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'remarks' => $request->admin_notes,
        ]);

        return redirect()
            ->route('admin.documents.show', $document)
            ->with('success', 'Document rejected successfully.');
    }

    private function updateRequestStatus($pbcRequest)
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
