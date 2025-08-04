@extends('layouts.app')
@section('title', 'PBC Request Details')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $pbcRequest->title }}</h1>
        <p class="text-muted mb-0">View and upload required documents for this PBC request</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $pbcRequest->status == 'completed' ? 'success' : 'secondary' }} fs-6">{{ ucfirst($pbcRequest->status) }}</span>
        <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Requests
        </a>
    </div>
</div>

<!-- Request Details Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="fas fa-info-circle text-primary me-2"></i>
            Request Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Project</label>
                    <div class="fw-medium">{{ $pbcRequest->project->name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Due Date</label>
                    <div class="fw-medium">{{ $pbcRequest->due_date ? $pbcRequest->due_date->format('M d, Y') : 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label small text-muted">Progress</label>
                    <div class="fw-medium">{{ $pbcRequest->getProgressPercentage() }}%</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Description</label>
                    <div class="fw-medium">{{ $pbcRequest->description ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Required Documents Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Required Documents
            </h5>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Document Required</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Upload/Files</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pbcRequest->items as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->category ?? 'General' }}</td>
                        <td class="px-4 py-3">{{ $item->particulars }}</td>
                        <td class="px-4 py-3">
                            <span class="badge bg-{{
                                $item->getCurrentStatus() == 'approved' ? 'success' :
                                ($item->getCurrentStatus() == 'uploaded' ? 'warning' :
                                ($item->getCurrentStatus() == 'rejected' ? 'danger' : 'secondary'))
                            }}">
                                {{ ucfirst($item->getCurrentStatus()) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
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
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Table enhancements */
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

/* Badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}

/* Button styling */
.btn {
    font-weight: 500;
    border-radius: 0.375rem;
}

/* Card styling */
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #fff !important;
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

/* Document container styling */
.border.rounded {
    transition: all 0.2s ease;
}

.border.rounded:hover {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
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

    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add CSRF token if missing
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.warn('CSRF token meta tag not found. Adding it...');
        const metaTag = document.createElement('meta');
        metaTag.name = 'csrf-token';
        metaTag.content = document.querySelector('input[name="_token"]')?.value || '';
        document.head.appendChild(metaTag);
    }

    // Add loading states to upload forms
    const uploadForms = document.querySelectorAll('form[enctype="multipart/form-data"]');
    uploadForms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
        });
    });
});
</script>
@endsection
