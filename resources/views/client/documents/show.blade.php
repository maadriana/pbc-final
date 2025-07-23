@extends('layouts.app')
@section('title', 'Document Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Document Details</h1>
    <span class="badge bg-{{
        $document->status == 'approved' ? 'success' :
        ($document->status == 'rejected' ? 'danger' : 'warning')
    }} fs-6">
        {{ ucfirst($document->status) }}
    </span>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>File Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Filename:</strong> {{ $document->original_filename }}</p>
                <p><strong>File Size:</strong> {{ $document->getFileSizeFormatted() }}</p>
                <p><strong>File Type:</strong> {{ strtoupper($document->file_extension) }}</p>
                <p><strong>Uploaded:</strong> {{ $document->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Uploaded By:</strong> {{ $document->uploader->name }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Request Information</h5>
            </div>
            <div class="card-body">
                <p><strong>PBC Request:</strong> {{ $document->pbcRequestItem->pbcRequest->title }}</p>
                <p><strong>Project:</strong> {{ $document->pbcRequestItem->pbcRequest->project->name }}</p>
                <p><strong>Document Required:</strong> {{ $document->pbcRequestItem->particulars }}</p>
                <p><strong>Category:</strong> {{ $document->pbcRequestItem->category ?? 'General' }}</p>
            </div>
        </div>
    </div>
</div>

@if($document->status == 'rejected' && $document->admin_notes)
<div class="alert alert-danger mt-3">
    <h6>Rejection Notes:</h6>
    <p>{{ $document->admin_notes }}</p>
</div>
@endif

@if($document->status == 'approved')
<div class="alert alert-success mt-3">
    <h6>Document Approved</h6>
    <p>This document has been approved by {{ $document->approver->name }} on {{ $document->approved_at->format('M d, Y H:i') }}.</p>
    @if($document->admin_notes)
        <p><strong>Notes:</strong> {{ $document->admin_notes }}</p>
    @endif
</div>
@endif

<div class="mt-3">
    <a href="{{ route('documents.download', $document) }}" class="btn btn-success">
        <i class="fas fa-download"></i> Download File
    </a>
    <a href="{{ route('client.documents.index') }}" class="btn btn-secondary">Back to Documents</a>
    <a href="{{ route('client.pbc-requests.show', $document->pbcRequestItem->pbcRequest) }}" class="btn btn-primary">View Request</a>
</div>
@endsection
