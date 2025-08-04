@extends('layouts.app')
@section('title', $project->engagement_name . ' - PBC Requests')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $project->engagement_name ?? 'Statutory Audit for YE122024' }}</h1>
        <p class="text-muted mb-0">
            {{ $client->company_name }} |
            Job ID: {{ $project->job_id ?? '1-01-001' }} |
            Partner: {{ $project->engagementPartner->name ?? 'EYM' }} |
            Manager: {{ $project->manager->name ?? 'MNGR 1' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.clients.projects.pbc-requests.import', [$client, $project]) }}" class="btn btn-info">
            <i class="fas fa-upload me-2"></i>Import Request
        </a>
        <a href="{{ route('admin.clients.projects.pbc-requests.create', [$client, $project]) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Send Request
        </a>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Projects
        </a>
    </div>
</div>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search Requests</label>
                <input type="text" name="search" class="form-control" placeholder="Search by description..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Filter by Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Uploaded</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Filter by Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="CF" {{ request('category') == 'CF' ? 'selected' : '' }}>CF</option>
                    <option value="Other" {{ request('category') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
@if(isset($stats))
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['total_requests'] }}</h4>
                <small>Total Requests</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['pending'] }}</h4>
                <small>Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['in_progress'] }}</h4>
                <small>In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white border-0 shadow-sm">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['completed'] }}</h4>
                <small>Completed</small>
            </div>
        </div>
    </div>
</div>
@endif

<!-- PBC Requests Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                PBC Request Items ({{ $requests->sum(function($r) { return $r->items->count(); }) }})
            </h5>
            <div class="text-muted small">
                {{ $requests->count() }} request(s) with {{ $requests->sum(function($r) { return $r->items->count(); }) }} total items
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Request Description</th>
                        <th class="px-4 py-3">Requestor</th>
                        <th class="px-4 py-3">Date Requested</th>
                        <th class="px-4 py-3">Assigned to</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                        <th class="px-4 py-3">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        @foreach($request->items as $item)
                        @php
                            $status = $item->getCurrentStatus();
                            $statusColors = [
                                'pending' => 'warning',
                                'uploaded' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'overdue' => 'danger'
                            ];
                            $statusColor = $statusColors[$status] ?? 'warning';

                            // Get rejection/approval notes
                            $rejectedDoc = $item->documents->where('status', 'rejected')->first();
                            $approvedDoc = $item->documents->where('status', 'approved')->first();
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <span class="badge {{ $item->category == 'CF' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $item->category ?? 'CF' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <div class="fw-medium">{{ $item->particulars }}</div>
                                    @if($item->documents->count() > 0)
                                        <small class="text-muted">{{ $item->documents->count() }} file(s)</small>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-muted">{{ $item->requestor ?? 'MNGR 1' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-muted small">
                                    {{ $item->date_requested_formatted ?? '25/07/2025' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-muted">{{ $item->assigned_to ?? 'Client Staff 1' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-muted small">
                                    {{ $item->due_date_formatted ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="btn-group" role="group">
                                    @if($status == 'pending')
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openUploadModal({{ $item->id }})" title="Upload Document">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    @elseif($status == 'uploaded')
                                        @if($item->documents->where('status', 'uploaded')->first())
                                            <button type="button" class="btn btn-info btn-sm" onclick="viewDocument({{ $item->documents->where('status', 'uploaded')->first()->id }})" title="View Document">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-success btn-sm" onclick="approveItem({{ $item->id }})" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="openRejectModal({{ $item->id }}, {{ $request->id }})" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @elseif($status == 'approved')
                                        @if($item->documents->where('status', 'approved')->first())
                                            <button type="button" class="btn btn-success btn-sm" onclick="viewDocument({{ $item->documents->where('status', 'approved')->first()->id }})" title="View Document">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
                                    @elseif($status == 'rejected')
                                        <button type="button" class="btn btn-warning btn-sm" onclick="openUploadModal({{ $item->id }})" title="Re-upload Document">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    @elseif($status == 'overdue')
                                        <button class="btn btn-danger btn-sm" onclick="sendReminder({{ $item->id }})" title="Send Reminder">
                                            <i class="fas fa-bell"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openUploadModal({{ $item->id }})" title="Upload Document">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openUploadModal({{ $item->id }})" title="Upload Document">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-truncate" style="max-width: 150px;">
                                    @if($status === 'rejected' && $rejectedDoc && $rejectedDoc->admin_notes)
                                        <span class="text-danger" title="{{ $rejectedDoc->admin_notes }}">
                                            {{ Str::limit($rejectedDoc->admin_notes, 30) }}
                                        </span>
                                    @elseif($status === 'approved' && $approvedDoc && $approvedDoc->admin_notes)
                                        <span class="text-success" title="{{ $approvedDoc->admin_notes }}">
                                            {{ Str::limit($approvedDoc->admin_notes, 30) }}
                                        </span>
                                    @elseif($item->remarks)
                                        <span title="{{ $item->remarks }}">{{ Str::limit($item->remarks, 30) }}</span>
                                    @else
                                        <span class="text-muted">No notes</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No PBC requests found</div>
                                <small>
                                    @if(request('search') || request('status') || request('category'))
                                        Try adjusting your search criteria or <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}" class="text-decoration-none">clear filters</a>
                                    @else
                                        This project doesn't have any PBC requests yet
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
</div>

<!-- Enhanced Upload Modal for 300MB files -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload Document
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="itemId" name="item_id">

                    <!-- File Selection Section -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-file me-2"></i>Select File
                        </label>
                        <input type="file"
                               class="form-control form-control-lg"
                               name="document"
                               id="documentFile"
                               required
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar,.txt,.csv"
                               onchange="handleFileSelection()">

                        <!-- File Format Guidelines -->
                        <div class="mt-2">
                            <small class="text-muted d-block">
                                <strong>Accepted formats:</strong> PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, PNG, JPG, JPEG, ZIP, RAR, TXT, CSV
                            </small>
                            <small class="text-success d-block">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Maximum file size: 300MB</strong>
                            </small>
                        </div>
                    </div>

                    <!-- File Preview Area (dynamically populated) -->
                    <div id="filePreviewArea" style="display: none;" class="mb-4">
                        <div class="card border-success">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-eye me-2"></i>Selected File Preview
                                </h6>
                            </div>
                            <div class="card-body" id="fileInfo">
                                <!-- File information will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Upload Progress Section -->
                    <div class="mb-4">
                        <div class="progress" style="display: none; height: 25px;" id="uploadProgress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar"
                                 style="width: 0%"
                                 aria-valuenow="0"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                0%
                            </div>
                        </div>

                        <!-- Upload Status Messages -->
                        <div id="uploadStatus" style="display: none;" class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <span id="statusMessage">Preparing upload...</span>
                            </small>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-sticky-note me-2"></i>Notes (Optional)
                        </label>
                        <textarea class="form-control"
                                  name="notes"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Add any notes about this document (e.g., version info, special instructions)..."></textarea>
                        <div class="form-text">
                            <small class="text-muted">Maximum 500 characters</small>
                        </div>
                    </div>

                    <!-- Upload Guidelines -->
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="fas fa-lightbulb me-2 mt-1"></i>
                        <div>
                            <strong>Upload Guidelines:</strong>
                            <ul class="mb-0 mt-1">
                                <li>Ensure stable internet connection for large files</li>
                                <li>Do not close this window during upload</li>
                                <li>Upload may take several minutes for large files</li>
                                <li>File will be automatically scanned for security</li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="uploadDocument()" id="uploadButton">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload Document
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    @csrf
                    <input type="hidden" id="rejectItemId" name="item_id">
                    <input type="hidden" id="rejectRequestId" name="request_id">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection *</label>
                        <textarea class="form-control" name="reason" rows="4" required
                                  placeholder="Please provide a detailed reason for rejection..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="rejectDocument()">Reject</button>
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

/* Enhanced modal styling for better UX */
.modal-lg {
    max-width: 800px;
}

.form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1.1rem;
}

.progress {
    border-radius: 10px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

.progress-bar {
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 25px;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.file-preview-card {
    border: 2px dashed #28a745;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.upload-guidelines {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-left: 4px solid #28a745;
}

/* File type icons */
.file-icon {
    font-size: 2rem;
    color: #6c757d;
}

/* Animation for upload states */
.upload-success {
    animation: success-pulse 0.5s ease-in-out;
}

@keyframes success-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.upload-error {
    animation: error-shake 0.5s ease-in-out;
}

@keyframes error-shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Stats cards */
.card.bg-primary, .card.bg-info, .card.bg-warning,
.card.bg-danger, .card.bg-success {
    border: none;
    border-radius: 0.5rem;
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

/* Text truncation */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Empty state styling */
.fa-file-alt {
    color: #6c757d;
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

    .modal-lg {
        max-width: 95%;
        margin: 1rem auto;
    }

    .modal-body {
        padding: 1rem;
    }

    .form-control-lg {
        font-size: 1rem;
        padding: 0.5rem 0.75rem;
    }
}

/* Search form styling */
.card-body .row {
    align-items: end;
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
// Global variables for upload system
let uploadLimits = null;

// Debug: Log the route information
console.log('=== ROUTE DEBUG INFO ===');
console.log('Current URL:', window.location.pathname);
console.log('Client ID from Blade:', {{ $client->id }});
console.log('Project ID from Blade:', {{ $project->id }});

// Parse URL to get client and project IDs
const urlParts = window.location.pathname.split('/');
console.log('URL Parts:', urlParts);

const clientIndex = urlParts.indexOf('clients');
const projectIndex = urlParts.indexOf('projects');
const clientIdFromUrl = urlParts[clientIndex + 1];
const projectIdFromUrl = urlParts[projectIndex + 1];

console.log('Client ID from URL:', clientIdFromUrl);
console.log('Project ID from URL:', projectIdFromUrl);

document.addEventListener('DOMContentLoaded', function() {
    // Load upload limits on page load
    loadUploadLimits();

    // Auto-submit filter form when selection changes
    const statusSelect = document.querySelector('select[name="status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }

    // Add CSRF token to all fetch requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.warn('CSRF token meta tag not found. Adding it...');
        const metaTag = document.createElement('meta');
        metaTag.name = 'csrf-token';
        metaTag.content = document.querySelector('input[name="_token"]')?.value || '';
        document.head.appendChild(metaTag);
    }
});

// Load system upload limits
function loadUploadLimits() {
    fetch('/admin/upload/limits')
        .then(response => response.json())
        .then(data => {
            console.log('Upload limits loaded:', data);
            uploadLimits = data;
        })
        .catch(error => {
            console.error('Failed to load upload limits:', error);
            // Set default limits if API fails
            uploadLimits = {
                max_file_size: 314572800, // 300MB
                max_file_size_formatted: '300MB',
                allowed_extensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'txt', 'csv']
            };
        });
}

// Helper function to get CSRF token
function getCsrfToken() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                     document.querySelector('input[name="_token"]')?.value;

    if (!csrfToken) {
        console.error('CSRF token not found');
        showAlert('Security token not found. Please refresh the page.', 'danger');
        return null;
    }

    return csrfToken;
}

// Open upload modal
function openUploadModal(itemId) {
    console.log('Opening upload modal for item:', itemId);

    const itemIdInput = document.getElementById('itemId');
    const uploadForm = document.getElementById('uploadForm');

    if (!itemIdInput || !uploadForm) {
        console.error('Upload modal elements not found');
        showAlert('Upload form not found. Please refresh the page.', 'danger');
        return;
    }

    // Reset form and set item ID
    uploadForm.reset();
    itemIdInput.value = itemId;

    // Reset modal state
    const previewArea = document.getElementById('filePreviewArea');
    const progressContainer = document.getElementById('uploadProgress');
    const uploadButton = document.getElementById('uploadButton');

    if (previewArea) previewArea.style.display = 'none';
    if (progressContainer) progressContainer.style.display = 'none';
    if (uploadButton) {
        uploadButton.disabled = false;
        uploadButton.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Upload Document';
    }

    const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
    uploadModal.show();
}

// Open reject modal
function openRejectModal(itemId, requestId) {
    console.log('Opening reject modal for item:', itemId, 'request:', requestId);

    const rejectItemId = document.getElementById('rejectItemId');
    const rejectRequestId = document.getElementById('rejectRequestId');
    const rejectForm = document.getElementById('rejectForm');

    if (!rejectItemId || !rejectRequestId || !rejectForm) {
        console.error('Reject modal elements not found');
        showAlert('Reject form not found. Please refresh the page.', 'danger');
        return;
    }

    // Reset form and set IDs
    rejectForm.reset();
    rejectItemId.value = itemId;
    rejectRequestId.value = requestId;

    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// Enhanced file selection handler with real-time validation
function handleFileSelection() {
    const fileInput = document.getElementById('documentFile');
    const previewArea = document.getElementById('filePreviewArea');
    const fileInfo = document.getElementById('fileInfo');
    const uploadButton = document.getElementById('uploadButton');

    if (!fileInput || !fileInput.files[0]) {
        if (previewArea) previewArea.style.display = 'none';
        if (uploadButton) uploadButton.disabled = false;
        return;
    }

    const file = fileInput.files[0];
    const maxSize = uploadLimits ? uploadLimits.max_file_size : 314572800; // 300MB
    const isValidSize = file.size <= maxSize;
    const isValidType = validateFileType(file.name);

    // Show preview area
    if (previewArea) previewArea.style.display = 'block';

    // Generate file preview with enhanced information
    const fileIcon = getFileIcon(file.name);
    const sizeFormatted = formatFileSize(file.size);
    const lastModified = new Date(file.lastModified).toLocaleDateString();

    if (fileInfo) {
        fileInfo.innerHTML = `
            <div class="row align-items-center">
                <div class="col-auto">
                    <i class="fas fa-file${fileIcon} fa-3x ${isValidType ? 'text-primary' : 'text-danger'}"></i>
                </div>
                <div class="col">
                    <h6 class="mb-1 text-truncate" title="${file.name}">${file.name}</h6>
                    <p class="mb-1">
                        <span class="badge ${isValidType ? 'bg-success' : 'bg-danger'}">
                            ${isValidType ? 'Valid Format' : 'Invalid Format'}
                        </span>
                        <span class="badge ${isValidSize ? 'bg-success' : 'bg-danger'} ms-1">
                            ${sizeFormatted}
                        </span>
                    </p>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>Modified: ${lastModified}
                    </small>
                </div>
            </div>

            ${!isValidSize || !isValidType ? `
            <div class="alert alert-danger mt-3 mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${!isValidSize ? `File size (${sizeFormatted}) exceeds 300MB limit. ` : ''}
                ${!isValidType ? 'File type not supported. ' : ''}
                Please select a different file.
            </div>
            ` : `
            <div class="alert alert-success mt-3 mb-0">
                <i class="fas fa-check-circle me-2"></i>
                File is ready for upload!
            </div>
            `}
        `;
    }

    // Enable/disable upload button
    if (uploadButton) uploadButton.disabled = !isValidSize || !isValidType;
}

// Validate file type
function validateFileType(filename) {
    const allowedExtensions = uploadLimits ?
        uploadLimits.allowed_extensions :
        ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'txt', 'csv'];

    const extension = filename.split('.').pop().toLowerCase();
    return allowedExtensions.includes(extension);
}

// Get file icon based on extension
function getFileIcon(filename) {
    const extension = filename.split('.').pop().toLowerCase();
    const iconMap = {
        'pdf': '-pdf',
        'doc': '-word', 'docx': '-word',
        'xls': '-excel', 'xlsx': '-excel',
        'ppt': '-powerpoint', 'pptx': '-powerpoint',
        'jpg': '-image', 'jpeg': '-image', 'png': '-image',
        'zip': '-archive', 'rar': '-archive',
        'txt': '-alt', 'csv': '-csv'
    };

    return iconMap[extension] || '';
}

// Enhanced file size formatting
function formatFileSize(bytes) {
    if (!bytes || bytes <= 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;

    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }

    return `${bytes.toFixed(i === 0 ? 0 : 2)} ${units[i]}`;
}

// Enhanced upload function with XMLHttpRequest and progress tracking
function uploadDocument() {
    console.log('Starting document upload...');

    const form = document.getElementById('uploadForm');
    const itemId = document.getElementById('itemId').value;
    const fileInput = form.querySelector('input[name="document"]');
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = progressContainer.querySelector('.progress-bar');
    const statusContainer = document.getElementById('uploadStatus');
    const statusMessage = document.getElementById('statusMessage');

    if (!form || !itemId || !fileInput.files[0]) {
        showAlert('Please select a file to upload', 'danger');
        return;
    }

    const file = fileInput.files[0];
    const maxSize = uploadLimits ? uploadLimits.max_file_size : 314572800; // 300MB

    // File size validation
    if (file.size > maxSize) {
        showAlert('File size exceeds 300MB limit. Please choose a smaller file.', 'danger');
        return;
    }

    // File type validation
    if (!validateFileType(file.name)) {
        showAlert('File type not allowed. Please select a valid file format.', 'danger');
        return;
    }

    const formData = new FormData(form);
    const uploadBtn = document.getElementById('uploadButton');
    const cancelBtn = document.querySelector('#uploadModal .btn-secondary');

    if (!uploadBtn) {
        console.error('Upload button not found');
        return;
    }

    // Show progress and disable controls
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    uploadBtn.disabled = true;
    if (cancelBtn) cancelBtn.disabled = true;

    progressContainer.style.display = 'block';
    statusContainer.style.display = 'block';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    progressBar.classList.remove('bg-danger', 'bg-success');
    progressBar.classList.add('bg-primary');

    // Get CSRF token
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        resetUploadState(uploadBtn, cancelBtn, originalText, progressContainer, statusContainer);
        return;
    }

    // Create XMLHttpRequest for progress tracking
    const xhr = new XMLHttpRequest();
    const uploadStartTime = Date.now();

    // Track upload progress
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percentComplete + '%';
            progressBar.textContent = percentComplete + '%';

            // Show upload speed and estimated time
            if (e.loaded > 0 && uploadStartTime) {
                const uploadSpeed = e.loaded / ((Date.now() - uploadStartTime) / 1000); // bytes per second
                const remainingBytes = e.total - e.loaded;
                const estimatedTime = remainingBytes / uploadSpeed;

                if (estimatedTime > 60) {
                    const minutes = Math.floor(estimatedTime / 60);
                    const seconds = Math.floor(estimatedTime % 60);
                    statusMessage.textContent = `${formatFileSize(uploadSpeed)}/s - ${minutes}m ${seconds}s remaining`;
                } else if (estimatedTime > 0) {
                    statusMessage.textContent = `${formatFileSize(uploadSpeed)}/s - ${Math.floor(estimatedTime)}s remaining`;
                } else {
                    statusMessage.textContent = `${formatFileSize(uploadSpeed)}/s - Almost complete`;
                }
            }
        }
    }, false);

    // Handle completion
    xhr.addEventListener('load', function() {
        try {
            const response = JSON.parse(xhr.responseText);
            console.log('Upload response:', response);

            if (xhr.status === 200 && response.success) {
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-success');
                progressBar.textContent = '100% - Upload Complete!';
                statusMessage.textContent = 'File uploaded successfully!';

                showAlert('Document uploaded successfully!', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
                if (modal) modal.hide();

                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(response.message || 'Upload failed');
            }
        } catch (error) {
            console.error('Upload error:', error);
            progressBar.classList.remove('bg-primary');
            progressBar.classList.add('bg-danger');
            progressBar.textContent = 'Upload Failed';
            statusMessage.textContent = 'Upload failed: ' + error.message;
            showAlert('Failed to upload document: ' + error.message, 'danger');

            setTimeout(() => {
                resetUploadState(uploadBtn, cancelBtn, originalText, progressContainer, statusContainer);
            }, 3000);
        }
    });

    // Handle errors
    xhr.addEventListener('error', function() {
        console.error('Upload network error');
        progressBar.classList.remove('bg-primary');
        progressBar.classList.add('bg-danger');
        progressBar.textContent = 'Network Error';
        statusMessage.textContent = 'Network error during upload';
        showAlert('Network error during upload. Please check your connection and try again.', 'danger');
        resetUploadState(uploadBtn, cancelBtn, originalText, progressContainer, statusContainer);
    });

    // Handle timeout
    xhr.addEventListener('timeout', function() {
        console.error('Upload timeout');
        progressBar.classList.remove('bg-primary');
        progressBar.classList.add('bg-danger');
        progressBar.textContent = 'Upload Timeout';
        statusMessage.textContent = 'Upload timed out';
        showAlert('Upload timed out. Please try again with a stable connection.', 'danger');
        resetUploadState(uploadBtn, cancelBtn, originalText, progressContainer, statusContainer);
    });

    // Set timeout for large files (10 minutes)
    xhr.timeout = 600000;

    // Send request
    console.log('Uploading to:', `/admin/pbc-requests/items/${itemId}/upload`);
    xhr.open('POST', `/admin/pbc-requests/items/${itemId}/upload`);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.send(formData);
}

// Helper function to reset upload state
function resetUploadState(uploadBtn, cancelBtn, originalText, progressContainer, statusContainer) {
    if (uploadBtn) {
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
    }
    if (cancelBtn) cancelBtn.disabled = false;
    if (progressContainer) progressContainer.style.display = 'none';
    if (statusContainer) statusContainer.style.display = 'none';

    const progressBar = progressContainer?.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.classList.remove('bg-danger', 'bg-success');
        progressBar.classList.add('bg-primary');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
    }
}

// Reject document function
function rejectDocument() {
    console.log('=== REJECT DOCUMENT DEBUG ===');

    const form = document.getElementById('rejectForm');
    const itemId = document.getElementById('rejectItemId').value;
    const requestId = document.getElementById('rejectRequestId').value;

    console.log('Form found:', !!form);
    console.log('Item ID:', itemId);
    console.log('Request ID:', requestId);

    if (!form || !itemId || !requestId) {
        console.error('Missing form data:', {form: !!form, itemId, requestId});
        showAlert('Reject form data missing', 'danger');
        return;
    }

    // Get client and project IDs from URL
    const urlParts = window.location.pathname.split('/').filter(part => part.length > 0);
    console.log('URL Parts:', urlParts);

    const clientIndex = urlParts.indexOf('clients');
    const projectIndex = urlParts.indexOf('projects');

    if (clientIndex === -1 || projectIndex === -1 ||
        clientIndex + 1 >= urlParts.length ||
        projectIndex + 1 >= urlParts.length) {
        console.error('Cannot find client/project in URL:', urlParts);
        showAlert('Error determining project context. Please refresh the page.', 'danger');
        return;
    }

    const clientId = urlParts[clientIndex + 1];
    const projectId = urlParts[projectIndex + 1];

    console.log('Extracted IDs - Client:', clientId, 'Project:', projectId);

    // Construct the exact route that matches web.php
    const rejectUrl = `/admin/clients/${clientId}/projects/${projectId}/pbc-requests/${requestId}/items/${itemId}/reject`;
    console.log('Constructed reject URL:', rejectUrl);

    const formData = new FormData(form);
    const rejectBtn = document.querySelector('#rejectModal .btn-danger');

    if (!rejectBtn) {
        console.error('Reject button not found');
        return;
    }

    const originalText = rejectBtn.innerHTML;
    rejectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
    rejectBtn.disabled = true;

    // Get CSRF token
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        console.error('No CSRF token found');
        rejectBtn.innerHTML = originalText;
        rejectBtn.disabled = false;
        return;
    }

    console.log('CSRF token found:', csrfToken.substring(0, 10) + '...');
    console.log('Making fetch request to:', rejectUrl);

    fetch(rejectUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));

        return response.text().then(text => {
            console.log('Raw response body:', text);

            if (!response.ok) {
                console.error('Response not OK:', response.status, response.statusText);

                let errorMessage = `HTTP error! status: ${response.status}`;
                try {
                    const errorData = JSON.parse(text);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    errorMessage = text || errorMessage;
                }

                throw new Error(errorMessage);
            }

            try {
                const jsonData = JSON.parse(text);
                console.log('Parsed JSON response:', jsonData);
                return jsonData;
            } catch (parseError) {
                console.error('Failed to parse JSON:', parseError);
                console.error('Response was:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        console.log('Success response:', data);

        if (data.success) {
            showAlert('Document rejected successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
            if (modal) modal.hide();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            console.error('Server returned error:', data);
            showAlert('Failed to reject document: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('Error rejecting document: ' + error.message, 'danger');
    })
    .finally(() => {
        rejectBtn.innerHTML = originalText;
        rejectBtn.disabled = false;
    });
}

// View document function
function viewDocument(documentId) {
    console.log('Viewing document:', documentId);
    window.open(`/documents/${documentId}/download`, '_blank');
}

// Approve item function
function approveItem(itemId) {
    console.log('Approving item:', itemId);

    if (!confirm('Approve this item?')) {
        return;
    }

    const approveBtn = event.target;
    const originalText = approveBtn.innerHTML;
    approveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
    approveBtn.disabled = true;

    // Get CSRF token
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        approveBtn.innerHTML = originalText;
        approveBtn.disabled = false;
        return;
    }

    console.log('Approving at:', `/admin/pbc-requests/items/${itemId}/approve`);

    fetch(`/admin/pbc-requests/items/${itemId}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => {
        console.log('Approve response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Approve response:', data);

        if (data.success) {
            showAlert('Item approved successfully!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('Failed to approve item: ' + (data.message || 'Unknown error'), 'danger');
            approveBtn.innerHTML = originalText;
            approveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Approve error:', error);
        showAlert('Error approving item. Please try again.', 'danger');
        approveBtn.innerHTML = originalText;
        approveBtn.disabled = false;
    });
}

// Send reminder function
function sendReminder(itemId) {
    console.log('Sending reminder for item:', itemId);

    if (!confirm('Send reminder to client for this item?')) {
        return;
    }

    const reminderBtn = event.target;
    const originalText = reminderBtn.innerHTML;
    reminderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    reminderBtn.disabled = true;

    // Get CSRF token
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        reminderBtn.innerHTML = originalText;
        reminderBtn.disabled = false;
        return;
    }

    fetch(`/admin/reminders/send`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            pbc_request_item_id: itemId,
            reminder_type: 'standard'
        })
    })
    .then(response => {
        console.log('Reminder response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Reminder response:', data);

        if (data.success) {
            showAlert('Reminder sent successfully!', 'success');
            reminderBtn.innerHTML = '<i class="fas fa-check"></i> Sent!';
            reminderBtn.classList.add('btn-success');
            reminderBtn.classList.remove('btn-danger');

            setTimeout(() => {
                reminderBtn.innerHTML = originalText;
                reminderBtn.classList.remove('btn-success');
                reminderBtn.classList.add('btn-danger');
                reminderBtn.disabled = false;
            }, 3000);
        } else {
            showAlert('Failed to send reminder: ' + (data.message || 'Unknown error'), 'danger');
            reminderBtn.innerHTML = originalText;
            reminderBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Reminder error:', error);
        showAlert('Error sending reminder. Please try again.', 'danger');
        reminderBtn.innerHTML = originalText;
        reminderBtn.disabled = false;
    });
}

// Enhanced alert function
function showAlert(message, type) {
    console.log(`Alert [${type}]:`, message);

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
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

    // Manual close functionality
    const closeBtn = alertDiv.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                bsAlert.close();
            } catch (e) {
                alertDiv.remove();
            }
        });
    }
}

// Modal reset functionality
document.getElementById('uploadModal').addEventListener('hidden.bs.modal', function () {
    const form = document.getElementById('uploadForm');
    const previewArea = document.getElementById('filePreviewArea');
    const progressContainer = document.getElementById('uploadProgress');
    const statusContainer = document.getElementById('uploadStatus');
    const uploadButton = document.getElementById('uploadButton');

    if (form) form.reset();
    if (previewArea) previewArea.style.display = 'none';
    if (progressContainer) progressContainer.style.display = 'none';
    if (statusContainer) statusContainer.style.display = 'none';

    const progressBar = progressContainer?.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.classList.remove('bg-danger', 'bg-success');
        progressBar.classList.add('bg-primary');
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
    }

    if (uploadButton) {
        uploadButton.disabled = false;
        uploadButton.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Upload Document';
    }
});

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
