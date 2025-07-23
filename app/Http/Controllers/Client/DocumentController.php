<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DocumentUpload;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $client = auth()->user()->client;

        $query = DocumentUpload::whereHas('pbcRequestItem.pbcRequest', function($q) use ($client) {
            $q->where('client_id', $client->id);
        })
        ->with([
            'pbcRequestItem.pbcRequest.project',
            'pbcRequestItem'
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                  ->orWhereHas('pbcRequestItem.pbcRequest', function($requestQuery) use ($search) {
                      $requestQuery->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->latest()->paginate(20);

        return view('client.documents.index', compact('documents'));
    }

    public function show(DocumentUpload $document)
    {
        // Check access permission
        if (!$document->canBeAccessedBy(auth()->user())) {
            abort(403, 'Unauthorized access to this document.');
        }

        $document->load([
            'pbcRequestItem.pbcRequest.project',
            'pbcRequestItem',
            'uploader',
            'approver'
        ]);

        return view('client.documents.show', compact('document'));
    }
}
