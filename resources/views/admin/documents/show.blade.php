@extends('layouts.app')
@section('title', 'Document Review')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Document Review</h1>
    <div>
        <span class="badge bg-{{
            $document->status == 'approved' ? 'success' :
            ($document->status == 'rejected' ? 'danger' : 'warning')
        }} fs-6">
            {{ ucfirst($document->status) }}
        </span>
        <a href="{{ route('admin.documents.index') }}" class="btn btn-secondary">Back to Documents</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>File Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Original Filename:</strong> {{ $document->original_filename }}</p>
                <p><strong>File Size:</strong> {{ $document->getFileSizeFormatted() }}</p>
                <p><strong>File Type:</strong> {{ strtoupper($document->file_extension) }}</p>
                <p><strong>MIME Type:</strong> {{ $document->mime_type }}</p>
                <p><strong>Uploaded:</strong> {{ $document->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Uploaded By:</strong> {{ $document->uploader->name }} ({{ $document->uploader->email }})</p>

                <div class="mt-3">
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-success">
                        <i class="fas fa-download"></i> Download File
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Request Information</h5>
            </div>
            <div class="card-body">
                <p><strong>PBC Request:</strong>
                    <a href="{{ route('admin.pbc-requests.show', $document->pbcRequestItem->pbcRequest) }}">
                        {{ $document->pbcRequestItem->pbcRequest->title }}
                    </a>
                </p>
                <p><strong>Client:</strong> {{ $document->pbcRequestItem->pbcRequest->client->company_name }}</p>
                <p><strong>Project:</strong> {{ $document->pbcRequestItem->pbcRequest->project->name }}</p>
                <p><strong>Document Required:</strong> {{ $document->pbcRequestItem->particulars }}</p>
                <p><strong>Category:</strong> {{ $document->pbcRequestItem->category ?? 'General' }}</p>
                @if($document->pbcRequestItem->remarks)
                    <p><strong>Item Remarks:</strong> {{ $document->pbcRequestItem->remarks }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Review Actions -->
@if($document->status == 'uploaded')
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Review Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('admin.documents.approve', $document) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">Approval Notes (Optional)</label>
                                <textarea name="admin_notes" class="form-control" rows="3" placeholder="Add any notes about the approval..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this document?')">
                                <i class="fas fa-check"></i> Approve Document
                            </button>
                        </form>
                    </div>

                    <div class="col-md-6">
                        <form method="POST" action="{{ route('admin.documents.reject', $document) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea name="admin_notes" class="form-control" rows="3" required placeholder="Explain why this document is being rejected..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this document?')">
                                <i class="fas fa-times"></i> Reject Document
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Review History -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Review History</h5>
            </div>
            <div class="card-body">
                @if($document->status == 'approved')
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Document Approved</h6>
                        <p><strong>Approved by:</strong> {{ $document->approver->name }}</p>
                        <p><strong>Approved on:</strong> {{ $document->approved_at->format('M d, Y H:i') }}</p>
                        @if($document->admin_notes)
                            <p><strong>Notes:</strong> {{ $document->admin_notes }}</p>
                        @endif
                    </div>
                @elseif($document->status == 'rejected')
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-times-circle"></i> Document Rejected</h6>
                        <p><strong>Rejected by:</strong> {{ $document->approver->name }}</p>
                        <p><strong>Rejected on:</strong> {{ $document->updated_at->format('M d, Y H:i') }}</p>
                        @if($document->admin_notes)
                            <p><strong>Reason:</strong> {{ $document->admin_notes }}</p>
                        @endif
                    </div>
                @else
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-clock"></i> Pending Review</h6>
                        <p>This document is waiting for admin review and approval.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
