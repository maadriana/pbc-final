<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\PbcRequestItem;
use App\Http\Requests\FileUploadRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function upload(FileUploadRequest $request)
    {
        $pbcRequestItem = PbcRequestItem::findOrFail($request->pbc_request_item_id);

        // Check if user has permission to upload to this item
        if (auth()->user()->isClient()) {
            $clientId = auth()->user()->client->id;
            if ($pbcRequestItem->pbcRequest->client_id !== $clientId) {
                abort(403, 'Unauthorized to upload to this request.');
            }
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Generate unique filename
        $storedName = Str::uuid() . '.' . $extension;

        // Create directory structure: client_id/project_id/request_id/
        $directory = 'pbc-documents/' .
                    $pbcRequestItem->pbcRequest->client_id . '/' .
                    $pbcRequestItem->pbcRequest->project_id . '/' .
                    $pbcRequestItem->pbcRequest->id;

        // Store file
        $filePath = $file->storeAs($directory, $storedName, 'local');

        // Create document record
        $document = DocumentUpload::create([
            'pbc_request_item_id' => $pbcRequestItem->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_extension' => $extension,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        // Update PBC request item status
        $pbcRequestItem->update([
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        // Update PBC request status to in_progress if it's still pending
        if ($pbcRequestItem->pbcRequest->status === 'pending') {
            $pbcRequestItem->pbcRequest->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'document' => [
                'id' => $document->id,
                'filename' => $document->original_filename,
                'size' => $document->getFileSizeFormatted(),
                'uploaded_at' => $document->created_at->format('M d, Y H:i'),
            ]
        ]);
    }

    public function download(DocumentUpload $document)
    {
        // Check if user has permission to download this file
        if (!$document->canBeAccessedBy(auth()->user())) {
            abort(403, 'Unauthorized to access this file.');
        }

        // Check if file exists
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename
        );
    }

    public function delete(DocumentUpload $document)
    {
        // Only allow clients to delete their own documents, and only if not reviewed yet
        if (auth()->user()->isClient()) {
            if ($document->uploaded_by !== auth()->id() || $document->status !== 'uploaded') {
                abort(403, 'Cannot delete this document.');
            }
        }

        // Delete file from storage
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        // Update PBC request item status back to pending if no other documents
        $pbcRequestItem = $document->pbcRequestItem;
        $document->delete();

        if (!$pbcRequestItem->documents()->exists()) {
            $pbcRequestItem->update([
                'status' => 'pending',
                'uploaded_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }
}
