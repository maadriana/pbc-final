@extends('layouts.app')
@section('title', 'PBC Request List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">PBC Request List</h1>
        <p class="text-muted mb-0">Rule: Pending for uploading</p>
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

                <!-- All Status Filter -->
                <div class="col-md-2">
                    <label class="form-label small text-muted fw-bold">All Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Uploaded</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
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

                            // Get status colors
                            $statusColors = [
                                'pending' => 'secondary',
                                'uploaded' => 'warning',
                                'approved' => 'success',
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
                                    @if($currentStatus === 'uploaded')
                                        <!-- Approve Action -->
                                        <form method="POST" action="{{ route('admin.pbc-requests.review-item', [$request, $item]) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="action" value="approve">
                                            @if($item->documents->where('status', 'uploaded')->first())
                                                <input type="hidden" name="document_id" value="{{ $item->documents->where('status', 'uploaded')->first()->id }}">
                                            @endif
                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                Approve
                                            </button>
                                        </form>
                                    @elseif($currentStatus === 'approved')
                                        <!-- View/Download Action -->
                                        @if($item->documents->where('status', 'approved')->first())
                                            <a href="{{ route('documents.download', $item->documents->where('status', 'approved')->first()) }}"
                                               class="btn btn-sm btn-outline-success" title="View/Download">
                                                View/Download
                                            </a>
                                        @endif
                                    @elseif($currentStatus === 'overdue' || $displayStatus === 'overdue')
                                        <!-- Send Reminder Action -->
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                onclick="sendReminder({{ $request->id }})"
                                                title="Send Reminder">
                                            Send Reminder
                                        </button>
                                    @else
                                        <!-- Default Upload Action -->
                                        <a href="{{ route('admin.pbc-requests.show', $request) }}"
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            Upload
                                        </a>
                                    @endif

                                    <!-- View Request Details -->
                                    <a href="{{ route('admin.pbc-requests.show', $request) }}"
                                       class="btn btn-sm btn-outline-info" title="View Request">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
                        @endforeach
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h5>No PBC Requests Found</h5>
                                <p>No requests match your current filters.</p>
                                <div class="mt-3">
                                    <a href="{{ route('admin.pbc-requests.create') }}" class="btn btn-primary me-2">
                                        <i class="fas fa-plus"></i> Create Request
                                    </a>
                                    <a href="{{ route('admin.pbc-requests.import') }}" class="btn btn-success">
                                        <i class="fas fa-upload"></i> Import Requests
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
document.addEventListener('DOMContentLoaded', function() {
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
            // You can implement partial refresh here
        }
    }, 180000); // 3 minutes

    // Enhanced search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            // Add search suggestions or live search here
        });
    }

    // Button loading states
    document.querySelectorAll('.btn-group .btn').forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit' && !this.disabled) {
                this.classList.add('loading');
                this.disabled = true;

                // Re-enable after 3 seconds if form hasn't redirected
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 3000);
            }
        });
    });

    // Row click enhancement (excluding buttons)
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-group') && !e.target.closest('button')) {
                const viewBtn = this.querySelector('.btn-outline-info');
                if (viewBtn) {
                    window.location.href = viewBtn.href;
                }
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
        }
    });
});

// Send reminder function
function sendReminder(requestId) {
    if (confirm('Send reminder to client for this request?')) {
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
                custom_message: 'Your document submission is overdue. Please upload as soon as possible.'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> Sent!';
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');

                // Show success message
                showAlert('Reminder sent successfully!', 'success');

                // Reset button after 3 seconds
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
            console.error('Error:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            showAlert('Error sending reminder. Please try again.', 'danger');
        });
    }
}

// Show alert function
function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Add to page
    document.body.appendChild(alertDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Table sorting function (basic)
function sortTable(columnIndex) {
    // Add table sorting logic here if needed
    console.log('Sort column:', columnIndex);
}

// Export function (placeholder)
function exportRequests() {
    console.log('Export functionality not implemented yet');
    showAlert('Export feature coming soon!', 'info');
}
</script>
@endsection
