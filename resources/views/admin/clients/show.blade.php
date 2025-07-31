@extends('layouts.app')
@section('title', $client->company_name . ' - Client Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $client->company_name }}</h1>
        <p class="text-muted mb-0">Client information and project overview</p>
    </div>
    <div class="d-flex gap-2">
        <!-- UPDATED: Create Job Button - Key feature from wireframe -->
        <a href="{{ route('admin.projects.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Job
        </a>
        @if(auth()->user()->canManageClients())
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Client
            </a>
        @endif
        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Clients
        </a>
    </div>
</div>

<!-- Client Information Card -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">

                        <div class="mb-3">
                            <strong>Address:</strong><br>
                            {{ $client->address ?: '123 HV Dela Costa Salcedo Village Makati' }}
                        </div>

                        <div class="mb-3">
                            <strong>Contact Person:</strong> {{ $client->contact_person ?: 'Juan Dela Cruz' }}<br>
                            <strong>Email Address:</strong> {{ $client->user->email ?: 'JDC@gmail.com' }}<br>
                            <strong>Contact Number:</strong> {{ $client->phone ?: '0919-000-0000' }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>SEC Registration:</strong> {{ $client->sec_registration ?? 'ABC-000-0000' }}<br>
                            <strong>Tax Identification Number:</strong> {{ $client->tin ?? '000-000-001' }}
                        </div>

                        <div class="mb-3">
                            <strong>Client Since:</strong> {{ $client->created_at->format('M d, Y') }}<br>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- UPDATED: Projects Table - Now matches wireframe exactly -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-briefcase text-primary me-2"></i>
                Client Projects
            </h5>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="projectStatusFilter" onchange="filterProjects()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="on_hold">On Hold</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">JOB ID</th>
                        <th class="py-3">Type of Engagement</th>
                        <th class="py-3">Engagement Name</th>
                        <th class="py-3">Engagement Period</th>
                        <th class="py-3">Engagement Partner</th>
                        <th class="py-3">Pending Request</th>
                        <th class="py-3">Submitted Request</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->projects as $project)
                    @php
                        $pendingRequests = $project->pbcRequests()->where('status', 'pending')->count();
                        $submittedRequests = $project->pbcRequests()->where('status', 'in_progress')->count();
                    @endphp
                    <tr class="project-row" data-status="{{ $project->status }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.projects.show', $project) }}" class="text-decoration-none fw-bold">
                                {{ $project->job_id ?? sprintf('1-01-%03d', $project->id) }}
                            </a>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-primary">
                                {{ ucfirst($project->engagement_type ?? 'audit') }}
                            </span>
                        </td>
                        <td class="py-3">{{ $project->engagement_name ?? $project->name }}</td>
                        <td class="py-3">
                            @if($project->engagement_period_start && $project->engagement_period_end)
                                {{ $project->engagement_period_start->format('Y') }}
                            @else
                                {{ $project->created_at->format('Y') }}
                            @endif
                        </td>
                        <td class="py-3">
                            {{ $project->engagementPartner->name ?? 'EYM' }}
                        </td>
                        <td class="py-3">
                            <div class="text-center">
                                @if($pendingRequests > 0)
                                    <span class="badge bg-warning">{{ $pendingRequests }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="text-center">
                                @if($submittedRequests > 0)
                                    <span class="badge bg-success">{{ $submittedRequests }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="btn-group" role="group">
                                <!-- UPDATED: View button now routes to project-specific PBC requests -->
                                <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}"
                                   class="btn btn-outline-primary btn-sm"
                                   title="View PBC Requests">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="{{ route('admin.projects.edit', $project) }}"
                                   class="btn btn-outline-warning btn-sm"
                                   title="Edit Project">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="deleteProject({{ $project->id }})"
                                        title="Delete Project">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-briefcase fa-3x mb-3"></i>
                                <h5>No Projects Found</h5>
                                <p>This client doesn't have any projects yet.</p>
                                <div class="mt-3">
                                    <a href="{{ route('admin.projects.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create First Project
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


@endsection

@section('styles')
<style>
/* Enhanced card styles */
.card {
    border-radius: 0.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}



/* Table improvements */
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
    font-size: 0.9rem;
}



/* Badge improvements */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
}

.badge.bg-primary { background-color: #0d6efd !important; }
.badge.bg-success { background-color: #198754 !important; }
.badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
.badge.bg-danger { background-color: #dc3545 !important; }
.badge.bg-secondary { background-color: #6c757d !important; }

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

/* Progress bar styling */
.progress {
    border-radius: 0.25rem;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 0.25rem;
    transition: width 0.6s ease;
}

/* Quick stats styling */
.border-end {
    border-right: 1px solid #dee2e6 !important;
}

/* Card header improvements */
.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

/* Empty state styling */
.fa-briefcase, .fa-file-alt {
    opacity: 0.5;
    color: #6c757d;
}

/* Contact info styling */
.small {
    font-size: 0.875rem;
}

.small .fas {
    width: 16px;
    text-align: center;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }

    .border-end {
        border-right: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }

    .card-body {
        padding: 1rem;
    }
}

/* Icon consistency */
.fas {
    width: 16px;
    text-align: center;
}

/* Status colors */
.text-primary { color: #0d6efd !important; }
.text-success { color: #198754 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-info { color: #0dcaf0 !important; }

/* Quick actions grid */
.d-grid .btn {
    text-align: left;
    justify-content: flex-start;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Animation for stats */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.text-center h5 {
    animation: countUp 0.5s ease-out;
}


/* Enhanced table striping */
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Enhanced focus states */
.form-select:focus, .btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Card shadow variations */
.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
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

    // Animate statistics on page load
    setTimeout(animateStats, 500);
});

// Filter projects by status
function filterProjects() {
    const filter = document.getElementById('projectStatusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.project-row');

    rows.forEach(row => {
        const status = row.getAttribute('data-status').toLowerCase();
        if (filter === '' || status === filter) {
            row.style.display = '';
            row.style.opacity = '1';
        } else {
            row.style.display = 'none';
            row.style.opacity = '0';
        }
    });

    // Show message if no rows visible
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const tbody = document.querySelector('.project-row').closest('tbody');

    // Remove existing no-results message
    const existingMessage = tbody.querySelector('.no-results');
    if (existingMessage) {
        existingMessage.remove();
    }

    if (visibleRows.length === 0 && filter !== '') {
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results';
        noResultsRow.innerHTML = `
            <td colspan="9" class="text-center py-4">
                <div class="text-muted">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p class="mb-0">No projects found with status "${filter}"</p>
                </div>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    }
}

// Delete project function
function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone and will also delete all associated PBC requests.')) {
        // Show loading state
        const deleteBtn = event.target.closest('button');
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Create and submit a form to delete the project
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/projects/${projectId}`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Send reminder function
function sendReminder(requestId) {
    if (confirm('Send reminder to client for this request?')) {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

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
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.remove('btn-outline-warning');
                btn.classList.add('btn-outline-success');
            } else {
                showAlert('Failed to send reminder: ' + data.message, 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bell"></i>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error sending reminder', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bell"></i>';
        });
    }
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

// Export client data
function exportClientData() {
    window.location.href = `/admin/clients/{{ $client->id }}/export`;
}

// Print client info
function printClientInfo() {
    window.print();
}

// Animate statistics
function animateStats() {
    const statNumbers = document.querySelectorAll('.text-center h5');

    statNumbers.forEach(function(stat) {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = finalValue / 20;

        const timer = setInterval(function() {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(currentValue);
            }
        }, 50);
    });
}

// Real-time updates (optional)
function checkForUpdates() {
    // This could fetch recent changes and update the UI
    fetch(`/admin/clients/{{ $client->id }}/recent-activity`)
        .then(response => response.json())
        .then(data => {
            if (data.hasUpdates) {
                // Show notification or update parts of the page
                const notification = document.createElement('div');
                notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
                notification.innerHTML = `
                    New activity detected. <a href="#" onclick="window.location.reload()">Refresh page</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(notification);
            }
        })
        .catch(error => console.log('Update check failed:', error));
}

// Check for updates every 2 minutes
setInterval(checkForUpdates, 120000);

// Enhanced search functionality (if needed)
function quickSearch(query) {
    const rows = document.querySelectorAll('.project-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query.toLowerCase()) || query === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N for new project
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = '{{ route("admin.projects.create", ["client_id" => $client->id]) }}';
    }

    // ESC to clear filters
    if (e.key === 'Escape') {
        const filterSelect = document.getElementById('projectStatusFilter');
        if (filterSelect.value !== '') {
            filterSelect.value = '';
            filterProjects();
        }
    }
});
</script>
@endsection
