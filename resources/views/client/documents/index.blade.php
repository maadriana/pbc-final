@extends('layouts.app')
@section('title', 'My Documents')

@section('content')
<h1>My Documents</h1>

<!-- Simple Search Form -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search documents..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="uploaded" {{ request('status') == 'uploaded' ? 'selected' : '' }}>Pending Review</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </div>
</form>

<!-- Documents Table -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Filename</th>
            <th>Request</th>
            <th>Document Type</th>
            <th>Size</th>
            <th>Status</th>
            <th>Uploaded</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($documents as $document)
        <tr>
            <td>{{ $document->id }}</td>
            <td>
                <i class="{{ \App\Helpers\FileHelper::getFileIcon($document->file_extension) }}"></i>
                {{ Str::limit($document->original_filename, 40) }}
            </td>
            <td>{{ $document->pbcRequestItem->pbcRequest->title }}</td>
            <td>{{ Str::limit($document->pbcRequestItem->particulars, 50) }}</td>
            <td>{{ $document->getFileSizeFormatted() }}</td>
            <td>
                <span class="badge bg-{{
                    $document->status == 'approved' ? 'success' :
                    ($document->status == 'rejected' ? 'danger' : 'warning')
                }}">
                    {{ ucfirst($document->status) }}
                </span>
                @if($document->status == 'rejected' && $document->admin_notes)
                    <br><small class="text-danger">{{ Str::limit($document->admin_notes, 50) }}</small>
                @endif
            </td>
            <td>{{ $document->created_at->format('M d, Y H:i') }}</td>
            <td>
                <a href="{{ route('client.documents.show', $document) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View</a>
                <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-success">Download</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center">No documents found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $documents->links() }}
@endsection
