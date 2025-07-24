@extends('layouts.app')
@section('title', 'PBC Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $pbcRequest->title }}</h1>
    <span class="badge bg-{{ $pbcRequest->status == 'completed' ? 'success' : 'secondary' }} fs-6">{{ ucfirst($pbcRequest->status) }}</span>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <p><strong>Project:</strong> {{ $pbcRequest->project->name }}</p>
        <p><strong>Due Date:</strong> {{ $pbcRequest->due_date ? $pbcRequest->due_date->format('M d, Y') : 'N/A' }}</p>
    </div>
    <div class="col-md-6">
        <p><strong>Progress:</strong> {{ $pbcRequest->getProgressPercentage() }}%</p>
        <p><strong>Description:</strong> {{ $pbcRequest->description ?? 'N/A' }}</p>
    </div>
</div>

<!-- Request Items -->
<h3>Required Documents</h3>
<table class="table">
    <thead>
        <tr><th>Category</th><th>Document Required</th><th>Status</th><th>Upload/Files</th></tr>
    </thead>
    <tbody>
        @foreach($pbcRequest->items as $item)
        <tr>
            <td>{{ $item->category ?? 'General' }}</td>
            <td>{{ $item->particulars }}</td>
            <td>
                <span class="badge bg-{{
                    $item->getCurrentStatus() == 'approved' ? 'success' :
                    ($item->getCurrentStatus() == 'uploaded' ? 'warning' :
                    ($item->getCurrentStatus() == 'rejected' ? 'danger' : 'secondary'))
                }}">
                    {{ ucfirst($item->getCurrentStatus()) }}
                </span>
            </td>
            <td>
                <!-- Show all uploaded documents for this item -->
                @if($item->documents->count() > 0)
                    @foreach($item->documents as $document)
                        <div class="mb-2 p-2 border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="{{ \App\Helpers\FileHelper::getFileIcon($document->file_extension) }}"></i>
                                    <a href="{{ route('documents.download', $document) }}" class="text-decoration-none">
                                        {{ Str::limit($document->original_filename, 25) }}
                                    </a>
                                </div>
                                <span class="badge bg-{{
                                    $document->status == 'approved' ? 'success' :
                                    ($document->status == 'rejected' ? 'danger' : 'warning')
                                }}">
                                    {{ ucfirst($document->status) }}
                                </span>
                            </div>
                            @if($document->status == 'rejected' && $document->admin_notes)
                                <small class="text-danger d-block mt-1">
                                    <strong>Rejection reason:</strong> {{ $document->admin_notes }}
                                </small>
                            @elseif($document->status == 'approved')
                                <small class="text-success d-block mt-1">
                                    <i class="fas fa-check-circle"></i> Approved on {{ $document->approved_at->format('M d, Y') }}
                                </small>
                            @elseif($document->status == 'uploaded')
                                <small class="text-warning d-block mt-1">
                                    <i class="fas fa-clock"></i> Pending admin review
                                </small>
                            @endif
                        </div>
                    @endforeach
                @endif

                <!-- Always allow new uploads if item is not fully approved -->
                @if($item->getCurrentStatus() !== 'approved')
                    <form method="POST" action="{{ route('client.pbc-requests.upload', [$pbcRequest, $item]) }}" enctype="multipart/form-data" class="d-flex gap-2 mt-2">
                        @csrf
                        <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip,.txt" required>
                        <button type="submit" class="btn btn-sm btn-primary">
                            {{ $item->documents->count() > 0 ? 'Upload New Version' : 'Upload' }}
                        </button>
                    </form>
                    @if($item->getCurrentStatus() == 'rejected')
                        <small class="text-muted d-block mt-1">
                            Please upload a new file to replace the rejected document(s).
                        </small>
                    @endif
                @else
                    <div class="text-success mt-2">
                        <i class="fas fa-check-circle"></i> Document approved - No further uploads needed
                    </div>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<a href="{{ route('client.pbc-requests.index') }}" class="btn btn-secondary">Back to Requests</a>
@endsection
