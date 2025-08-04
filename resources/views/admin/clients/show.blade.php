@extends('layouts.app')
@section('title', $client->company_name . ' - Client Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">{{ $client->company_name }}</h1>
        <p class="text-muted mb-0">Client information and project overview</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.projects.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Job
        </a>
        @if(auth()->user()->canManageClients())
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit Client
            </a>
        @endif
        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Clients
        </a>
    </div>
</div>

<!-- Client Information Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="fas fa-building text-primary me-2"></i>
            Client Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px; font-size: 20px; font-weight: 600;">
                        {{ strtoupper(substr($client->company_name, 0, 2)) }}
                    </div>
                    <div>
                        <h4 class="mb-1">{{ $client->company_name }}</h4>
                        <p class="text-muted mb-0">Client since {{ $client->created_at->format('M d, Y') }}</p>
                    </div>
                </div>

                <div class="mb-3">
                    <strong class="text-muted">Address:</strong><br>
                    <span>{{ $client->address ?: '123 HV Dela Costa Salcedo Village Makati' }}</span>
                </div>

                <div class="mb-3">
                    <strong class="text-muted">Contact Person:</strong><br>
                    <span>{{ $client->contact_person ?: 'Juan Dela Cruz' }}</span>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <strong class="text-muted">Email Address:</strong><br>
                    <span>{{ $client->user->email ?: 'JDC@gmail.com' }}</span>
                </div>

                <div class="mb-3">
                    <strong class="text-muted">Contact Number:</strong><br>
                    <span>{{ $client->phone ?: '0919-000-0000' }}</span>
                </div>

                <div class="mb-3">
                    <strong class="text-muted">SEC Registration:</strong><br>
                    <span>{{ $client->sec_registration ?? 'ABC-000-0000' }}</span>
                </div>

                <div class="mb-3">
                    <strong class="text-muted">Tax Identification Number:</strong><br>
                    <span>{{ $client->tin ?? '000-000-001' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Projects Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-briefcase text-primary me-2"></i>
                Client Projects ({{ $client->projects->count() }})
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
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">Job ID</th>
                        <th class="px-4 py-3">Project Details</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3">Partner</th>
                        <th class="px-4 py-3">Pending</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3">Actions</th>
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
                            <span class="fw-bold text-primary">{{ $project->job_id ?? sprintf('1-01-%03d', $project->id) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px; font-weight: 600;">
                                    @if($project->engagement_type === 'audit')
                                        <i class="fas fa-search"></i>
                                    @elseif($project->engagement_type === 'accounting')
                                        <i class="fas fa-calculator"></i>
                                    @elseif($project->engagement_type === 'tax')
                                        <i class="fas fa-file-invoice"></i>
                                    @else
                                        <i class="fas fa-briefcase"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-medium">{{ $project->engagement_name ?? $project->name }}</div>
                                    <small class="text-muted">{{ ucfirst($project->status) }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $engagementColors = [
                                    'audit' => 'primary',
                                    'accounting' => 'success',
                                    'tax' => 'warning',
                                    'special_engagement' => 'info',
                                    'others' => 'secondary'
                                ];
                                $color = $engagementColors[$project->engagement_type] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">
                                {{ ucfirst($project->engagement_type ?? 'audit') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-muted small">
                                @if($project->engagement_period_start && $project->engagement_period_end)
                                    {{ $project->engagement_period_start->format('Y') }}
                                @else
                                    {{ $project->created_at->format('Y') }}
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="fw-medium">{{ $project->engagementPartner->name ?? 'EYM' }}</div>
                                <small class="text-muted">Partner</small>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                @if($pendingRequests > 0)
                                    <span class="badge bg-warning">{{ $pendingRequests }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                @if($submittedRequests > 0)
                                    <span class="badge bg-success">{{ $submittedRequests }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}" class="btn btn-primary btn-sm" title="View PBC Requests">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-warning btn-sm" title="Edit Project">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteProject({{ $project->id }})" title="Delete Project">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-briefcase fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No projects found</div>
                                <small>This client doesn't have any projects yet</small>
                                <div class="mt-3">
                                    <a href="{{ route('admin.projects.create', ['client_id' => $client->id]) }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create First Project
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

/* Avatar styling */
.rounded-circle {
    border-radius: 50% !important;
}

/* Form styling */
.form-select {
    border-radius: 0.375rem;
}

.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Empty state styling */
.fa-briefcase {
    color: #6c757d;
}

/* Client info styling */
.card-body .mb-3:last-child {
    margin-bottom: 0 !important;
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

    .rounded-circle {
        width: 50px !important;
        height: 50px !important;
        font-size: 16px !important;
    }
}

/* Animation for stats */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.text-center h5, .card-body h4 {
    animation: countUp 0.5s ease-out;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    const tbody = document.querySelector('.project-row')?.closest('tbody');

    if (tbody) {
        // Remove existing no-results message
        const existingMessage = tbody.querySelector('.no-results');
        if (existingMessage) {
            existingMessage.remove();
        }

        if (visibleRows.length === 0 && filter !== '') {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results';
            noResultsRow.innerHTML = `
                <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p class="mb-0">No projects found with status "${filter}"</p>
                    </div>
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    }
}

// Delete project function
function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone and will also delete all associated PBC requests.')) {
        // Show loading state
        const deleteBtn = event.target.closest('button');
        const originalHtml = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        // Create and submit a form to delete the project
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/projects/${projectId}`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

// Show alert function
function showAlert(message, type) {
    // Remove any existing alerts first
    const existingAlerts = document.querySelectorAll('.alert.position-fixed');
    existingAlerts.forEach(alert => alert.remove());

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

// Animate statistics
function animateStats() {
    const statNumbers = document.querySelectorAll('.text-center h5, .card-body h4');

    statNumbers.forEach(function(stat) {
        const text = stat.textContent.trim();
        const finalValue = parseInt(text);

        if (isNaN(finalValue)) return;

        let currentValue = 0;
        const increment = Math.ceil(finalValue / 20);

        const timer = setInterval(function() {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(timer);
            } else {
                stat.textContent = currentValue;
            }
        }, 50);
    });
}

// Auto-submit filter form when selection changes
document.getElementById('projectStatusFilter')?.addEventListener('change', filterProjects);

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
        if (filterSelect && filterSelect.value !== '') {
            filterSelect.value = '';
            filterProjects();
        }
    }
});
</script>
@endsection
