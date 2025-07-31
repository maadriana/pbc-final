<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Models\DocumentUpload;
use App\Models\Project;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PbcRequestController extends Controller
{
    public function index()
    {
        $client = auth()->user()->client;

        // Get only assigned projects for this client
        $assignedProjectIds = Project::where('client_id', $client->id)->pluck('id');

        // Client can only see PBC requests for their assigned projects
        $requests = PbcRequest::with(['project', 'items'])
            ->where('client_id', $client->id)
            ->whereIn('project_id', $assignedProjectIds)
            ->latest()
            ->paginate(15);

        return view('client.pbc-requests.index', compact('requests'));
    }

    public function show(PbcRequest $pbcRequest)
    {
        $client = auth()->user()->client;

        // Security check - ensure client owns this request
        if ($pbcRequest->client_id !== $client->id) {
            abort(403, 'Unauthorized access to this PBC request.');
        }

        // Additional check - ensure request is for an assigned project
        $assignedProjectIds = Project::where('client_id', $client->id)->pluck('id');
        if (!$assignedProjectIds->contains($pbcRequest->project_id)) {
            abort(403, 'This request is not for an assigned project.');
        }

        $pbcRequest->load([
            'project',
            'items' => function($query) {
                $query->orderBy('order_index');
            },
            'items.documents' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        return view('client.pbc-requests.show', compact('pbcRequest'));
    }

    public function upload(Request $request, PbcRequest $pbcRequest, PbcRequestItem $item)
    {
        $client = auth()->user()->client;

        // Security checks
        if ($pbcRequest->client_id !== $client->id) {
            abort(403, 'Unauthorized access to this PBC request.');
        }

        if ($item->pbc_request_id !== $pbcRequest->id) {
            abort(403, 'Invalid request item.');
        }

        // Additional check - ensure request is for an assigned project
        $assignedProjectIds = Project::where('client_id', $client->id)->pluck('id');
        if (!$assignedProjectIds->contains($pbcRequest->project_id)) {
            abort(403, 'This request is not for an assigned project.');
        }

        // Validate file upload
        $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Generate unique filename using helper
        $storedName = FileHelper::generateUniqueFilename($originalName);

        // Create directory path
        $directory = FileHelper::generateStoragePath(
            $pbcRequest->client_id,
            $pbcRequest->project_id,
            $pbcRequest->id
        );

        // Store file
        $filePath = $file->storeAs($directory, $storedName, 'local');

        // Create NEW document record (don't update existing ones)
        $document = $item->documents()->create([
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_extension' => $extension,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'status' => 'uploaded', // New upload is always "uploaded" status
        ]);

        // Update item status to "uploaded" (has pending documents)
        $item->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        // Update request status if still pending
        if ($pbcRequest->status === 'pending') {
            $pbcRequest->update(['status' => 'in_progress']);
        }

        return redirect()
            ->route('client.pbc-requests.show', $pbcRequest)
            ->with('success', 'File uploaded successfully.');
    }

    /**
     * Show dashboard with project-filtered data
     */
    public function dashboard()
    {
        $client = auth()->user()->client;

        // Get only assigned projects
        $assignedProjectIds = Project::where('client_id', $client->id)->pluck('id');

        // Get PBC requests for assigned projects only
        $pbcRequests = PbcRequest::where('client_id', $client->id)
            ->whereIn('project_id', $assignedProjectIds)
            ->with(['items.documents', 'project'])
            ->get();

        return view('client.dashboard', compact('pbcRequests'));
    }
}
