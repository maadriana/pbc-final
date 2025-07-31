@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">Monitor and manage all system activities, users, clients, and PBC requests</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.pbc-requests.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Job
        </a>
        <a href="{{ route('admin.pbc-requests.import') }}" class="btn btn-success">
            <i class="fas fa-upload"></i> Import Request
        </a>
        <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
</div>

<!-- Admin Main Metrics Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $metrics['total_users'] ?? 7 }}</h3>
                <small class="opacity-90">Users</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $metrics['total_clients'] ?? 2 }}</h3>
                <small class="opacity-90">Clients</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $metrics['total_projects'] ?? 3 }}</h3>
                <small class="opacity-90">Projects</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $metrics['active_requests'] ?? 3 }}</h3>
                <small class="opacity-90">Active Request</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $metrics['pending_documents'] ?? 3 }}</h3>
                <small class="opacity-90">Pending Review</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #326C79, #4a8a95);">
            <div class="card-body text-white">
                <h3 class="mb-1">{{ $metrics['completed_requests'] ?? 3 }}</h3>
                <small class="opacity-90">Completed</small>
            </div>
        </div>
    </div>
</div>

<!-- New Created Users -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog text-primary me-2"></i>
                        New Created Users
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="userTimeFilter" onchange="filterUsersByTime()">
                            <option value="today">Today</option>
                            <option value="week" selected>This Week</option>
                            <option value="month">This Month</option>
                            <option value="all">All Time</option>
                        </select>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Surname</th>
                                <th>Given Name</th>
                                <th>Position</th>
                                <th>Created Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($new_users ?? [] as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->surname }}</td>
                                <td>{{ $user->given_name }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ ucfirst($user->position ?? 'Staff') }}
                                    </span>
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-primary btn-sm" title="View User">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-warning btn-sm" title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                                    <div>No new users found</div>
                                    <small>Recently created users will appear here</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Created Clients -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-building text-success me-2"></i>
                        New Created Clients
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="clientTimeFilter" onchange="filterClientsByTime()">
                            <option value="today">Today</option>
                            <option value="week" selected>This Week</option>
                            <option value="month">This Month</option>
                            <option value="all">All Time</option>
                        </select>
                        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Contact Person</th>
                                <th>Contact Number</th>
                                <th>Position</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($new_clients ?? [] as $client)
                            <tr>
                                <td>{{ $client->id }}</td>
                                <td>{{ $client->company_name }}</td>
                                <td>{{ $client->contact_person }}</td>
                                <td>{{ $client->contact_number }}</td>
                                <td>{{ $client->contact_position ?? 'N/A' }}</td>
                                <td>{{ $client->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-primary btn-sm" title="View Client">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-outline-warning btn-sm" title="Edit Client">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-building fa-2x mb-2"></i>
                                    <div>No new clients found</div>
                                    <small>Recently created clients will appear here</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Created Projects -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-project-diagram text-info me-2"></i>
                        New Created Projects
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="projectTimeFilter" onchange="filterProjectsByTime()">
                            <option value="today">Today</option>
                            <option value="week" selected>This Week</option>
                            <option value="month">This Month</option>
                            <option value="all">All Time</option>
                        </select>
                        <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm ">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Engagement Name</th>
                                <th>Engagement Type</th>
                                <th>Engagement Period</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($new_projects ?? [] as $project)
                            <tr>
                                <td>{{ $project->job_id ?? sprintf('#%05d', $project->id) }}</td>
                                <td>{{ $project->client->company_name ?? 'N/A' }}</td>
                                <td>{{ $project->engagement_name }}</td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ ucfirst($project->engagement_type) }}
                                    </span>
                                </td>
                                <td>{{ $project->engagement_period ?? 'N/A' }}</td>
                                <td>{{ $project->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-outline-primary btn-sm" title="View Project">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-outline-warning btn-sm" title="Edit Project">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-project-diagram fa-2x mb-2"></i>
                                    <div>No new projects found</div>
                                    <small>Recently created projects will appear here</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Created Request -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt text-warning me-2"></i>
                        New Created Request
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="requestTimeFilter" onchange="filterRequestsByTime()">
                            <option value="today">Today</option>
                            <option value="week" selected>This Week</option>
                            <option value="month">This Month</option>
                            <option value="all">All Time</option>
                        </select>
                        <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm ">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Engagement Name</th>
                                <th>Request Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($new_requests ?? [] as $request)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.pbc-requests.show', $request) }}" class="text-decoration-none fw-bold">
                                        {{ $request->project->job_id ?? sprintf('#%05d', $request->id) }}
                                    </a>
                                </td>
                                <td>{{ $request->client->company_name }}</td>
                                <td>{{ $request->project->engagement_name ?? $request->title }}</td>
                                <td>{{ \Str::limit($request->title, 40) }}</td>
                                <td>
                                    @php
                                        $statusClasses = [
                                            'completed' => 'success',
                                            'in_progress' => 'warning',
                                            'overdue' => 'danger',
                                            'pending' => 'secondary'
                                        ];
                                        $displayStatus = $request->isOverdue() ? 'overdue' : $request->status;
                                        $statusClass = $statusClasses[$displayStatus] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $displayStatus == 'overdue' ? 'Overdue' : ucfirst(str_replace('_', ' ', $request->status)) }}
                                    </span>
                                </td>
                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-outline-primary btn-sm" title="View Request">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($request->status !== 'completed')
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="sendReminder({{ $request->id }})" title="Send Reminder">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                                    <div>No new requests found</div>
                                    <small>Recently created requests will appear here</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Recent Uploaded Request -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Uploaded Request</h6>
                    <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-outline-primary btn-sm">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Engagement Type</th>
                                <th>Engagement Name</th>
                                <th>Request Description</th>
                                <th>Uploaded By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recent_requests ?? [] as $request)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.pbc-requests.show', $request) }}" class="text-decoration-none fw-bold">
                                        {{ $request->project->job_id ?? sprintf('#%05d', $request->id) }}
                                    </a>
                                </td>
                                <td>{{ $request->client->company_name }}</td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ ucfirst($request->project->engagement_type ?? 'audit') }}
                                    </span>
                                </td>
                                <td>{{ $request->project->engagement_name ?? $request->title }}</td>
                                <td>{{ \Str::limit($request->title, 40) }}</td>
                                <td>{{ $request->creator->name ?? 'System' }}</td>
                                <td>
                                    @php
                                        $statusClasses = [
                                            'completed' => 'success',
                                            'in_progress' => 'warning',
                                            'overdue' => 'danger',
                                            'pending' => 'secondary'
                                        ];
                                        $displayStatus = $request->isOverdue() ? 'overdue' : $request->status;
                                        $statusClass = $statusClasses[$displayStatus] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $displayStatus == 'overdue' ? 'Overdue' : ucfirst(str_replace('_', ' ', $request->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-outline-primary btn-sm" title="View Request">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($request->status !== 'completed')
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="sendReminder({{ $request->id }})" title="Send Reminder">
                                                <i class="fas fa-bell"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <div>No recent uploaded requests found</div>
                                    <small>Recent client uploads will appear here</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Recent Activity Log -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-history text-secondary me-2"></i>
                        Recent Activity
                    </h6>
                    <button class="btn btn-outline-secondary btn-sm" onclick="loadMoreActivity()">
                        <i class="fas fa-ellipsis-h"></i> Load More
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($recent_requests ?? [] as $index => $request)
                        @if($index < 5)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1">{{ $request->title }}</h6>
                                    <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-2 text-muted">
                                    Request created for <strong>{{ $request->client->company_name }}</strong>
                                    by {{ $request->creator->name ?? 'System' }}
                                </p>
                                <div class="timeline-actions">
                                    <a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-outline-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-clock fa-3x mb-3"></i>
                            <div>No recent activity</div>
                            <small>Recent activities will appear here</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Enhanced card styles */
.card {
    border-radius: 0.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
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
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

/* Badge improvements */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
}

/* Button improvements */
.btn {
    font-weight: 500;
    transition: all 0.2s;
    border-radius: 0.375rem;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Timeline styling */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    left: -1.5rem;
    top: 0.5rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
    border-left: 3px solid #0d6efd;
}

.timeline-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.timeline-header h6 {
    margin: 0;
    color: #495057;
}

/* Alert list styling */
.list-group-item {
    border: none;
    padding: 0.75rem 0;
}

.list-group-item:not(:last-child) {
    border-bottom: 1px solid #e9ecef;
}

/* Responsive improvements */
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

    .timeline {
        padding-left: 1.5rem;
    }

    .timeline-marker {
        left: -1.25rem;
    }
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Animation for metrics */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card-body h3, .card-body h4 {
    animation: countUp 0.5s ease-out;
}

/* Custom scrollbar for tables */
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

/* Empty state styling */
.fa-inbox, .fa-clock, .fa-user-plus, .fa-building, .fa-project-diagram, .fa-file-alt {
    opacity: 0.5;
}

/* Quick actions grid */
.d-grid .btn {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Status consistency */
.badge.bg-warning {
    color: #000 !important;
}

/* Card header enhancements */
.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #fff !important;
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

    // Auto-refresh dashboard every 5 minutes
    setInterval(refreshDashboard, 300000);

    // Animate metrics on load
    setTimeout(animateMetrics, 500);
});

// Dashboard refresh function
function refreshDashboard() {
    const refreshBtn = document.querySelector('button[onclick="refreshDashboard()"]');
    const originalHtml = refreshBtn.innerHTML;

    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    // Simulate refresh (replace with actual AJAX call if needed)
    setTimeout(function() {
        window.location.reload();
    }, 1000);
}

// Filter functions for different sections
function filterUsersByTime() {
    const filter = document.getElementById('userTimeFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('userTimeFilter', filter);
    window.location.href = url.toString();
}

function filterClientsByTime() {
    const filter = document.getElementById('clientTimeFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('clientTimeFilter', filter);
    window.location.href = url.toString();
}

function filterProjectsByTime() {
    const filter = document.getElementById('projectTimeFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('projectTimeFilter', filter);
    window.location.href = url.toString();
}

function filterRequestsByTime() {
    const filter = document.getElementById('requestTimeFilter').value;
    const url = new URL(window.location.href);
    url.searchParams.set('requestTimeFilter', filter);
    window.location.href = url.toString();
}

// Send reminder function
function sendReminder(requestId) {
    if (confirm('Send reminder to client for this request?')) {
        fetch(`/admin/reminders/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                pbc_request_id: requestId,
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

// Load more activity function
function loadMoreActivity() {
    showAlert('Loading more activities...', 'info');
}

// Animate metrics function
function animateMetrics() {
    const metricNumbers = document.querySelectorAll('.card-body h3, .card-body h4');

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
    alertDiv.innerHTML = `
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

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'r':
                e.preventDefault();
                refreshDashboard();
                break;
            case 'n':
                e.preventDefault();
                window.location.href = '{{ route("admin.pbc-requests.create") }}';
                break;
            case 'i':
                e.preventDefault();
                window.location.href = '{{ route("admin.pbc-requests.import") }}';
                break;
        }
    }
});
</script>
@endsection
