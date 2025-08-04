@extends('layouts.app')
@section('title', 'Client Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Client Dashboard</h1>
        <p class="text-muted mb-0">Welcome, {{ auth()->user()->client->company_name }}!</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-primary">
            <i class="fas fa-file-alt me-2"></i>View All Requests
        </a>
        <a href="{{ route('client.documents.index') }}" class="btn btn-info">
            <i class="fas fa-folder-open me-2"></i>My Documents
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100 bg-primary text-white">
            <div class="card-body">
                <h3 class="mb-1">{{ $metrics['total_requests'] }}</h3>
                <small class="opacity-90">Total Requests</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100 bg-warning text-dark">
            <div class="card-body">
                <h3 class="mb-1">{{ $metrics['pending_requests'] }}</h3>
                <small>Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100 bg-info text-white">
            <div class="card-body">
                <h3 class="mb-1">{{ $metrics['in_progress_requests'] }}</h3>
                <small class="opacity-90">In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100 bg-success text-white">
            <div class="card-body">
                <h3 class="mb-1">{{ $metrics['completed_requests'] }}</h3>
                <small class="opacity-90">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100 bg-danger text-white">
            <div class="card-body">
                <h3 class="mb-1">{{ $metrics['overdue_requests'] }}</h3>
                <small class="opacity-90">Overdue</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100 bg-info text-white">
            <div class="card-body">
                <h3 class="mb-1">{{ $metrics['total_documents'] }}</h3>
                <small class="opacity-90">Documents</small>
            </div>
        </div>
    </div>
</div>

<!-- Recent PBC Requests Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Recent PBC Requests
            </h5>
            <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-outline-primary btn-sm">
                View All
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Request Details</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent_requests as $request)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="fw-bold text-primary">#{{ $request->id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px; font-weight: 600;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $request->title }}</div>
                                    <small class="text-muted">PBC Request</small>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="fw-medium">{{ $request->project->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $request->project->engagement_type ?? 'Project' }}</small>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'overdue' => 'danger'
                                ];
                                $statusColor = $statusColors[$request->status] ?? 'warning';
                            @endphp
                            <span class="badge bg-{{ $statusColor }}">
                                {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($request->due_date)
                                <div class="text-muted small">
                                    {{ $request->due_date->format('M d, Y') }}
                                    <br>
                                    <span class="text-muted">{{ $request->due_date->diffForHumans() }}</span>
                                </div>
                            @else
                                <span class="text-muted">No due date</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="btn-group" role="group">
                                <a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-primary btn-sm" title="View Request">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($request->status == 'pending' || $request->status == 'in_progress')
                                    <a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-warning btn-sm" title="Upload Documents">
                                        <i class="fas fa-upload"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No PBC requests found</div>
                                <small>Your PBC requests will appear here when they are created</small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Card styling */
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #fff !important;
}

/* Status card specific styling */
.card-body h3, .card-body h4 {
    font-weight: 600;
}

/* Table improvements */
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

/* Avatar styling */
.rounded-circle {
    border-radius: 50% !important;
}

/* Empty state styling */
.fa-file-alt {
    color: #6c757d;
}

/* Responsive design */
@media (max-width: 768px) {
    .col-md-2 {
        margin-bottom: 1rem;
    }

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

/* Animation for metrics */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card-body h3, .card-body h4 {
    animation: countUp 0.5s ease-out;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate metrics on page load
    setTimeout(animateMetrics, 500);

    // Set up refresh interval for metrics
    setInterval(function() {
        // You can implement auto-refresh of metrics here if needed
        console.log('Dashboard metrics refresh check');
    }, 300000); // 5 minutes
});

// Animate metrics function
function animateMetrics() {
    const metricNumbers = document.querySelectorAll('.card-body h3');

    metricNumbers.forEach(function(metric) {
        const finalValue = parseInt(metric.textContent);
        if (isNaN(finalValue)) return;

        let currentValue = 0;
        const increment = Math.ceil(finalValue / 20);

        const timer = setInterval(function() {
            currentValue += increment;
            if (currentValue >= finalValue) {
                metric.textContent = finalValue;
                clearInterval(timer);
            } else {
                metric.textContent = currentValue;
            }
        }, 50);
    });
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px;';

    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };

    alertDiv.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + R for requests
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        window.location.href = '{{ route("client.pbc-requests.index") }}';
    }

    // Ctrl/Cmd + D for documents
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        window.location.href = '{{ route("client.documents.index") }}';
    }
});
</script>
@endsection
