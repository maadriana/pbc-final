@extends('layouts.app')
@section('title', 'Progress Tracking')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 border-bottom">
    <div>
        <h1 class="mb-1 fs-2 fw-semibold text-dark">Progress Tracking</h1>
        <p class="text-muted mb-0">Track the progress of your document requests and submissions</p>
    </div>
    <button class="btn btn-outline-secondary" onclick="refreshProgress()">
        <i class="fas fa-sync-alt me-2"></i>Refresh
    </button>
</div>

<!-- Stats Cards Section -->
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ $progressData ? count($progressData) : 0 }}</div>
                <div class="small opacity-90">Total Request</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ collect($progressData ?? [])->where('status', 'pending')->count() }}</div>
                <div class="small">Pending</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ collect($progressData ?? [])->where('status', 'in_progress')->count() }}</div>
                <div class="small">Pending Review</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-danger text-white">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ collect($progressData ?? [])->filter(function($item) { return isset($item['due_date']) && $item['due_date'] && $item['due_date']->isPast() && $item['status'] !== 'completed'; })->count() }}</div>
                <div class="small opacity-90">Overdue</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 bg-success text-white">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ collect($progressData ?? [])->where('status', 'completed')->count() }}</div>
                <div class="small opacity-90">Completed</div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Section -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-chart-line text-primary me-2"></i>
                Request Progress Details
            </h5>
            <select class="form-select form-select-sm" id="statusFilter" onchange="filterByStatus()" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3 fw-semibold">ID</th>
                        <th class="px-4 py-3 fw-semibold">Client Name</th>
                        <th class="px-4 py-3 fw-semibold">Engagement Type</th>
                        <th class="px-4 py-3 fw-semibold">Engagement Name</th>
                        <th class="px-4 py-3 fw-semibold">Status</th>
                        <th class="px-4 py-3 fw-semibold">Progress</th>
                        <th class="px-4 py-3 fw-semibold">Days Outstanding</th>
                        <th class="px-4 py-3 fw-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests ?? [] as $request)
                    @php
                        $progress = $request->getProgressPercentage();
                        $daysOutstanding = $request->getDaysOutstanding();
                        $isOverdue = $request->isOverdue();
                        $completedItems = $request->items->filter(function($item) { return $item->getCurrentStatus() === 'approved'; })->count();
                        $totalItems = $request->items->count();
                    @endphp
                    <tr class="status-row" data-status="{{ $isOverdue ? 'overdue' : $request->status }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('client.pbc-requests.show', $request) }}" class="text-decoration-none fw-bold text-primary">
                                {{ $request->project->job_id ?? sprintf('#%05d', $request->id) }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="fw-medium">{{ auth()->user()->client->company_name }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-capitalize">{{ ucfirst($request->project->engagement_type ?? 'audit') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span>{{ $request->project->engagement_name ?? $request->title }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($isOverdue)
                                <span class="badge bg-danger px-3 py-2">OVERDUE</span>
                            @elseif($request->status === 'completed')
                                <span class="badge bg-success px-3 py-2">COMPLETED</span>
                            @elseif($request->status === 'in_progress')
                                <span class="badge bg-warning px-3 py-2">IN PROGRESS</span>
                            @else
                                <span class="badge bg-warning px-3 py-2">PENDING</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="progress me-3" style="width: 100px; height: 8px; background-color: #e5e7eb;">
                                    <div class="progress-bar bg-success" style="width: {{ $progress }}%;"></div>
                                </div>
                                <small class="text-muted">{{ $progress }}%</small>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="fw-medium">{{ $daysOutstanding }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="btn-group" role="group">
                                <a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($request->status !== 'completed')
                                    <button type="button" class="btn btn-success btn-sm" onclick="quickUpload({{ $request->id }})" title="Quick Upload">
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
                                <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No progress data available</div>
                                <small>Progress tracking data will appear here when requests are available</small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($requests) && method_exists($requests, 'links'))
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }}
                    of {{ $requests->total() ?? 0 }} results
                </div>
                {{ $requests->links() }}
            </div>
        </div>
    @endif
</div>

<!-- Progress Summary Cards -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie text-success me-2"></i>
                    Category Progress
                </h5>
            </div>
            <div class="card-body">
                @if(isset($categoryProgress) && count($categoryProgress) > 0)
                    @foreach($categoryProgress as $category => $data)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-medium">{{ $category === 'CF' ? 'Current File' : ($category === 'PF' ? 'Permanent File' : $category) }}</small>
                            <small class="text-muted">{{ $data['completed'] }}/{{ $data['total'] }}</small>
                        </div>
                        <div class="progress" style="height: 8px; background-color: #e5e7eb;">
                            <div class="progress-bar bg-{{ $category === 'CF' ? 'primary' : 'secondary' }}"
                                 style="width: {{ $data['total'] > 0 ? ($data['completed'] / $data['total']) * 100 : 0 }}%;">
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
                <h5 class="mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    Progress Summary
                </h5>
            </div>
            <div class="card-body">
                @php
                    $totalRequests = count($progressData ?? []);
                    $totalCompleted = collect($progressData ?? [])->where('status', 'completed')->count();
                    $totalOverdue = collect($progressData ?? [])->filter(function($item) {
                        return isset($item['due_date']) && $item['due_date'] && $item['due_date']->isPast() && $item['status'] !== 'completed';
                    })->count();
                    $overallProgress = $totalRequests > 0 ? round(($totalCompleted / $totalRequests) * 100) : 0;
                @endphp

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-medium">Overall Progress</span>
                        <span class="text-primary fw-bold">{{ $overallProgress }}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 12px; background-color: #e5e7eb;">
                        <div class="progress-bar bg-primary" style="width: {{ $overallProgress }}%;"></div>
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
/* Display sizing */
.display-6 {
    font-size: 2.5rem;
    font-weight: 700;
}

.table th {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.progress {
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 4px;
}

.badge {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 0.375rem;
}

/* Animation for numbers */
@keyframes countUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.display-6 {
    animation: countUp 0.8s ease-out;
}

/* Responsive design */
@media (max-width: 768px) {
    .display-6 {
        font-size: 2rem;
    }

    .table-responsive {
        font-size: 0.875rem;
    }

    .px-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}

/* Empty state styling */
.fa-chart-line {
    color: #6c757d;
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
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counter numbers on page load
    setTimeout(function() {
        const counters = document.querySelectorAll('.display-6');

        counters.forEach(counter => {
            const target = parseInt(counter.textContent);
            if (isNaN(target)) return;

            let current = 0;
            const increment = Math.ceil(target / 30);

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = current;
                }
            }, 30);
        });
    }, 300);

    // Progress bar animation
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
});

// Refresh progress data
function refreshProgress() {
    const refreshBtn = document.querySelector('button[onclick="refreshProgress()"]');
    const originalHtml = refreshBtn.innerHTML;

    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';

    setTimeout(function() {
        window.location.reload();
    }, 1000);
}

// Filter by status
function filterByStatus() {
    const filter = document.getElementById('statusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.status-row');

    rows.forEach(row => {
        const status = row.getAttribute('data-status').toLowerCase();
        if (filter === '' || status === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Quick upload function
function quickUpload(requestId) {
    window.location.href = `/client/pbc-requests/${requestId}#upload-section`;
}
</script>
@endsection
