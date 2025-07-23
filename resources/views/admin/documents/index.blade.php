@extends('layouts.app')
@section('title', 'Documents Archive')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Documents Archive</h1>
    <div>
        <span class="badge bg-secondary me-2">Total: {{ $stats['total_documents'] }}</span>
        <span class="badge bg-warning me-2">Pending: {{ $stats['pending_review'] }}</span>
        <span class="badge bg-success me-2">Approved: {{ $stats['approved'] }}</span>
        <span class="badge bg-danger">Rejected: {{ $stats['rejected'] }}</span>
    </div>
</div>

<!-- Search Form -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search documents..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="uploaded" {{ request('status') == 'uploaded' ? 'selected' : '' }}>Pending Review</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="client_id" class="form-control">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->company_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
        </div>
        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
        </div>
        <div class="col-md-1">
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
            <th>Client</th>
            <th>Request</th>
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
                {{ Str::limit($document->original_filename, 30) }}
            </td>
            <td>{{ $document->pbcRequestItem->pbcRequest->client->company_name }}</td>
            <td>{{ Str::limit($document->pbcRequestItem->pbcRequest->title, 30) }}</td>
            <td>{{ $document->getFileSizeFormatted() }}</td>
            <td>
                <span class="badge bg-{{
                    $document->status == 'approved' ? 'success' :
                    ($document->status == 'rejected' ? 'danger' : 'warning')
                }}">
                    {{ ucfirst($document->status) }}
                </span>
            </td>
            <td>{{ $document->created_at->format('M d, Y') }}</td>
            <td>
                <a href="{{ route('admin.documents.show', $document) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View</a>
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
