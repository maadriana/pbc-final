@extends('layouts.app')
@section('title', 'Documents Archive')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Documents Archive</h1>
        <p class="text-muted mb-0">Manage and review all uploaded documents</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display: none;" onclick="bulkDeleteDocuments()">
            <i class="fas fa-trash me-2"></i>Delete Selected
        </button>
        {{-- ADDED: Link to view deleted documents --}}
        <a href="{{ route('admin.documents.deleted') }}" class="btn btn-outline-secondary">
            <i class="fas fa-archive me-2"></i>Deleted Archive
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['total_documents'] }}</h4>
                <small>Total Documents</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['pending_review'] }}</h4>
                <small>Pending Review</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['approved'] }}</h4>
                <small>Approved</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['rejected'] }}</h4>
                <small>Rejected</small>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Search Documents</label>
                <input type="text" name="search" class="form-control" placeholder="Search by filename..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Filter by Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="uploaded" {{ request('status') == 'uploaded' ? 'selected' : '' }}>Pending Review</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
                <label class="form-label small text-muted">From Date</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">To Date</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Documents Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">
                        Select All
                    </label>
                </div>
                <h5 class="mb-0">
                    <i class="fas fa-folder-open text-primary me-2"></i>
                    Active Documents (<span id="documentCount">{{ $documents->total() ?? 0 }}</span>)
                </h5>
            </div>
            <div class="text-muted small">
                Showing {{ $documents->firstItem() ?? 0 }} to {{ $documents->lastItem() ?? 0 }} of {{ $documents->total() ?? 0 }} results
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3" style="width: 50px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllTable">
                            </div>
                        </th>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Document Details</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Request</th>
                        <th class="px-4 py-3">Size</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Uploaded</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                    <tr>
                        <td class="px-4 py-3">
                            {{-- FIXED: Only show checkbox for documents that can be deleted --}}
                            @if(in_array($document->status, ['approved', 'rejected']))
                                <div class="form-check">
                                    <input class="form-check-input document-checkbox" type="checkbox" value="{{ $document->id }}">
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="fw-bold text-primary">#{{ $document->id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px; font-weight: 600;">
                                    <i class="{{ \App\Helpers\FileHelper::getFileIcon($document->file_extension) }}"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ Str::limit($document->original_filename, 30) }}</div>
                                    <small class="text-muted">{{ strtoupper($document->file_extension) }} File</small>
                                    {{-- ADDED: Show file availability status --}}
                                    @if(!$document->isFileAvailable())
                                        <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> {{ $document->file_status }}</small>
                                    @endif
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
                                <span class="badge bg-success">Approved</span>
                            @elseif($document->status == 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-warning">Pending Review</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-muted small">
                                {{ $document->created_at->format('M d, Y') }}
                                <br>
                                <span class="text-muted">{{ $document->created_at->format('h:i A') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.documents.show', $document) }}" class="btn btn-primary btn-sm" title="View Document">
                                    <i class="fas fa-eye"></i>
                                </a>
                                {{-- FIXED: Only show download if file is available --}}
                                @if($document->isFileAvailable())
                                    <a href="{{ route('documents.download', $document) }}" class="btn btn-success btn-sm" title="Download Document">
                                        <i class="fas fa-download"></i>
                                    </a>
                                @endif
                                @if($document->status == 'uploaded')
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reviewDocument({{ $document->id }})" title="Review Document">
                                        <i class="fas fa-clipboard-check"></i>
                                    </button>
                                @endif
                                {{-- FIXED: Only allow deletion of approved/rejected documents --}}
                                @if(in_array($document->status, ['approved', 'rejected']))
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteDocument({{ $document->id }}, '{{ addslashes($document->original_filename) }}')" title="Delete Document">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No documents found</div>
                                <small>
                                    @if(request('search') || request('status') || request('client_id') || request('date_from') || request('date_to'))
                                        Try adjusting your search criteria or <a href="{{ route('admin.documents.index') }}" class="text-decoration-none">clear filters</a>
                                    @else
                                        Documents will appear here when they are uploaded
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
    @if($documents->hasPages())
    <div class="card-footer bg-white border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $documents->firstItem() ?? 0 }} to {{ $documents->lastItem() ?? 0 }} of {{ $documents->total() ?? 0 }} results
            </div>
            {{ $documents->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Choose an action for this document:</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="approveDocument()">
                        <i class="fas fa-check me-2"></i>Approve Document
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectDocument()">
                        <i class="fas fa-times me-2"></i>Reject Document
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>Are you sure you want to delete this document?</h5>
                    <p class="text-muted mb-0">
                        <strong id="deleteFileName"></strong>
                    </p>
                    <small class="text-danger">This action will permanently delete the file from the server.</small>
                    <div class="alert alert-info mt-3">
                        <small><i class="fas fa-info-circle"></i> The document record will be kept for audit purposes, but the file will be permanently removed.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete Document
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Bulk Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>Are you sure you want to delete the selected documents?</h5>
                    <p class="text-muted">
                        You have selected <strong id="bulkDeleteCount"></strong> document(s) for deletion.
                    </p>
                    <small class="text-danger">This action will permanently delete all selected files from the server.</small>
                    <div class="alert alert-info mt-3">
                        <small><i class="fas fa-info-circle"></i> Document records will be kept for audit purposes, but files will be permanently removed.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmBulkDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete Selected Documents
                </button>
            </div>
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

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Card styling */
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #fff !important;
}

/* Stats cards */
.card.bg-primary, .card.bg-info, .card.bg-warning,
.card.bg-danger, .card.bg-success {
    border: none;
    border-radius: 0.5rem;
}

/* Avatar styling */
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

/* Modal improvements */
.modal-content {
    border-radius: 0.5rem;
}

/* Empty state styling */
.fa-folder-open {
    color: #6c757d;
}

/* Checkbox styling */
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Bulk delete button animation */
#bulkDeleteBtn {
    transition: all 0.3s ease;
}

/* Loading state */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin-top: -8px;
    margin-left: -8px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* File status indicators */
.text-danger small {
    font-weight: 500;
}

/* Responsive design */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
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
</style>
@endsection

@section('scripts')
<script>
let currentDocumentId = null;
let selectedDocuments = [];

// Set up base URLs using Laravel route helpers
const baseUrl = '{{ url("/admin/documents") }}';
const bulkDeleteUrl = '{{ route("admin.documents.bulk-delete") }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Review functionality
function reviewDocument(documentId) {
    currentDocumentId = documentId;
    const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
    reviewModal.show();
}

function approveDocument() {
    if (!currentDocumentId) return;

    if (confirm('Approve this document?')) {
        // Add your approve logic here
        showAlert('Document approved successfully!', 'success');
        const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
        if (modal) modal.hide();
        setTimeout(() => window.location.reload(), 1000);
    }
}

function rejectDocument() {
    if (!currentDocumentId) return;

    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        // Add your reject logic here
        showAlert('Document rejected successfully!', 'success');
        const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
        if (modal) modal.hide();
        setTimeout(() => window.location.reload(), 1000);
    }
}

// Delete functionality
function deleteDocument(documentId, filename) {
    currentDocumentId = documentId;
    document.getElementById('deleteFileName').textContent = filename;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Confirm single document delete
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!currentDocumentId) return;

    const btn = this;
    const originalText = btn.innerHTML;

    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    btn.disabled = true;

    const deleteUrl = `{{ url('/admin/documents') }}/${currentDocumentId}`;

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Remove the row from table with animation
            const row = document.querySelector(`input[value="${currentDocumentId}"]`).closest('tr');
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    updateDocumentCount();
                }, 300);
            }
        } else {
            showAlert(data.message || 'Failed to delete document', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while deleting the document: ' + error.message, 'danger');
    })
    .finally(() => {
        // Reset button state
        btn.innerHTML = originalText;
        btn.disabled = false;

        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        if (modal) modal.hide();

        currentDocumentId = null;
    });
});

// Checkbox functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllTableCheckbox = document.getElementById('selectAllTable');
    const documentCheckboxes = document.querySelectorAll('.document-checkbox');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    // Sync the two "select all" checkboxes
    selectAllCheckbox.addEventListener('change', function() {
        selectAllTableCheckbox.checked = this.checked;
        documentCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedDocuments();
    });

    selectAllTableCheckbox.addEventListener('change', function() {
        selectAllCheckbox.checked = this.checked;
        documentCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedDocuments();
    });

    // Individual checkbox change
    documentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedDocuments();

            // Update "select all" checkboxes
            const allChecked = Array.from(documentCheckboxes).every(cb => cb.checked);
            const noneChecked = Array.from(documentCheckboxes).every(cb => !cb.checked);

            selectAllCheckbox.checked = allChecked;
            selectAllTableCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
            selectAllTableCheckbox.indeterminate = !allChecked && !noneChecked;
        });
    });

    function updateSelectedDocuments() {
        selectedDocuments = Array.from(documentCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);

        if (selectedDocuments.length > 0) {
            bulkDeleteBtn.style.display = 'inline-block';
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash me-2"></i>Delete Selected (${selectedDocuments.length})`;
        } else {
            bulkDeleteBtn.style.display = 'none';
        }
    }
});

// Bulk delete functionality
function bulkDeleteDocuments() {
    if (selectedDocuments.length === 0) {
        showAlert('Please select documents to delete', 'warning');
        return;
    }

    document.getElementById('bulkDeleteCount').textContent = selectedDocuments.length;
    const bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
    bulkDeleteModal.show();
}

// Confirm bulk delete
document.getElementById('confirmBulkDeleteBtn').addEventListener('click', function() {
    if (selectedDocuments.length === 0) return;

    const btn = this;
    const originalText = btn.innerHTML;

    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    btn.disabled = true;

    const bulkDeleteUrl = '{{ route("admin.documents.bulk-delete") }}';

    fetch(bulkDeleteUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            document_ids: selectedDocuments
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');

            // Remove rows from table
            selectedDocuments.forEach(documentId => {
                const row = document.querySelector(`input[value="${documentId}"]`).closest('tr');
                if (row) {
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                    }, 300);
                }
            });

            setTimeout(() => {
                updateDocumentCount();
                resetSelections();
            }, 300);
        } else {
            showAlert(data.message || 'Failed to delete documents', 'danger');
            if (data.errors) {
                console.error('Bulk delete errors:', data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while deleting documents: ' + error.message, 'danger');
    })
    .finally(() => {
        // Reset button state
        btn.innerHTML = originalText;
        btn.disabled = false;

        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
        if (modal) modal.hide();
    });
});

function resetSelections() {
    selectedDocuments = [];
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAllTable').checked = false;
    document.querySelectorAll('.document-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('bulkDeleteBtn').style.display = 'none';
}

function updateDocumentCount() {
    const remainingRows = document.querySelectorAll('tbody tr:not([style*="opacity: 0"])').length;
    const countElement = document.getElementById('documentCount');
    if (countElement) {
        const currentCount = parseInt(countElement.textContent);
        countElement.textContent = Math.max(0, currentCount - (selectedDocuments.length || 1));
    }
}

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

// Enhanced alert function
function showAlert(message, type) {
    // Remove any existing alerts first
    const existingAlerts = document.querySelectorAll('.alert.position-fixed');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';

    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };

    alertDiv.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                bsAlert.close();
            } catch (e) {
                alertDiv.remove();
            }
        }
    }, 5000);
}
</script>
@endsection
