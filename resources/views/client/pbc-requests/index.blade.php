@extends('layouts.app')
@section('title', 'My PBC Requests')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">My PBC Requests</h1>
        <p class="text-muted mb-0">View and manage your assigned PBC document requests</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="refreshPage()">
            <i class="fas fa-sync-alt me-2"></i>Refresh
        </button>
    </div>
</div>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search Requests</label>
                <input type="text" name="search" class="form-control" placeholder="Search by title, project..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Filter by Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">Sort by</label>
                <select name="sort" class="form-select">
                    <option value="">Default</option>
                    <option value="due_date_asc" {{ request('sort') == 'due_date_asc' ? 'selected' : '' }}>Due Date (Earliest)</option>
                    <option value="due_date_desc" {{ request('sort') == 'due_date_desc' ? 'selected' : '' }}>Due Date (Latest)</option>
                    <option value="created_desc" {{ request('sort') == 'created_desc' ? 'selected' : '' }}>Recently Created</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- PBC Requests Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                My PBC Requests ({{ $requests->count() }})
            </h5>
            <div class="text-muted small">
                {{ $requests->where('status', 'completed')->count() }} of {{ $requests->count() }} completed
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Progress</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    @php
                        $isOverdue = $request->due_date && $request->due_date->isPast() && $request->status !== 'completed';
                        $displayStatus = $isOverdue ? 'overdue' : $request->status;

                        $statusColors = [
                            'pending' => 'warning',
                            'in_progress' => 'info',
                            'completed' => 'success',
                            'overdue' => 'danger'
                        ];
                        $statusColor = $statusColors[$displayStatus] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="px-4 py-3">{{ $request->id }}</td>
                        <td class="px-4 py-3">{{ $request->title }}</td>
                        <td class="px-4 py-3">{{ $request->project->name }}</td>
                        <td class="px-4 py-3">
                            <span class="badge bg-{{ $statusColor }}">
                                {{ ucfirst($displayStatus) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($request->due_date)
                                <div class="text-muted small {{ $isOverdue ? 'text-danger' : '' }}">
                                    {{ $request->due_date->format('M d, Y') }}
                                    @if($isOverdue)
                                        <br>
                                        <i class="fas fa-exclamation-triangle"></i>
                                        {{ $request->due_date->diffForHumans() }}
                                    @else
                                        <br>
                                        {{ $request->due_date->diffForHumans() }}
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $request->getProgressPercentage() }}%</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">View & Upload</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No PBC requests found</div>
                                <small>
                                    @if(request('search') || request('status') || request('sort'))
                                        Try adjusting your search criteria or <a href="{{ route('client.pbc-requests.index') }}" class="text-decoration-none">clear filters</a>
                                    @else
                                        No requests assigned
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
    @if($requests->hasPages())
    <div class="card-footer bg-white border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() ?? 0 }} results
            </div>
            {{ $requests->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
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

    .text-truncate {
        max-width: 100px !important;
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
});

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

// Refresh page
function refreshPage() {
    console.log('Refreshing page...');

    const refreshBtn = event.target;
    const originalText = refreshBtn.innerHTML;

    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Refreshing...';
    refreshBtn.disabled = true;

    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Enhanced alert function
function showAlert(message, type) {
    console.log(`Alert [${type}]:`, message);

    // Remove existing alerts
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
