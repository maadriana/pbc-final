<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PbcRequest;
use App\Models\PbcRequestItem;
use App\Http\Requests\FileUploadRequest;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PbcRequestController extends Controller
{
    public function index()
    {
        $client = auth()->user()->client;

        $requests = $client->pbcRequests()
            ->with(['project', 'creator'])
            ->latest()
            ->paginate(15);

        return view('client.pbc-requests.index', compact('requests'));
    }

    public function show(PbcRequest $pbcRequest)
    {
        // Check if this request belongs to the current client
        if ($pbcRequest->client_id !== auth()->user()->client->id) {
            abort(403, 'Unauthorized access to this request.');
        }

        $pbcRequest->load([
            'project',
            'creator',
            'items' => function($query) {
                $query->orderBy('order_index');
            },
            'items.documents'
        ]);

        return view('client.pbc-requests.show', compact('pbcRequest'));
    }

    public function upload(Request $request, PbcRequest $pbcRequest, PbcRequestItem $item)
    {
        // Security check
        if ($pbcRequest->client_id !== auth()->user()->client->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($item->pbc_request_id !== $pbcRequest->id) {
            abort(403, 'Invalid request item.');
        }

        // Simple validation
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

        // Create document record
        $document = $item->documents()->create([
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_extension' => $extension,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        // Update item status
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
}
