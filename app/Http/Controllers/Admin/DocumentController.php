<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentUpload;
use App\Models\PbcRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentUpload::with([
            'pbcRequestItem.pbcRequest.client',
            'pbcRequestItem.pbcRequest.project',
            'uploader',
            'approver',
            'fileDeleter' // ADDED: Include file deleter relationship
        ])
        // FIXED: Only show documents that haven't been deleted
        ->whereNull('file_deleted_at');

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

        // FIXED: Update stats to exclude deleted documents
        $stats = [
            'total_documents' => DocumentUpload::whereNull('file_deleted_at')->count(),
            'pending_review' => DocumentUpload::where('status', 'uploaded')->whereNull('file_deleted_at')->count(),
            'approved' => DocumentUpload::where('status', 'approved')->whereNull('file_deleted_at')->count(),
            'rejected' => DocumentUpload::where('status', 'rejected')->whereNull('file_deleted_at')->count(),
        ];

        return view('admin.documents.index', compact('documents', 'clients', 'stats'));
    }

    public function show(DocumentUpload $document)
    {
        $document->load([
            'pbcRequestItem.pbcRequest.client.user',
            'pbcRequestItem.pbcRequest.project',
            'uploader',
            'approver',
            'fileDeleter' // ADDED: Load file deleter info
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

    /**
     * Delete a document and its associated file
     * FIXED: Only allows deletion of approved or rejected documents
     * FIXED: Maintains the item status even after file deletion
     */
    public function destroy(DocumentUpload $document)
    {
        try {
            // Check if document status allows deletion
            if ($document->status === 'uploaded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete pending documents. Only approved or rejected documents can be deleted.'
                ], 403);
            }

            // Check if document is already deleted
            if ($document->file_deleted_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document has already been deleted.'
                ], 409);
            }

            // Store document info for logging
            $filename = $document->original_filename;
            $filepath = $document->file_path;
            $status = $document->status;
            $documentId = $document->id;
            $pbcRequestItem = $document->pbcRequestItem;

            // Store the current item status before deleting the document
            $currentItemStatus = $pbcRequestItem->status;
            $currentRemarks = $pbcRequestItem->remarks;
            $currentReviewedAt = $pbcRequestItem->reviewed_at;
            $currentReviewedBy = $pbcRequestItem->reviewed_by;

            // Delete the physical file from storage
            if ($filepath && Storage::exists($filepath)) {
                Storage::delete($filepath);
                Log::info("Physical file deleted: {$filepath}");
            } else {
                Log::warning("Physical file not found or already deleted: {$filepath}");
            }

            // FIXED: Mark the document as deleted but keep the database record for history
            $document->update([
                'file_deleted_at' => now(),
                'file_deleted_by' => auth()->id(),
                // Optionally add a note about file deletion
                'admin_notes' => ($document->admin_notes ? $document->admin_notes . ' | ' : '') . 'File deleted on ' . now()->format('Y-m-d H:i:s')
            ]);

            // Only reset to pending if this was the last active document AND it was still pending
            $remainingActiveDocuments = DocumentUpload::where('pbc_request_item_id', $pbcRequestItem->id)
                ->whereNull('file_deleted_at') // Only count non-deleted files
                ->count();

            // Only reset to pending if:
            // 1. This was the last active document
            // 2. AND the item was not yet approved/rejected (still in uploaded status)
            if ($remainingActiveDocuments === 0 && $currentItemStatus === 'uploaded') {
                $pbcRequestItem->update([
                    'status' => 'pending',
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                ]);

                // Update the parent request status
                $this->updateRequestStatus($pbcRequestItem->pbcRequest);
            }
            // If the item was approved/rejected, keep that status even if file is deleted

            Log::info("Document file deleted successfully: {$filename} (ID: {$documentId}, Status: {$status})");

            return response()->json([
                'success' => true,
                'message' => 'Document file deleted successfully. Status maintained.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting document (ID: {$document->id}): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the document.'
            ], 500);
        }
    }

    /**
     * Bulk delete multiple documents
     * FIXED: Maintains item status for approved/rejected items
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:document_uploads,id'
        ]);

        $deletedCount = 0;
        $skippedCount = 0;
        $alreadyDeletedCount = 0;
        $errors = [];

        foreach ($request->document_ids as $documentId) {
            try {
                $document = DocumentUpload::find($documentId);
                if (!$document) continue;

                // Check if document is already deleted
                if ($document->file_deleted_at) {
                    $alreadyDeletedCount++;
                    $errors[] = "Document already deleted: {$document->original_filename} (ID: {$documentId})";
                    continue;
                }

                // Check if document status allows deletion
                if ($document->status === 'uploaded') {
                    $skippedCount++;
                    $errors[] = "Skipped pending document: {$document->original_filename} (ID: {$documentId})";
                    continue;
                }

                // Store current item info
                $pbcRequestItem = $document->pbcRequestItem;
                $currentItemStatus = $pbcRequestItem->status;

                // Delete physical file
                if ($document->file_path && Storage::exists($document->file_path)) {
                    Storage::delete($document->file_path);
                }

                // Mark as deleted instead of removing record
                $document->update([
                    'file_deleted_at' => now(),
                    'file_deleted_by' => auth()->id(),
                    'admin_notes' => ($document->admin_notes ? $document->admin_notes . ' | ' : '') . 'File deleted on ' . now()->format('Y-m-d H:i:s')
                ]);

                // Only reset status if this was the last active document AND it was still pending
                $remainingActiveDocuments = DocumentUpload::where('pbc_request_item_id', $pbcRequestItem->id)
                    ->whereNull('file_deleted_at')
                    ->count();

                if ($remainingActiveDocuments === 0 && $currentItemStatus === 'uploaded') {
                    $pbcRequestItem->update([
                        'status' => 'pending',
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                    ]);

                    $this->updateRequestStatus($pbcRequestItem->pbcRequest);
                }

                $deletedCount++;

            } catch (\Exception $e) {
                $errors[] = "Failed to delete document ID {$documentId}: " . $e->getMessage();
                Log::error("Bulk delete error for document ID {$documentId}: " . $e->getMessage());
            }
        }

        $message = "Deleted {$deletedCount} document files successfully.";
        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} pending documents.";
        }
        if ($alreadyDeletedCount > 0) {
            $message .= " {$alreadyDeletedCount} documents were already deleted.";
        }

        if (count($errors) > 0) {
            return response()->json([
                'success' => $deletedCount > 0,
                'message' => $message,
                'errors' => $errors,
                'deleted' => $deletedCount,
                'skipped' => $skippedCount,
                'already_deleted' => $alreadyDeletedCount
            ], $deletedCount > 0 ? 207 : 400);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'deleted' => $deletedCount,
            'skipped' => $skippedCount,
            'already_deleted' => $alreadyDeletedCount
        ]);
    }

    /**
     * Show deleted documents archive (optional feature)
     */
    public function deletedIndex(Request $request)
    {
        $query = DocumentUpload::with([
            'pbcRequestItem.pbcRequest.client',
            'pbcRequestItem.pbcRequest.project',
            'uploader',
            'approver',
            'fileDeleter'
        ])
        // Only show deleted documents
        ->whereNotNull('file_deleted_at');

        // Search functionality for deleted documents
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhereHas('pbcRequestItem.pbcRequest.client', function($clientQuery) use ($search) {
                      $clientQuery->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $deletedDocuments = $query->latest('file_deleted_at')->paginate(20);
        $clients = Client::orderBy('company_name')->get();

        return view('admin.documents.deleted', compact('deletedDocuments', 'clients'));
    }

    private function updateRequestStatus($pbcRequest)
    {
        $totalItems = $pbcRequest->items()->count();
        $approvedItems = $pbcRequest->items()->where('status', 'approved')->count();
        $pendingItems = $pbcRequest->items()->where('status', 'pending')->count();

        if ($approvedItems === $totalItems) {
            $pbcRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } elseif ($pendingItems > 0) {
            // If there are pending items, mark request as in progress
            $pbcRequest->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);
        }
    }
}
