@extends('layouts.app')
@section('title', 'PBC Request List')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">PBC Request List</h1>
        <p class="text-muted mb-0">Rule: Pending for uploading and review only</p>
    </div>
</div>

<!-- Advanced Search and Filter Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="mb-0">
            <div class="row g-3">
                <!-- Search Request -->
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">Search Request</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by title, description, job ID..."
                           value="{{ request('search') }}">
                </div>

                <!-- Status Filter - Updated to exclude completed/approved -->
                <div class="col-md-2">
                    <label class="form-label small text-muted fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Active</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Uploaded</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>

                <!-- All Clients Filter -->
                <div class="col-md-4">
                    <label class="form-label small text-muted fw-bold">All Clients</label>
                    <select name="client_id" class="form-select">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->company_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Button -->
                <div class="col-md-2">
                    <label class="form-label small text-muted fw-bold">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-outline-secondary" title="Reset">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Category</th>
                        <th class="py-3">Client Name</th>
                        <th class="py-3">Request Description</th>
                        <th class="py-3">Requestor</th>
                        <th class="py-3">Date Requested</th>
                        <th class="py-3">Assigned to</th>
                        <th class="py-3">Due Date</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Actions</th>
                        <th class="py-3">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        @foreach($request->items as $item)
                        @php
                            // Get the current status for this item
                            $currentStatus = $item->getCurrentStatus();
                            $isOverdue = $request->due_date && $request->due_date->isPast() && $currentStatus !== 'approved';
                            $displayStatus = $isOverdue ? 'overdue' : $currentStatus;

                            // FILTER OUT APPROVED ITEMS - Skip this iteration if approved
                            if ($currentStatus === 'approved') {
                                continue;
                            }

                            // Get status colors
                            $statusColors = [
                                'pending' => 'secondary',
                                'uploaded' => 'warning',
                                'rejected' => 'danger',
                                'overdue' => 'danger'
                            ];
                            $statusColor = $statusColors[$displayStatus] ?? 'secondary';

                            // Get category info
                            $categoryInfo = [
                                'CF' => ['label' => 'CF', 'class' => 'primary', 'full' => 'Current File'],
                                'PF' => ['label' => 'PF', 'class' => 'secondary', 'full' => 'Permanent File']
                            ];
                            $catInfo = $categoryInfo[$item->category] ?? ['label' => 'CF', 'class' => 'primary', 'full' => 'Current File'];
                        @endphp
                        <tr>
                            <!-- Category Column -->
                            <td class="px-4 py-3">
                                <span class="badge bg-{{ $catInfo['class'] }}" title="{{ $catInfo['full'] }}">
                                    {{ $catInfo['label'] }}
                                </span>
                            </td>

                            <!-- Client Name -->
                            <td class="py-3">
                                <div>
                                    <div class="fw-semibold">{{ $request->client->company_name }}</div>
                                    <small class="text-muted">{{ $request->project->job_id ?? 'No Job ID' }}</small>
                                </div>
                            </td>

                            <!-- Request Description -->
                            <td class="py-3">
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $item->particulars }}">
                                    {{ Str::limit($item->particulars, 50) }}
                                </div>
                                <small class="text-muted">{{ $request->project->engagement_type ?? 'audit' }}</small>
                            </td>

                            <!-- Requestor -->
                            <td class="py-3">
                                <div class="fw-semibold">{{ $request->creator->name ?? 'MNGR 1' }}</div>
                                <small class="text-muted">{{ $request->creator->getRoleDisplayName() ?? 'Manager' }}</small>
                            </td>

                            <!-- Date Requested -->
                            <td class="py-3">
                                <div class="fw-semibold">{{ $item->date_requested ? $item->date_requested->format('d/m/Y') : $request->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $request->created_at->format('H:i') }}</small>
                            </td>

                            <!-- Assigned to -->
                            <td class="py-3">
                                <div>
                                    @if($request->client->contact_person)
                                        <div class="fw-semibold">{{ $request->client->contact_person }}</div>
                                        <small class="text-muted">Client Contact</small>
                                    @else
                                        <div class="fw-semibold">Client Staff 1</div>
                                        <small class="text-muted">Default Contact</small>
                                    @endif
                                </div>
                            </td>

                            <!-- Due Date -->
                            <td class="py-3">
                                @if($request->due_date)
                                    <div class="fw-semibold {{ $isOverdue ? 'text-danger' : '' }}">
                                        {{ $request->due_date->format('d/m/Y') }}
                                    </div>
                                    @if($isOverdue)
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            {{ $request->due_date->diffForHumans() }}
                                        </small>
                                    @else
                                        <small class="text-muted">{{ $request->due_date->diffForHumans() }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">No due date</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3">
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ ucfirst($displayStatus) }}
                                </span>
                                @if($item->documents->count() > 0)
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            {{ $item->documents->count() }} file(s)
                                        </small>
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3">
                                <div class="btn-group" role="group">
                                    @if($currentStatus === 'pending')
                                        <!-- Upload Button - Available for all user levels -->
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                onclick="openUploadModal({{ $item->id }})"
                                                title="Upload Document">
                                            Upload
                                        </button>
                                    @elseif($currentStatus === 'uploaded')
                                        <!-- View/Download Button -->
                                        @if($item->documents->where('status', 'uploaded')->first())
                                            <button type="button"
                                                    class="btn btn-sm btn-info"
                                                    onclick="viewDocument({{ $item->documents->where('status', 'uploaded')->first()->id }})"
                                                    title="View Document">
                                                View/Download
                                            </button>
                                        @endif

                                        <!-- Approve Button -->
                                        <button class="btn btn-sm btn-success" onclick="approveItem({{ $item->id }})" title="Approve">
                                            Approve
                                        </button>

                                        <!-- Reject Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                onclick="openRejectModal({{ $item->id }})"
                                                title="Reject with Note">
                                            Reject
                                        </button>
                                    @elseif($currentStatus === 'rejected')
                                        <!-- Upload Button - Allow re-upload -->
                                        <button type="button"
                                                class="btn btn-sm btn-warning"
                                                onclick="openUploadModal({{ $item->id }})"
                                                title="Re-upload Document">
                                            Re-upload
                                        </button>
                                    @elseif($displayStatus === 'overdue')
                                        <!-- Send Reminder Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                onclick="sendReminder({{ $request->id }})"
                                                title="Send Reminder">
                                            Send Reminder
                                        </button>

                                        <!-- Upload Button - Available for staff -->
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                onclick="openUploadModal({{ $item->id }})"
                                                title="Upload Document">
                                            Upload
                                        </button>
                                    @else
                                        <!-- Default Upload Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                onclick="openUploadModal({{ $item->id }})"
                                                title="Upload Document">
                                            Upload
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Note column -->
                            <td class="py-3">
                                <div class="text-truncate" style="max-width: 150px;">
                                    @php
                                        // Get the latest rejected document's admin notes
                                        $rejectedDoc = $item->documents->where('status', 'rejected')->first();
                                    @endphp

                                    @if($currentStatus === 'rejected' && $rejectedDoc && $rejectedDoc->admin_notes)
                                        <span class="text-danger" title="{{ $rejectedDoc->admin_notes }}">
                                            {{ Str::limit($rejectedDoc->admin_notes, 30) }}
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
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h5>No Active PBC Requests Found</h5>
                                <p>No pending, uploaded, rejected, or overdue requests match your current filters.</p>
                                <div class="mt-3">
                                    <a href="{{ route('admin.pbc-requests.create') }}" class="btn btn-primary me-2">
                                        <i class="fas fa-plus"></i> Create Request
                                    </a>
                                    <a href="{{ route('admin.pbc-requests.import') }}" class="btn btn-success me-2">
                                        <i class="fas fa-upload"></i> Import Requests
                                    </a>
                                    <a href="{{ route('admin.document-archive.index') }}" class="btn btn-info">
                                        <i class="fas fa-archive"></i> View Archive
                                    </a>
                                </div>
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

<!-- Pagination -->
@if($requests->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $requests->appends(request()->query())->links() }}
</div>
@endif

@endsection

@section('styles')
<style>
/* Table styling improvements */
.table {
    font-size: 0.875rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    font-size: 0.8rem;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f8f9fa;
    padding: 0.75rem 0.5rem;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Badge improvements */
.badge {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 0.25em 0.5em;
}

/* Button group styling */
.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
}

.card-body {
    padding: 1rem;
}

/* Search form styling */
.form-label.small {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.8rem;
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

/* Status color improvements */
.bg-primary { background-color: #007bff !important; }
.bg-secondary { background-color: #6c757d !important; }
.bg-success { background-color: #28a745 !important; }
.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-info { background-color: #17a2b8 !important; }

/* Text colors */
.text-danger { color: #dc3545 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }

/* Empty state styling */
.fa-inbox {
    color: #6c757d;
    opacity: 0.5;
}

/* Text truncation */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Statistics cards */
.card-body.py-3 {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

.card-body h4 {
    font-size: 1.5rem;
    font-weight: 600;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.75rem;
    }

    .btn-group .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    .text-truncate {
        max-width: 100px !important;
    }

    .card-body h4 {
        font-size: 1.25rem;
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

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-1px);
}

/* Form control improvements */
.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Icon alignments */
.fas {
    width: 14px;
    text-align: center;
}

/* Row striping for better readability */
.table tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.01);
}

/* Action button specific colors */
.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-outline-success:hover {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-outline-info:hover {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

/* Small text improvements */
small.text-muted {
    font-size: 0.75rem;
}

/* Status badge animation for pending */
.badge.bg-warning {
    animation: pulse-warning 2s infinite;
}

@keyframes pulse-warning {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}

/* Overdue indicator */
.text-danger .fas {
    animation: blink-danger 1s infinite;
}

@keyframes blink-danger {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.5; }
}
</style>
@endsection

@section('scripts')
<script>
// Global variables for upload system
let uploadLimits = null;

document.addEventListener('DOMContentLoaded', function() {
    // Load upload limits on page load
    loadUploadLimits();

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-refresh for pending/uploaded items every 3 minutes
    setInterval(function() {
        const pendingItems = document.querySelectorAll('.badge.bg-warning, .badge.bg-secondary');
        if (pendingItems.length > 0) {
            console.log('Checking for status updates...');
        }
    }, 180000); // 3 minutes

    // Enhanced search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
        });
    }

    // Button loading states
    document.querySelectorAll('.btn-group .btn').forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit' && !this.disabled) {
                this.classList.add('loading');
                this.disabled = true;

                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 3000);
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput?.focus();
        }

        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.value = '';
        }
    });

    // Add CSRF token if missing
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

// Open reject modal
function openRejectModal(itemId) {
    console.log('Opening reject modal for item:', itemId);

    const rejectItemId = document.getElementById('rejectItemId');
    const rejectForm = document.getElementById('rejectForm');

    if (!rejectItemId || !rejectForm) {
        console.error('Reject modal elements not found');
        showAlert('Reject form not found. Please refresh the page.', 'danger');
        return;
    }

    rejectForm.reset();
    rejectItemId.value = itemId;

    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    rejectModal.show();
}

// Reject document
function rejectDocument() {
    console.log('Starting document rejection...');

    const form = document.getElementById('rejectForm');
    const itemId = document.getElementById('rejectItemId').value;

    if (!form || !itemId) {
        showAlert('Reject form data missing', 'danger');
        return;
    }

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
        rejectBtn.innerHTML = originalText;
        rejectBtn.disabled = false;
        return;
    }

    // Use the global reject route
    const rejectUrl = `/admin/pbc-requests/items/${itemId}/reject`;
    console.log('Rejecting at:', rejectUrl);

    fetch(rejectUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => {
        console.log('Reject response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Reject response:', data);

        if (data.success) {
            showAlert('Document rejected successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
            if (modal) modal.hide();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('Failed to reject document: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Reject error:', error);
        showAlert('Error rejecting document. Please try again.', 'danger');
    })
    .finally(() => {
        rejectBtn.innerHTML = originalText;
        rejectBtn.disabled = false;
    });
}

// Approve item function for global index
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

// View document
function viewDocument(documentId) {
    console.log('Viewing document:', documentId);
    window.open(`/documents/${documentId}/download`, '_blank');
}

// Send reminder function - FOR GLOBAL INDEX (takes requestId, not itemId)
function sendReminder(requestId) {
    console.log('Sending reminder for request:', requestId);

    if (!confirm('Send reminder to client for this request?')) {
        return;
    }

    const button = event.target;
    const originalText = button.innerHTML;

    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    button.disabled = true;

    // Get CSRF token
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        button.innerHTML = originalText;
        button.disabled = false;
        return;
    }

    fetch('/admin/pbc-requests/reminders/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            pbc_request_id: requestId,
            reminder_type: 'urgent',
            custom_message: 'Your document submission is overdue. Please upload as soon as possible.'
        })
    })
    .then(response => {
        console.log('Reminder response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Reminder response:', data);

        if (data.success) {
            button.innerHTML = '<i class="fas fa-check"></i> Sent!';
            button.classList.remove('btn-danger');
            button.classList.add('btn-success');

            showAlert('Reminder sent successfully!', 'success');

            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-danger');
                button.disabled = false;
            }, 3000);
        } else {
            button.innerHTML = originalText;
            button.disabled = false;
            showAlert('Failed to send reminder: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Reminder error:', error);
        button.innerHTML = originalText;
        button.disabled = false;
        showAlert('Error sending reminder. Please try again.', 'danger');
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

// Enhanced alert function
function showAlert(message, type) {
    console.log(`Alert [${type}]:`, message);

    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert.position-fixed');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';

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

// Table sorting function (basic placeholder)
function sortTable(columnIndex) {
    console.log('Sort column:', columnIndex);
    showAlert('Table sorting feature coming soon!', 'info');
}

// Export function (placeholder)
function exportRequests() {
    console.log('Export functionality not implemented yet');
    showAlert('Export feature coming soon!', 'info');
}

// Debug function
function debugPage() {
    console.log('=== DEBUG INFO ===');
    console.log('CSRF Token:', getCsrfToken());
    console.log('Upload Modal:', document.getElementById('uploadModal'));
    console.log('Reject Modal:', document.getElementById('rejectModal'));
    console.log('Upload Limits:', uploadLimits);
    console.log('Forms:', {
        upload: document.getElementById('uploadForm'),
        reject: document.getElementById('rejectForm')
    });
}
</script>
@endsection
