@extends('layouts.app')
@section('title', 'PBC Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1>{{ $pbcRequest->title }}</h1>
        <p class="text-muted mb-0">
            Client: {{ $pbcRequest->client->company_name }} |
            Project: {{ $pbcRequest->project->name }}
        </p>
    </div>
    <div>
        <span class="badge bg-{{
            $pbcRequest->status == 'completed' ? 'success' :
            ($pbcRequest->status == 'in_progress' ? 'warning' :
            ($pbcRequest->status == 'overdue' ? 'danger' : 'secondary'))
        }} fs-6 me-2">
            {{ ucfirst(str_replace('_', ' ', $pbcRequest->status)) }}
        </span>

        @if(!$pbcRequest->sent_at)
            <form method="POST" action="{{ route('admin.pbc-requests.send', $pbcRequest) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Send this request to the client?')">
                    Send to Client
                </button>
            </form>
            <a href="{{ route('admin.pbc-requests.edit', $pbcRequest) }}" class="btn btn-warning">Edit</a>
        @else
            <span class="badge bg-info">Sent: {{ $pbcRequest->sent_at->format('M d, Y') }}</span>
        @endif

        <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-secondary">Back to Requests</a>
    </div>
</div>

<!-- Request Information -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Request Information</h5></div>
            <div class="card-body">
                <p><strong>Description:</strong> {{ $pbcRequest->description ?? 'N/A' }}</p>
                <p><strong>Due Date:</strong> {{ $pbcRequest->due_date ? $pbcRequest->due_date->format('M d, Y') : 'N/A' }}</p>
                <p><strong>Progress:</strong> {{ $pbcRequest->getProgressPercentage() }}%</p>
                <p><strong>Created By:</strong> {{ $pbcRequest->creator->name }}</p>
                <p><strong>Created:</strong> {{ $pbcRequest->created_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Client Information</h5></div>
            <div class="card-body">
                <p><strong>Company:</strong> {{ $pbcRequest->client->company_name }}</p>
                <p><strong>Contact:</strong> {{ $pbcRequest->client->contact_person ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $pbcRequest->client->user->email }}</p>
                <p><strong>Phone:</strong> {{ $pbcRequest->client->phone ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Request Items -->
<div class="card">
    <div class="card-header">
        <h5>Request Items ({{ $pbcRequest->items->count() }} items)</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Document Required</th>
                    <th>Status</th>
                    <th>Uploaded Files</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pbcRequest->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->category ?? 'General' }}</td>
                    <td>
                        {{ $item->particulars }}
                        @if($item->remarks)
                            <br><small class="text-muted">{{ $item->remarks }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{
                            $item->status == 'approved' ? 'success' :
                            ($item->status == 'uploaded' ? 'warning' :
                            ($item->status == 'rejected' ? 'danger' : 'secondary'))
                        }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td>
                        @if($item->documents->count() > 0)
                            @foreach($item->documents as $document)
                                <div class="mb-1">
                                    <i class="{{ \App\Helpers\FileHelper::getFileIcon($document->file_extension) }}"></i>
                                    <a href="{{ route('documents.download', $document) }}" class="text-decoration-none">
                                        {{ Str::limit($document->original_filename, 30) }}
                                    </a>
                                    <span class="badge bg-{{ $document->status == 'approved' ? 'success' : ($document->status == 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $document->status }}
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted">No files uploaded</span>
                        @endif
                    </td>
                    <td>
                        @if($item->status == 'uploaded')
                            <form method="POST" action="{{ route('admin.pbc-requests.review-item', [$pbcRequest, $item]) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $item->id }}">
                                Reject
                            </button>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.pbc-requests.review-item', [$pbcRequest, $item]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="reject">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Document</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Reason for rejection:</label>
                                                    <textarea name="admin_notes" class="form-control" rows="3" required placeholder="Explain why this document is being rejected..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reject Document</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
