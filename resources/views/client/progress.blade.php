@extends('layouts.app')
@section('title', 'Progress Tracking')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Progress Tracking</h1>
        <p class="text-muted mb-0">Track the progress of your document requests and submissions</p>
    </div>
    <div>
        <button class="btn btn-outline-primary" onclick="refreshProgress()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
</div>

<!-- Progress Status Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $progressData ? count($progressData) : 0 }}</h3>
                <small class="opacity-90">Total Request</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ collect($progressData ?? [])->where('status', 'pending')->count() }}</h3>
                <small class="opacity-90">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ collect($progressData ?? [])->where('status', 'in_progress')->count() }}</h3>
                <small class="opacity-90">Pending Review</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ collect($progressData ?? [])->filter(function($item) { return isset($item['due_date']) && $item['due_date'] && $item['due_date']->isPast() && $item['status'] !== 'completed'; })->count() }}</h3>
                <small class="opacity-90">Overdue</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ collect($progressData ?? [])->where('status', 'completed')->count() }}</h3>
                <small class="opacity-90">Completed</small>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Progress Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-chart-line text-primary me-2"></i>
                Request Progress Details
            </h5>
            <div class="d-flex gap-2">
                <!-- Filter Options -->
                <select class="form-select form-select-sm" id="statusFilter" onchange="filterByStatus()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>

                <!-- View Toggle -->
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="viewMode" id="tableView" checked onchange="toggleView('table')">
                    <label class="btn btn-outline-secondary btn-sm" for="tableView">
                        <i class="fas fa-table"></i>
                    </label>

                    <input type="radio" class="btn-check" name="viewMode" id="cardView" onchange="toggleView('card')">
                    <label class="btn btn-outline-secondary btn-sm" for="cardView">
                        <i class="fas fa-th-large"></i>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Table View -->
        <div id="tableViewContent" class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="py-3">Client Name</th>
                        <th class="py-3">Engagement Type</th>
                        <th class="py-3">Engagement Name</th>
                        <th class="py-3">Status</th>
                        <th class="py-3">Progress</th>
                        <th class="py-3">Days Outstanding</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests ?? [] as $request)
                    @php
                        $progress = $request->getProgressPercentage();
                        $daysOutstanding = $request->getDaysOutstanding();
                        $isOverdue = $request->isOverdue();
                        $statusClass = match($request->status) {
                            'completed' => 'success',
                            'in_progress' => 'warning',
                            'pending' => 'secondary',
                            default => $isOverdue ? 'danger' : 'secondary'
                        };
                        $progressClass = match(true) {
                            $progress >= 100 => 'success',
                            $progress >= 75 => 'info',
                            $progress >= 50 => 'warning',
                            $progress >= 25 => 'warning',
                            default => 'danger'
                        };
                    @endphp
                    <tr class="status-row" data-status="{{ $isOverdue ? 'overdue' : $request->status }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('client.pbc-requests.show', $request) }}" class="text-decoration-none fw-bold">
                                {{ $request->project->job_id ?? sprintf('#%05d', $request->id) }}
                            </a>
                        </td>
                        <td class="py-3">{{ auth()->user()->client->company_name }}</td>
                        <td class="py-3">
                            <span class="badge bg-primary">
                                {{ ucfirst($request->project->engagement_type ?? 'audit') }}
                            </span>
                        </td>
                        <td class="py-3">{{ $request->project->engagement_name ?? $request->title }}</td>
                        <td class="py-3">
                            <span class="badge bg-{{ $statusClass }}">
                                {{ $isOverdue ? 'Overdue' : ucfirst(str_replace('_', ' ', $request->status)) }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 80px; height: 8px;">
                                    <div class="progress-bar bg-{{ $progressClass }}"
                                         role="progressbar"
                                         style="width: {{ $progress }}%"
                                         aria-valuenow="{{ $progress }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">{{ $progress }}%</small>
                            </div>
                            <small class="text-muted">
                                {{ $request->items->filter(function($item) { return $item->getCurrentStatus() === 'approved'; })->count() }}/{{ $request->items->count() }} completed
                            </small>
                        </td>
                        <td class="py-3">
                            <div class="text-center">
                                @if($daysOutstanding > 0)
                                    <span class="badge bg-{{ $isOverdue ? 'danger' : ($daysOutstanding > 7 ? 'warning' : 'info') }}">
                                        {{ $daysOutstanding }} {{ Str::plural('day', $daysOutstanding) }}
                                    </span>
                                @else
                                    <span class="text-muted">0 days</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="btn-group" role="group">
                                <a href="{{ route('client.pbc-requests.show', $request) }}"
                                   class="btn btn-outline-primary btn-sm"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($request->status !== 'completed')
                                    <button type="button"
                                            class="btn btn-outline-success btn-sm"
                                            onclick="quickUpload({{ $request->id }})"
                                            title="Quick Upload">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-chart-line fa-3x mb-3"></i>
                                <h5>No Progress Data Available</h5>
                                <p>You don't have any active requests to track progress.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Card View (Hidden by default) -->
        <div id="cardViewContent" class="p-4" style="display: none;">
            <div class="row g-4">
                @foreach($requests ?? [] as $request)
                @php
                    $progress = $request->getProgressPercentage();
                    $daysOutstanding = $request->getDaysOutstanding();
                    $isOverdue = $request->isOverdue();
                    $completedItems = $request->items->filter(function($item) { return $item->getCurrentStatus() === 'approved'; })->count();
                    $totalItems = $request->items->count();
                @endphp
                <div class="col-md-6 col-lg-4 status-card" data-status="{{ $isOverdue ? 'overdue' : $request->status }}">
                    <div class="card h-100 border-0 shadow-sm {{ $isOverdue ? 'border-danger' : '' }}">
                        <div class="card-header bg-{{ $isOverdue ? 'danger' : 'primary' }} text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ $request->project->job_id ?? sprintf('#%05d', $request->id) }}</h6>
                                <span class="badge bg-white text-{{ $isOverdue ? 'danger' : 'primary' }}">
                                    {{ $progress }}%
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title">{{ $request->project->engagement_name ?? $request->title }}</h6>
                            <p class="card-text text-muted small">
                                <i class="fas fa-building me-1"></i>{{ auth()->user()->client->company_name }}<br>
                                <i class="fas fa-tag me-1"></i>{{ ucfirst($request->project->engagement_type ?? 'audit') }}
                            </p>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Progress</small>
                                    <small class="text-muted">{{ $completedItems }}/{{ $totalItems }} items</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-{{ $progress >= 75 ? 'success' : ($progress >= 50 ? 'warning' : 'danger') }}"
                                         role="progressbar"
                                         style="width: {{ $progress }}%"
                                         aria-valuenow="{{ $progress }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Status and Days -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-{{ $isOverdue ? 'danger' : ($request->status === 'completed' ? 'success' : ($request->status === 'in_progress' ? 'warning' : 'secondary')) }}">
                                    {{ $isOverdue ? 'Overdue' : ucfirst(str_replace('_', ' ', $request->status)) }}
                                </span>
                                @if($daysOutstanding > 0)
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>{{ $daysOutstanding }} {{ Str::plural('day', $daysOutstanding) }}
                                    </small>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                @if($request->status !== 'completed')
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="quickUpload({{ $request->id }})">
                                        <i class="fas fa-upload me-1"></i>Quick Upload
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($request->due_date)
                            <div class="card-footer bg-light text-muted small">
                                <i class="fas fa-calendar me-1"></i>
                                Due: {{ $request->due_date->format('M d, Y') }}
                                @if($isOverdue)
                                    <span class="text-danger">({{ $request->due_date->diffForHumans() }})</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Pagination -->
@if(isset($requests) && $requests->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $requests->appends(request()->query())->links() }}
</div>
@endif

<!-- Progress Summary -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">
                    <i class="fas fa-chart-pie text-success me-2"></i>
                    Category Progress
                </h6>
            </div>
            <div class="card-body">
                @if(isset($categoryProgress) && count($categoryProgress) > 0)
                    @foreach($categoryProgress as $category => $data)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold">{{ $category === 'CF' ? 'Current File' : ($category === 'PF' ? 'Permanent File' : $category) }}</small>
                            <small class="text-muted">{{ $data['completed'] }}/{{ $data['total'] }}</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $category === 'CF' ? 'primary' : 'secondary' }}"
                                 role="progressbar"
                                 style="width: {{ $data['total'] > 0 ? ($data['completed'] / $data['total']) * 100 : 0 }}%">
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted text-center mb-0">No category data available</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Progress Summary
                </h6>
            </div>
            <div class="card-body">
                @php
                    $totalRequests = count($progressData ?? []);
                    $totalCompleted = collect($progressData ?? [])->where('status', 'completed')->count();
                    $totalInProgress = collect($progressData ?? [])->where('status', 'in_progress')->count();
                    $totalOverdue = collect($progressData ?? [])->filter(function($item) {
                        return isset($item['due_date']) && $item['due_date'] && $item['due_date']->isPast() && $item['status'] !== 'completed';
                    })->count();
                    $overallProgress = $totalRequests > 0 ? round(($totalCompleted / $totalRequests) * 100) : 0;
                @endphp

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold">Overall Progress</span>
                        <span class="text-primary fw-bold">{{ $overallProgress }}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 12px;">
                        <div class="progress-bar bg-gradient bg-primary"
                             role="progressbar"
                             style="width: {{ $overallProgress }}%">
                        </div>
                    </div>
                </div>

                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h6 class="text-success mb-0">{{ $totalCompleted }}</h6>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h6 class="text-{{ $totalOverdue > 0 ? 'danger' : 'muted' }} mb-0">{{ $totalOverdue }}</h6>
                        <small class="text-muted">Overdue</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Status cards styling */
.card {
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
}

/* Progress bar enhancements */
.progress {
    border-radius: 0.5rem;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 0.5rem;
    transition: width 0.6s ease;
}

/* Table enhancements */
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Badge improvements */
.badge {
    font-size: 0.75em;
    font-weight: 500;
    border-radius: 0.375rem;
}

/* Button group styling */
.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Card view specific styling */
.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
}

.card-footer {
    border-radius: 0 0 0.5rem 0.5rem;
}

/* Status indicators */
.border-danger {
    border-color: #dc3545 !important;
    border-width: 2px !important;
}

/* Filter and view toggle */
.btn-check:checked + .btn {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

/* Animation for progress bars */
@keyframes progressFill {
    from { width: 0%; }
    to { width: var(--progress-width); }
}

.progress-bar {
    animation: progressFill 1s ease-in-out;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-2 {
        margin-bottom: 1rem;
    }

    .table-responsive {
        font-size: 0.875rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

/* Custom scrollbar */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Loading animation */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
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

    // Auto-refresh every 2 minutes
    setInterval(refreshProgress, 120000);
});

// Refresh progress data
function refreshProgress() {
    const refreshBtn = document.querySelector('button[onclick="refreshProgress()"]');
    const originalHtml = refreshBtn.innerHTML;

    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';

    // Simulate refresh (replace with actual AJAX call)
    setTimeout(function() {
        window.location.reload();
    }, 1000);
}

// Filter by status
function filterByStatus() {
    const filter = document.getElementById('statusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.status-row');
    const cards = document.querySelectorAll('.status-card');

    rows.forEach(row => {
        const status = row.getAttribute('data-status').toLowerCase();
        if (filter === '' || status === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    cards.forEach(card => {
        const status = card.getAttribute('data-status').toLowerCase();
        if (filter === '' || status === filter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Toggle between table and card view
function toggleView(viewType) {
    const tableView = document.getElementById('tableViewContent');
    const cardView = document.getElementById('cardViewContent');

    if (viewType === 'table') {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
    } else {
        tableView.style.display = 'none';
        cardView.style.display = 'block';
    }

    // Save preference
    localStorage.setItem('progressViewMode', viewType);
}

// Quick upload function
function quickUpload(requestId) {
    // Redirect to the request detail page for upload
    window.location.href = `/client/pbc-requests/${requestId}#upload-section`;
}

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('progressViewMode');
    if (savedView) {
        const radio = document.getElementById(savedView + 'View');
        if (radio) {
            radio.checked = true;
            toggleView(savedView);
        }
    }
});

// Progress bar animation
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar');

    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';

        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
}

// Run animation on page load
setTimeout(animateProgressBars, 500);

// Export progress report
function exportProgressReport() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', 'pdf');
    window.location.href = currentUrl.toString();
}

// Print progress report
function printProgressReport() {
    window.print();
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'r':
                e.preventDefault();
                refreshProgress();
                break;
            case 't':
                e.preventDefault();
                document.getElementById('tableView').click();
                break;
            case 'g':
                e.preventDefault();
                document.getElementById('cardView').click();
                break;
        }
    }
});
</script>
@endsection
