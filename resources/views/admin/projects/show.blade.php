@extends('layouts.app')
@section('title', 'Job Details - ' . $project->job_id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $project->engagement_name ?? $project->name }}</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">{{ $project->client->company_name ?? 'No Client' }}</span>
            <span class="badge bg-primary">{{ ucfirst($project->engagement_type) }}</span>
            <span class="text-muted">Engagement Period: {{ $project->engagement_period_start ? $project->engagement_period_start->format('Y') : 'N/A' }}</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-plus"></i> Import Request
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendRequestModal">
            <i class="fas fa-paper-plane"></i> Send Request
        </button>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Job Information Card -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Job ID</h6>
                        <h4 class="text-primary">{{ $project->job_id ?? 'No Job ID' }}</h4>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Partner</h6>
                        <div class="fw-bold">{{ $project->engagementPartner->name ?? 'EYM' }}</div>
                        <small class="text-muted">Engagement Partner</small>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Staff</h6>
                        <div class="d-flex flex-column gap-1">
                            <div class="fw-bold">STAFF 1</div>
                            <div class="fw-bold">STAFF 2</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Manager</h6>
                        <div class="fw-bold">{{ $project->manager->name ?? 'MNGR 1' }}</div>
                        <small class="text-muted">Project Manager</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">

    </div>
</div>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="mb-0">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small text-muted fw-bold">Search:</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search requests, documents, or items..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- PBC Requests Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list-alt text-primary"></i> PBC Requests for {{ $project->engagement_name ?? $project->name }}
        </h5>
        <span class="badge bg-info">{{ $project->pbcRequests->count() }} Total Requests</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">+/-</th>
                        <th class="py-3">Category</th>
                        <th class="py-3">Request Description</th>
                        <th class="py-3">Requestor</th>
                        <th class="py-3">Date Requested</th>
                        <th class="py-3">Assigned to</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Actions</th>
                        <th class="py-3">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->pbcRequests as $request)
                        @foreach($request->items as $item)
                        @php
                            $currentStatus = $item->getCurrentStatus();
                            $isOverdue = $request->due_date && $request->due_date->isPast() && $currentStatus !== 'approved';
                            $displayStatus = $isOverdue ? 'overdue' : $currentStatus;

                            $statusColors = [
                                'pending' => 'secondary',
                                'uploaded' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'overdue' => 'danger'
                            ];
                            $statusColor = $statusColors[$displayStatus] ?? 'secondary';

                            $categoryInfo = [
                                'CF' => ['label' => 'CF', 'class' => 'primary'],
                                'PF' => ['label' => 'PF', 'class' => 'secondary']
                            ];
                            $catInfo = $categoryInfo[$item->category] ?? ['label' => 'CF', 'class' => 'primary'];
                        @endphp
                        <tr class="expandable-row" data-request-id="{{ $request->id }}">
                            <!-- Expand/Collapse Button -->
                            <td class="px-4 py-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary expand-btn"
                                        onclick="toggleRequestDetails({{ $request->id }})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>

                            <!-- Category -->
                            <td class="py-3">
                                <span class="badge bg-{{ $catInfo['class'] }}">
                                    {{ $catInfo['label'] }}
                                </span>
                            </td>

                            <!-- Request Description -->
                            <td class="py-3">
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $item->particulars }}">
                                    {{ Str::limit($item->particulars, 50) }}
                                </div>
                                <small class="text-muted">{{ $request->title }}</small>
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
                                <div class="fw-semibold">{{ $request->client->contact_person ?? 'Client Staff 1' }}</div>
                                <small class="text-muted">{{ $request->client->company_name }}</small>
                            </td>

                            <!-- Status -->
                            <td class="py-3">
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ ucfirst($displayStatus) }}
                                </span>
                                @if($item->documents->count() > 0)
                                    <div class="mt-1">
                                        <small class="text-muted">{{ $item->documents->count() }} file(s)</small>
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3">
                                <div class="btn-group" role="group">
                                    @if($currentStatus === 'uploaded')
                                        <!-- View/Download/Approve/Reject -->
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                View/Download/Approve/Reject
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($item->documents->where('status', 'uploaded') as $document)
                                                <li><h6 class="dropdown-header">{{ Str::limit($document->original_filename, 20) }}</h6></li>
                                                <li><a class="dropdown-item" href="{{ route('documents.download', $document) }}">
                                                    <i class="fas fa-eye"></i> View/Download
                                                </a></li>
                                                <li>
                                                    <form method="POST" action="{{ route('admin.pbc-requests.review-item', [$request, $item]) }}" class="d-inline">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="document_id" value="{{ $document->id }}">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger"
                                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $document->id }}">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @elseif($currentStatus === 'approved')
                                        <!-- View/Download -->
                                        @if($item->documents->where('status', 'approved')->first())
                                            <a href="{{ route('documents.download', $item->documents->where('status', 'approved')->first()) }}"
                                               class="btn btn-sm btn-warning">
                                                View/Download
                                            </a>
                                        @endif
                                    @elseif($displayStatus === 'overdue')
                                        <!-- Send Reminder -->
                                        <button type="button" class="btn btn-sm btn-danger" onclick="sendReminder({{ $request->id }})">
                                            Send Reminder
                                        </button>
                                    @else
                                        <!-- Upload -->
                                        <a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">
                                            Upload
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <!-- Note -->
                            <td class="py-3">
                                <div class="text-truncate" style="max-width: 150px;">
                                    @if($item->remarks)
                                        {{ $item->remarks }}
                                    @elseif($currentStatus === 'rejected' && $item->documents->where('status', 'rejected')->first())
                                        <span class="text-danger">{{ Str::limit($item->documents->where('status', 'rejected')->first()->admin_notes ?? 'Rejected', 30) }}</span>
                                    @else
                                        <span class="text-muted">Insert text</span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Expandable Details Row (Hidden by default) -->
                        <tr class="request-details d-none" id="details-{{ $request->id }}">
                            <td colspan="9" class="bg-light p-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Request Details</h6>
                                        <p><strong>Title:</strong> {{ $request->title }}</p>
                                        <p><strong>Description:</strong> {{ $request->description ?? 'No description' }}</p>
                                        <p><strong>Due Date:</strong> {{ $request->due_date ? $request->due_date->format('M d, Y') : 'No due date' }}</p>
                                        <p><strong>Created:</strong> {{ $request->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary">Documents & Files</h6>
                                        @if($item->documents->count() > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($item->documents as $document)
                                                <div class="list-group-item px-0 py-2">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-file-{{ $document->file_extension === 'pdf' ? 'pdf text-danger' : ($document->file_extension === 'xlsx' ? 'excel text-success' : 'alt') }}"></i>
                                                            <span class="ms-2">{{ Str::limit($document->original_filename, 30) }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-{{ $document->status === 'approved' ? 'success' : ($document->status === 'rejected' ? 'danger' : 'warning') }}">
                                                                {{ ucfirst($document->status) }}
                                                            </span>
                                                            <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-primary ms-2">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    @if($document->status === 'rejected' && $document->admin_notes)
                                                        <small class="text-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> {{ $document->admin_notes }}
                                                        </small>
                                                    @endif
                                                </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted">No documents uploaded yet.</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Rejection Modals for each document -->
                        @foreach($item->documents->where('status', 'uploaded') as $document)
                        <div class="modal fade" id="rejectModal{{ $document->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.pbc-requests.review-item', [$request, $item]) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="document_id" value="{{ $document->id }}">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Document</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>File:</strong> {{ $document->original_filename }}</p>
                                            <div class="mb-3">
                                                <label class="form-label">Reason for rejection <span class="text-danger">*</span></label>
                                                <textarea name="admin_notes" class="form-control" rows="3" required
                                                          placeholder="Explain why this document is being rejected..."></textarea>
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
                        @endforeach
                        @endforeach
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h5>No PBC Requests for this Job</h5>
                                <p>Start by creating or importing PBC requests for this project.</p>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#sendRequestModal">
                                        <i class="fas fa-plus"></i> Create Request
                                    </button>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                        <i class="fas fa-upload"></i> Import Requests
                                    </button>
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

<!-- Import Request Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Request for {{ $project->job_id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Import PBC requests from Excel file for this project:</p>
                <ul>
                    <li><strong>Project:</strong> {{ $project->engagement_name }}</li>
                    <li><strong>Client:</strong> {{ $project->client->company_name ?? 'N/A' }}</li>
                    <li><strong>Job ID:</strong> {{ $project->job_id }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('admin.pbc-requests.import') }}?project_id={{ $project->id }}" class="btn btn-success">
                    <i class="fas fa-upload"></i> Go to Import Page
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Send Request Modal -->
<div class="modal fade" id="sendRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Request for {{ $project->job_id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Create a new PBC request for this project:</p>
                <ul>
                    <li><strong>Project:</strong> {{ $project->engagement_name }}</li>
                    <li><strong>Client:</strong> {{ $project->client->company_name ?? 'N/A' }}</li>
                    <li><strong>Job ID:</strong> {{ $project->job_id }}</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('admin.pbc-requests.create') }}?project_id={{ $project->id }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create PBC Request
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Card enhancements */
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
}

/* Table styling */
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f8f9fa;
    font-size: 0.875rem;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Expand button styling */
.expand-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.expand-btn.expanded .fa-plus:before {
    content: "\f068"; /* fa-minus */
}

/* Request details row */
.request-details {
    border-top: 3px solid #007bff;
}

.request-details td {
    background-color: #f8f9fa !important;
}

/* Badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Button group styling */
.btn-group .dropdown-toggle {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.dropdown-menu {
    font-size: 0.875rem;
}

/* Authority table styling */
.table-sm th,
.table-sm td {
    padding: 0.25rem !important;
    font-size: 0.7rem !important;
    text-align: center;
}

/* Action buttons specific colors */
.btn-primary { background-color: #007bff; }
.btn-success { background-color: #28a745; }
.btn-warning { background-color: #ffc107; color: #000; }
.btn-danger { background-color: #dc3545; }

/* File icons */
.fa-file-pdf { color: #dc3545; }
.fa-file-excel { color: #28a745; }
.fa-file-word { color: #007bff; }
.fa-file-alt { color: #6c757d; }

/* Text truncation */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

.expandable-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Status indicators */
.bg-success { background-color: #28a745 !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-secondary { background-color: #6c757d !important; }
.bg-primary { background-color: #007bff !important; }
.bg-info { background-color: #17a2b8 !important; }

/* Modal enhancements */
.modal-content {
    border-radius: 0.5rem;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
}

/* List group styling */
.list-group-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

.list-group-item:last-child {
    border-bottom: none;
}

/* Authority table responsive */
@media (max-width: 992px) {
    .table-responsive table {
        font-size: 0.6rem !important;
    }

    .table-responsive th,
    .table-responsive td {
        padding: 0.15rem !important;
    }
}

/* Empty state styling */
.fa-inbox {
    color: #6c757d;
    opacity: 0.5;
}

/* Form control improvements */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Dropdown menu improvements */
.dropdown-header {
    font-size: 0.75rem;
    font-weight: 600;
    color: #495057;
}

.dropdown-item {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Animation for expand/collapse */
.request-details {
    transition: all 0.3s ease;
}

.expand-btn {
    transition: transform 0.2s ease;
}

.expand-btn.expanded {
    transform: rotate(45deg);
}

/* Job information card styling */
.card-body h4.text-primary {
    font-weight: 600;
    margin-bottom: 0;
}

.card-body h6.text-muted {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Quick actions card */
.card.border-primary {
    border-width: 2px !important;
}

.card-header.bg-primary {
    background-color: #007bff !important;
}

/* Small badges in action buttons */
.btn .badge {
    font-size: 0.6rem;
    margin-left: 0.25rem;
}

/* Search form styling */
.form-label.small {
    font-weight: 600;
    margin-bottom: 0.25rem;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-refresh for status updates every 3 minutes
    setInterval(function() {
        const uploadedBadges = document.querySelectorAll('.badge.bg-warning');
        if (uploadedBadges.length > 0) {
            console.log('Checking for document status updates...');
            // You can implement partial refresh here
        }
    }, 180000); // 3 minutes

    // Search functionality enhancement
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Implement live search highlighting
                highlightSearchResults(this.value);
            }, 500);
        });
    }

    // Button loading states
    document.querySelectorAll('.btn').forEach(button => {
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
        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput?.focus();
        }

        // Escape to clear search
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.value = '';
            clearSearchHighlights();
        }
    });
});

// Toggle request details function
function toggleRequestDetails(requestId) {
    const detailsRow = document.getElementById(`details-${requestId}`);
    const expandBtn = document.querySelector(`[onclick="toggleRequestDetails(${requestId})"]`);
    const icon = expandBtn.querySelector('i');

    if (detailsRow.classList.contains('d-none')) {
        // Show details
        detailsRow.classList.remove('d-none');
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-minus');
        expandBtn.classList.add('expanded');
        expandBtn.title = 'Hide details';
    } else {
        // Hide details
        detailsRow.classList.add('d-none');
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
        expandBtn.classList.remove('expanded');
        expandBtn.title = 'Show details';
    }
}

// Send reminder function
function sendReminder(requestId) {
    if (confirm('Send urgent reminder to client for this overdue request?')) {
        const button = event.target;
        const originalText = button.innerHTML;

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        button.disabled = true;

        fetch('/admin/reminders/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                pbc_request_id: requestId,
                reminder_type: 'urgent',
                custom_message: 'URGENT: Your document submission is overdue. Please upload the required documents immediately to avoid delays in the audit process.'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> Reminder Sent!';
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');

                showAlert('Urgent reminder sent successfully!', 'success');

                // Reset button after 5 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-danger');
                    button.disabled = false;
                }, 5000);
            } else {
                button.innerHTML = originalText;
                button.disabled = false;
                showAlert('Failed to send reminder: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            showAlert('Error sending reminder. Please try again.', 'danger');
        });
    }
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Highlight search results
function highlightSearchResults(searchTerm) {
    clearSearchHighlights();

    if (!searchTerm.trim()) return;

    const rows = document.querySelectorAll('.table tbody tr:not(.request-details)');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let hasMatch = false;

        cells.forEach(cell => {
            const text = cell.textContent.toLowerCase();
            if (text.includes(searchTerm.toLowerCase())) {
                hasMatch = true;
                // Highlight the matching text
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                cell.innerHTML = cell.innerHTML.replace(regex, '<mark>$1</mark>');
            }
        });

        // Show/hide rows based on search match
        if (hasMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Clear search highlights
function clearSearchHighlights() {
    const marks = document.querySelectorAll('mark');
    marks.forEach(mark => {
        mark.outerHTML = mark.innerHTML;
    });

    const rows = document.querySelectorAll('.table tbody tr:not(.request-details)');
    rows.forEach(row => {
        row.style.display = '';
    });
}

// Expand all/Collapse all functionality
function expandAll() {
    const expandBtns = document.querySelectorAll('.expand-btn:not(.expanded)');
    expandBtns.forEach(btn => {
        btn.click();
    });
}

function collapseAll() {
    const expandBtns = document.querySelectorAll('.expand-btn.expanded');
    expandBtns.forEach(btn => {
        btn.click();
    });
}

// Export functionality (placeholder)
function exportJobData() {
    console.log('Export job data functionality not implemented yet');
    showAlert('Export feature coming soon!', 'info');
}

// Filter by status
function filterByStatus(status) {
    const rows = document.querySelectorAll('.table tbody tr:not(.request-details)');

    rows.forEach(row => {
        const statusBadge = row.querySelector('.badge');
        const badgeText = statusBadge ? statusBadge.textContent.toLowerCase() : '';

        if (status === 'all' || badgeText.includes(status.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Quick actions
function quickApproveAll() {
    if (confirm('Approve all uploaded documents for this job? This action cannot be undone.')) {
        // Implementation for bulk approve
        showAlert('Bulk approve functionality not implemented yet', 'warning');
    }
}

function quickRejectAll() {
    if (confirm('Reject all uploaded documents for this job? You will need to provide rejection reasons.')) {
        // Implementation for bulk reject
        showAlert('Bulk reject functionality not implemented yet', 'warning');
    }
}

// File download tracking
document.addEventListener('click', function(e) {
    if (e.target.closest('a[href*="download"]')) {
        const filename = e.target.textContent || 'document';
        console.log('Document download initiated:', filename);
        // Analytics tracking can be added here
    }
});

// Auto-save expanded state
function saveExpandedState() {
    const expandedRows = Array.from(document.querySelectorAll('.expand-btn.expanded'))
        .map(btn => btn.getAttribute('onclick').match(/\d+/)[0]);

    localStorage.setItem('job_expanded_rows', JSON.stringify(expandedRows));
}

function loadExpandedState() {
    const expandedRows = JSON.parse(localStorage.getItem('job_expanded_rows') || '[]');

    expandedRows.forEach(requestId => {
        const btn = document.querySelector(`[onclick="toggleRequestDetails(${requestId})"]`);
        if (btn && !btn.classList.contains('expanded')) {
            btn.click();
        }
    });
}

// Save state on expand/collapse
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('expand-btn') || e.target.closest('.expand-btn')) {
        setTimeout(saveExpandedState, 100);
    }
});

// Load state on page load
window.addEventListener('load', function() {
    loadExpandedState();
});
</script>
@endsection
