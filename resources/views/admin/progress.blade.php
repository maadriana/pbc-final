@extends('layouts.app')
@section('title', 'Progress Tracking')

@section('content')
<!-- Header Section -->
<div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 border-bottom">
    <h1 class="mb-0 fs-2 fw-semibold text-dark">Progress Tracking</h1>
</div>

<!-- Stats Cards Section - Horizontal Layout matching wireframe -->
<div class="row g-3 mb-4">
    <div class="col">
        <div class="card border-0 shadow-sm h-100 text-white" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ $stats['total_requests'] ?? 0 }}</div>
                <div class="small opacity-90">Total Request</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 text-white" style="background: linear-gradient(135deg, #374151, #6b7280);">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ $stats['pending'] ?? 0 }}</div>
                <div class="small opacity-90">Pending</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 text-white" style="background: linear-gradient(135deg, #d97706, #f59e0b);">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ $stats['pending_review'] ?? 0 }}</div>
                <div class="small opacity-90">Pending Review</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 text-white" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ $stats['overdue'] ?? 0 }}</div>
                <div class="small opacity-90">Overdue</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 shadow-sm h-100 text-white" style="background: linear-gradient(135deg, #059669, #10b981);">
            <div class="card-body text-center py-4">
                <div class="display-6 fw-bold mb-2">{{ $stats['completed'] ?? 0 }}</div>
                <div class="small opacity-90">Completed</div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Section -->
<div class="card border-0 shadow-sm">
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
                </tr>
            </thead>
            <tbody>
                @forelse($requests ?? [] as $request)
                <tr>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.pbc-requests.show', $request) }}" class="text-decoration-none fw-bold text-primary">
                            {{ $request->project->job_id ?? $request->id }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="fw-medium">{{ $request->client->company_name ?? 'N/A' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-capitalize">{{ ucfirst($request->project->engagement_type ?? 'audit') }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span>{{ $request->project->engagement_name ?? $request->title }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $isOverdue = method_exists($request, 'isOverdue') ? $request->isOverdue() : false;
                            $displayStatus = $isOverdue ? 'overdue' : $request->status;
                        @endphp

                        @if($displayStatus === 'completed')
                            <span class="badge rounded-pill px-3 py-2" style="background-color: #d1fae5; color: #065f46;">
                                COMPLETED
                            </span>
                        @elseif($displayStatus === 'in_progress')
                            <span class="badge rounded-pill px-3 py-2" style="background-color: #fef3c7; color: #92400e;">
                                IN PROGRESS
                            </span>
                        @elseif($displayStatus === 'overdue')
                            <span class="badge rounded-pill px-3 py-2" style="background-color: #fecaca; color: #991b1b;">
                                OVERDUE
                            </span>
                        @else
                            <span class="badge rounded-pill px-3 py-2" style="background-color: #f3f4f6; color: #374151;">
                                PENDING
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $progressPercentage = method_exists($request, 'getProgressPercentage') ? $request->getProgressPercentage() :
                                ($request->status == 'completed' ? 100 :
                                ($request->status == 'in_progress' ? 50 : 0));
                        @endphp
                        <div class="d-flex align-items-center">
                            <div class="progress me-3" style="width: 100px; height: 8px; background-color: #e5e7eb;">
                                <div class="progress-bar"
                                     style="background-color: #10b981; width: {{ $progressPercentage }}%;">
                                </div>
                            </div>
                            <small class="text-muted">{{ $progressPercentage }}%</small>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $daysOutstanding = method_exists($request, 'getDaysOutstanding') ? $request->getDaysOutstanding() :
                                ($request->created_at ? $request->created_at->diffInDays(now()) : 0);
                        @endphp
                        <span class="fw-medium">{{ $daysOutstanding }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-chart-line fa-3x mb-3 opacity-50"></i>
                            <div class="h5">No requests found</div>
                            <small>Progress tracking data will appear here when requests are available</small>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
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

@endsection

@section('styles')
<style>
/* Custom styles to match wireframe */
.display-6 {
    font-size: 2.5rem;
    font-weight: 700;
}

.card-body {
    transition: transform 0.2s ease;
}

.card:hover .card-body {
    transform: translateY(-2px);
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

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.progress {
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 4px;
    transition: width 0.6s ease;
}

.badge {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Stats cards animation */
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

/* Link hover effects */
.text-primary:hover {
    color: #0056b3 !important;
    text-decoration: underline !important;
}

/* Empty state styling */
.fa-chart-line {
    color: #6c757d;
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

    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'transform 0.2s ease';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

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

// Function to refresh data (can be called from other parts of the app)
function refreshProgressData() {
    window.location.reload();
}

// Function to filter data (for future enhancement)
function filterByStatus(status) {
    const url = new URL(window.location.href);
    if (status && status !== 'all') {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
}
</script>
@endsection
