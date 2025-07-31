@extends('layouts.app')
@section('title', $project->engagement_name . ' - PBC Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $project->engagement_name ?? 'Statutory Audit for YE122024' }}</h1>
        <p class="text-muted mb-0">
            {{ $client->company_name }} |
            Job ID: {{ $project->job_id ?? '1-01-001' }} |
            Partner: {{ $project->engagementPartner->name ?? 'EYM' }} |
            Manager: {{ $project->manager->name ?? 'MNGR 1' }} |
            Staff: {{ $project->associate1()?->user->name ?? 'STAFF 1' }}, {{ $project->associate2()?->user->name ?? 'STAFF 2' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.clients.projects.pbc-requests.import', [$client, $project]) }}" class="btn btn-info">
            <i class="fas fa-upload"></i> Import Request
        </a>
        <a href="{{ route('admin.clients.projects.pbc-requests.create', [$client, $project]) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Send Request
        </a>
        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Client
        </a>
    </div>
</div>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="mb-0">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">Search:</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search requests..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">All Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Uploaded</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Stats Cards -->
@if(isset($stats))
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white border-0">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['total_requests'] }}</h4>
                <small>Total Requests</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark border-0">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['pending'] }}</h4>
                <small>Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white border-0">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['in_progress'] }}</h4>
                <small>In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white border-0">
            <div class="card-body text-center">
                <h4 class="mb-1">{{ $stats['completed'] }}</h4>
                <small>Completed</small>
            </div>
        </div>
    </div>
</div>
@endif

<!-- PBC Requests Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">+/-</th>
                        <th class="py-3">Category</th>
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
                        <tr>
                            <td class="px-4 py-3">
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails({{ $item->id }})">
                                    <i class="fas fa-plus" id="toggle-icon-{{ $item->id }}"></i>
                                </button>
                            </td>
                            <td class="py-3">
                                <span class="badge {{ $item->category == 'CF' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $item->category ?? 'CF' }}
                                </span>
                            </td>
                            <td class="py-3">{{ $item->particulars }}</td>
                            <td class="py-3">{{ $item->requestor ?? 'MNGR 1' }}</td>
                            <td class="py-3">{{ $item->date_requested_formatted ?? '25/07/2025' }}</td>
                            <td class="py-3">{{ $item->assigned_to ?? 'Client Staff 1' }}</td>
                            <td class="py-3">{{ $item->due_date_formatted ?? '' }}</td>
                            <td class="py-3">
                                @php
                                    $status = $item->getCurrentStatus();
                                    $statusColors = [
                                        'pending' => 'secondary',
                                        'uploaded' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'overdue' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="py-3">
                                <div class="btn-group btn-group-sm">
                                    @if($status == 'uploaded')
                                        <button class="btn btn-outline-success" onclick="approveItem({{ $item->id }})">
                                            Approve
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="rejectItem({{ $item->id }})">
                                            Reject
                                        </button>
                                    @elseif($status == 'approved')
                                        <button class="btn btn-outline-primary" onclick="viewItem({{ $item->id }})">
                                            View/Download
                                        </button>
                                    @elseif($status == 'overdue')
                                        <button class="btn btn-outline-warning" onclick="sendReminder({{ $item->id }})">
                                            Send Reminder
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3">
                                <small class="text-muted">{{ $item->note ?? 'Insert text' }}</small>
                            </td>
                        </tr>
                        <!-- Hidden details row -->
                        <tr id="details-{{ $item->id }}" class="d-none">
                            <td></td>
                            <td colspan="9" class="bg-light">
                                <div class="p-3">
                                    <h6>Request Details</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Full Description:</strong>
                                            <p>{{ $item->particulars }}</p>
                                            <strong>Requirements:</strong>
                                            <p>{{ $item->is_required ? 'Required' : 'Optional' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Documents:</strong>
                                            @if($item->documents->count() > 0)
                                                <ul class="list-unstyled">
                                                    @foreach($item->documents as $doc)
                                                    <li>
                                                        <i class="fas fa-file-{{ $doc->file_extension == 'pdf' ? 'pdf' : 'alt' }} me-2"></i>
                                                        {{ $doc->original_filename }}
                                                        <span class="badge bg-{{ $doc->status == 'approved' ? 'success' : ($doc->status == 'rejected' ? 'danger' : 'warning') }} ms-2">
                                                            {{ ucfirst($doc->status) }}
                                                        </span>
                                                    </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted">No documents uploaded yet</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <h5>No PBC Requests Found</h5>
                                <p>This project doesn't have any PBC requests yet.</p>
                                <div class="mt-3">
                                    <a href="{{ route('admin.clients.projects.pbc-requests.create', [$client, $project]) }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create First Request
                                    </a>
                                    <a href="{{ route('admin.clients.projects.pbc-requests.import', [$client, $project]) }}" class="btn btn-info ms-2">
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

{{-- Remove the pagination section since we're not using paginate() --}}
{{--
<!-- Pagination -->
@if($requests->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $requests->appends(request()->query())->links() }}
    </div>
@endif
--}}

{{-- Optional: Add a simple count display --}}
@if($requests->count() > 0)
    <div class="text-center mt-3">
        <small class="text-muted">
            Showing {{ $requests->count() }} PBC request(s) with {{ $requests->sum(function($r) { return $r->items->count(); }) }} total items
        </small>
    </div>
@endif

@endsection

@section('styles')
<style>
/* Status color variations */
.badge.bg-secondary { background-color: #6c757d !important; }
.badge.bg-primary { background-color: #0d6efd !important; }
.badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
.badge.bg-success { background-color: #198754 !important; }
.badge.bg-danger { background-color: #dc3545 !important; }
.badge.bg-info { background-color: #0dcaf0 !important; color: #000 !important; }



/* Table enhancements */
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
    font-size: 0.9rem;
}


/* Button group improvements */
.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 0.25rem;
}

/* Stats cards */
.card.bg-primary, .card.bg-info, .card.bg-warning,
.card.bg-danger, .card.bg-success {
    border: none;
    border-radius: 0.5rem;
}

/* Toggle button styling */
.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}



/* Form styling */
.form-control, .form-select {
    border-radius: 0.375rem;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Alert styling */
.alert {
    border-radius: 0.5rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }

    .card-body.text-center h4 {
        font-size: 1.2rem;
    }
}

/* Animation for toggle */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Icon alignment */
.fas {
    width: 16px;
    text-align: center;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.btn.loading {
    position: relative;
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
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-submit filter form when selection changes
    document.querySelector('select[name="status"]').addEventListener('change', function() {
        this.form.submit();
    });
});

// Toggle item details
function toggleDetails(itemId) {
    const detailsRow = document.getElementById(`details-${itemId}`);
    const icon = document.getElementById(`toggle-icon-${itemId}`);

    if (detailsRow.classList.contains('d-none')) {
        detailsRow.classList.remove('d-none');
        detailsRow.classList.add('fade-in');
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-minus');
    } else {
        detailsRow.classList.add('d-none');
        icon.classList.remove('fa-minus');
        icon.classList.add('fa-plus');
    }
}

// Approve item
function approveItem(itemId) {
    if (confirm('Approve this item?')) {
        fetch(`/admin/pbc-requests/items/${itemId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Item approved successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('Failed to approve item: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error approving item', 'danger');
        });
    }
}

// Reject item
function rejectItem(itemId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        fetch(`/admin/pbc-requests/items/${itemId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Item rejected successfully!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('Failed to reject item: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error rejecting item', 'danger');
        });
    }
}

// View item documents
function viewItem(itemId) {
    window.location.href = `/admin/pbc-requests/items/${itemId}/documents`;
}

// Send reminder
function sendReminder(itemId) {
    if (confirm('Send reminder to client for this item?')) {
        fetch(`/admin/reminders/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                pbc_request_item_id: itemId,
                reminder_type: 'standard'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Reminder sent successfully!', 'success');
            } else {
                showAlert('Failed to send reminder: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error sending reminder', 'danger');
        });
    }
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Auto-refresh every 30 seconds for status updates
setInterval(() => {
    // Optional: Add subtle refresh indicator
    console.log('Auto-checking for status updates...');
}, 30000);
</script>
@endsection
