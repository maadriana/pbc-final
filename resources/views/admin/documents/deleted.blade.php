@extends('layouts.app')
@section('title', 'Deleted Documents Archive')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Deleted Documents Archive</h1>
        <p class="text-muted mb-0">View documents that have been deleted from the system</p>
    </div>
    <div>
        <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Active Documents
        </a>
    </div>
</div>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search Deleted Documents</label>
                <input type="text" name="search" class="form-control" placeholder="Search by filename..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Filter by Client</label>
                <select name="client_id" class="form-select">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Deleted Date Range</label>
                <input type="date" name="deleted_from" class="form-control" value="{{ request('deleted_from') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('admin.documents.deleted') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Deleted Documents Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-archive text-danger me-2"></i>
                Deleted Documents ({{ $deletedDocuments->total() ?? 0 }})
            </h5>
            <div class="text-muted small">
                Showing {{ $deletedDocuments->firstItem() ?? 0 }} to {{ $deletedDocuments->lastItem() ?? 0 }} of {{ $deletedDocuments->total() ?? 0 }} results
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Document Details</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Request</th>
                        <th class="px-4 py-3">Size</th>
                        <th class="px-4 py-3">Original Status</th>
                        <th class="px-4 py-3">Deleted Info</th>
                        <th class="px-4 py-3">Originally Uploaded</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deletedDocuments as $document)
                    <tr class="table-light">
                        <td class="px-4 py-3">
                            <span class="fw-bold text-muted">#{{ $document->id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px; font-weight: 600;">
                                    <i class="fas fa-file-slash"></i>
                                </div>
                                <div>
                                    <div class="fw-medium text-muted">{{ Str::limit($document->original_filename, 30) }}</div>
                                    <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> File Deleted</small>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-muted">{{ $document->pbcRequestItem->pbcRequest->client->company_name }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-muted">{{ Str::limit($document->pbcRequestItem->pbcRequest->title, 30) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-muted">{{ $document->file_size_formatted }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($document->status == 'approved')
                                <span class="badge bg-success">Was Approved</span>
                            @elseif($document->status == 'rejected')
                                <span class="badge bg-danger">Was Rejected</span>
                            @else
                                <span class="badge bg-warning">Was Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-muted small">
                                <strong>Deleted:</strong> {{ $document->file_deleted_at->format('M d, Y h:i A') }}
                                <br>
                                <strong>By:</strong>
                                @if($document->fileDeleter)
                                    {{ $document->fileDeleter->name }}
                                @else
                                    <span class="text-muted">Unknown</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-muted small">
                                {{ $document->created_at->format('M d, Y') }}
                                <br>
                                <span class="text-muted">{{ $document->created_at->format('h:i A') }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-archive fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No deleted documents found</div>
                                <small>
                                    @if(request('search') || request('client_id') || request('deleted_from'))
                                        Try adjusting your search criteria or <a href="{{ route('admin.documents.deleted') }}" class="text-decoration-none">clear filters</a>
                                    @else
                                        No documents have been deleted yet
                                    @endif
                                </small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deletedDocuments->hasPages())
    <div class="card-footer bg-white border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $deletedDocuments->firstItem() ?? 0 }} to {{ $deletedDocuments->lastItem() ?? 0 }} of {{ $deletedDocuments->total() ?? 0 }} results
            </div>
            {{ $deletedDocuments->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

@endsection

@section('styles')
<style>
/* Table enhancements for deleted documents */
.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
    border-bottom: 1px solid #f1f3f4;
}

/* Deleted document styling */
.table-light {
    background-color: rgba(248, 249, 250, 0.5);
}

.table-light td {
    opacity: 0.8;
}

/* Badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}

/* Card styling */
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #fff !important;
}

/* Avatar styling for deleted files */
.rounded-circle {
    border-radius: 50% !important;
}

/* Form styling */
.form-label {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.form-control, .form-select {
    border-radius: 0.375rem;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Empty state styling */
.fa-archive {
    color: #6c757d;
}

/* Responsive design */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .px-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
}

/* Search form styling */
.card-body .row {
    align-items: end;
}

/* Pagination styling */
.pagination {
    margin: 0;
}

.page-link {
    border-radius: 0.375rem;
    margin: 0 2px;
    border: 1px solid #dee2e6;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Deleted info styling */
.text-danger small {
    font-weight: 500;
}
</style>
@endsection

@section('scripts')
<script>
// Auto-submit search form with debounce
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        this.form.submit();
    }, 500);
});

// Show loading state for search
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.disabled = true;
});
</script>
@endsection
