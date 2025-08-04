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
                        @php
                            $clientInitial = $client->getClientInitial();
                        @endphp
                        @if($clientInitial)
                            <small class="badge bg-light text-dark">Code: {{ $clientInitial }}</small>
                        @endif
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

                @if($client->year_engaged)
                <div class="mb-3">
                    <strong class="text-muted">Year Engaged:</strong><br>
                    <span class="badge bg-info">{{ $client->year_engaged }}</span>
                </div>
                @endif
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
            <table class="table mb-0">
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

                        // Get job ID breakdown for enhanced display
                        $jobIdBreakdown = $project->getJobIdBreakdownAttribute();
                    @endphp
                    <tr class="project-row" data-status="{{ $project->status }}">
                        <td class="px-4 py-3">
                            <div class="job-id-container">
                                <span class="fw-bold text-primary d-block">{{ $project->job_id ?? 'Not Generated' }}</span>
                                @if($jobIdBreakdown)
                                    <small class="text-muted job-id-breakdown" title="Format: {{ $jobIdBreakdown['client_initial'] }}-{{ substr($jobIdBreakdown['year_engaged'], -2) }}-{{ $jobIdBreakdown['series'] }}-{{ $jobIdBreakdown['job_type_code'] }}-{{ substr($jobIdBreakdown['year_of_job'], -2) }}">
                                        <span class="breakdown-part breakdown-client">{{ $jobIdBreakdown['client_initial'] }}</span>-<span class="breakdown-part breakdown-year">{{ substr($jobIdBreakdown['year_engaged'], -2) }}</span>-<span class="breakdown-part breakdown-series">{{ $jobIdBreakdown['series'] }}</span>-<span class="breakdown-part breakdown-type">{{ $jobIdBreakdown['job_type_code'] }}</span>-<span class="breakdown-part breakdown-job-year">{{ substr($jobIdBreakdown['year_of_job'], -2) }}</span>
                                    </small>
                                @else
                                    <small class="text-danger">Job ID needs regeneration</small>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="fw-medium">{{ $project->engagement_name ?? $project->name }}</div>
                                <small class="text-muted d-flex align-items-center">
                                    <span class="badge badge-outline-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : 'warning') }} me-1">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                    @if($jobIdBreakdown && $jobIdBreakdown['year_of_job'] != date('Y'))
                                        <span class="badge bg-info ms-1">FY {{ $jobIdBreakdown['year_of_job'] }}</span>
                                    @endif
                                </small>
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
                            <div>
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst($project->engagement_type ?? 'audit') }}
                                </span>
                                @if($jobIdBreakdown)
                                    <br><small class="text-muted">Code: {{ $jobIdBreakdown['job_type_code'] }}</small>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-muted small">
                                @if($project->engagement_period_start && $project->engagement_period_end)
                                    {{ $project->engagement_period_start->format('Y') }}-{{ $project->engagement_period_end->format('Y') }}
                                    <br><span class="text-muted">{{ $project->engagement_period_start->format('M Y') }} - {{ $project->engagement_period_end->format('M Y') }}</span>
                                @elseif($jobIdBreakdown && $jobIdBreakdown['year_of_job'])
                                    {{ $jobIdBreakdown['year_of_job'] }}
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

.badge-outline-success {
    color: #198754;
    border: 1px solid #198754;
    background-color: transparent;
}

.badge-outline-primary {
    color: #0d6efd;
    border: 1px solid #0d6efd;
    background-color: transparent;
}

.badge-outline-warning {
    color: #ffc107;
    border: 1px solid #ffc107;
    background-color: transparent;
}

/* Job ID enhanced styling */
.job-id-container {
    position: relative;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
}


.job-id-breakdown {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    display: block;
    margin-top: 0.25rem;
}

.breakdown-part {
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    margin: 0 0.025rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.breakdown-client {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.breakdown-year {
    background-color: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.breakdown-series {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.breakdown-type {
    background-color: rgba(13, 202, 240, 0.1);
    color: #0dcaf0;
}

.breakdown-job-year {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

/* Copy to clipboard animation */
.copy-success {
    animation: copy-flash 0.5s ease-in-out;
}

@keyframes copy-flash {
    0% { background-color: transparent; }
    50% { background-color: rgba(25, 135, 84, 0.2); }
    100% { background-color: transparent; }
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

/* Tooltip styling */
.job-id-tooltip {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    font-size: 0.75rem;
    max-width: 200px;
}

.tooltip-arrow {
    pointer-events: none;
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

    .breakdown-part {
        padding: 0.1rem 0.2rem;
        font-size: 0.7rem;
    }

    .job-id-breakdown {
        font-size: 0.7rem;
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

/* Enhanced accessibility */
[role="button"] {
    cursor: pointer;
}

[role="button"]:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

/* Screen reader only content */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Performance improvements */
.job-id-container,
.breakdown-part {
    will-change: transform;
}

/* Print styles */
@media print {
    .btn-group,
    .card-header .d-flex > div:last-child {
        display: none !important;
    }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeJobIdInteractions();
    initializeJobIdTooltips();
    initializeAccessibilityFeatures();

    // Animate statistics on page load
    setTimeout(animateStats, 500);

    console.log('Client show page initialized successfully');
});

// Initialize Job ID interactions
function initializeJobIdInteractions() {
    const jobIdContainers = document.querySelectorAll('.job-id-container');

    jobIdContainers.forEach(container => {
        // Click to copy functionality
        container.addEventListener('click', function() {
            const jobId = this.querySelector('.fw-bold').textContent.trim();
            if (jobId && jobId !== 'Not Generated') {
                copyToClipboard(jobId);
            }
        });

        // Add accessibility attributes
        container.setAttribute('role', 'button');
        container.setAttribute('tabindex', '0');
        container.setAttribute('aria-label', 'Click to copy Job ID');
        container.title = 'Click to copy Job ID';

        // Keyboard support
        container.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Individual breakdown part interactions
    const breakdownParts = document.querySelectorAll('.breakdown-part');
    breakdownParts.forEach(part => {
        part.addEventListener('click', function(e) {
            e.stopPropagation();
            const partValue = this.textContent.trim();
            const partClass = this.className.split(' ').find(cls => cls.startsWith('breakdown-'));

            let partName = '';
            switch(partClass) {
                case 'breakdown-client':
                    partName = 'Client Code';
                    break;
                case 'breakdown-year':
                    partName = 'Year Engaged';
                    break;
                case 'breakdown-series':
                    partName = 'Series Number';
                    break;
                case 'breakdown-type':
                    partName = 'Engagement Type';
                    break;
                case 'breakdown-job-year':
                    partName = 'Job Year';
                    break;
                default:
                    partName = 'Job ID Component';
            }

            showAlert(`${partName}: ${partValue}`, 'info');
        });
    });
}

// Initialize enhanced tooltips for job ID breakdown
function initializeJobIdTooltips() {
    const jobIdElements = document.querySelectorAll('.job-id-breakdown');

    jobIdElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const parts = this.textContent.trim().replace(/\s+/g, '').split('-');
            if (parts.length === 5) {
                const tooltip = document.createElement('div');
                tooltip.className = 'job-id-tooltip position-absolute bg-dark text-white p-2 rounded small';
                tooltip.style.cssText = 'z-index: 1000; top: -80px; left: 50%; transform: translateX(-50%); white-space: nowrap; pointer-events: none;';
                tooltip.innerHTML = `
                    <div><strong>${parts[0]}:</strong> Client Code</div>
                    <div><strong>${parts[1]}:</strong> Year Engaged (20${parts[1]})</div>
                    <div><strong>${parts[2]}:</strong> Series Number</div>
                    <div><strong>${parts[3]}:</strong> Engagement Type</div>
                    <div><strong>${parts[4]}:</strong> Job Year (20${parts[4]})</div>
                `;

                this.style.position = 'relative';
                this.appendChild(tooltip);

                // Add arrow
                const arrow = document.createElement('div');
                arrow.className = 'tooltip-arrow';
                arrow.style.cssText = 'position: absolute; top: 100%; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 5px solid #333;';
                tooltip.appendChild(arrow);
            }
        });

        element.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.job-id-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
}

// Initialize accessibility features
function initializeAccessibilityFeatures() {
    // Add ARIA labels for project rows
    const projectRows = document.querySelectorAll('.project-row');
    projectRows.forEach((row, index) => {
        row.setAttribute('tabindex', '0');
        row.setAttribute('aria-label', `Project row ${index + 1}`);

        row.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const viewBtn = this.querySelector('.btn-primary');
                if (viewBtn) {
                    viewBtn.click();
                }
            }
        });
    });

    // Enhance filter select accessibility
    const filterSelect = document.getElementById('projectStatusFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            // Announce filter change to screen readers
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.className = 'sr-only';
            announcement.textContent = `Filter applied: ${this.options[this.selectedIndex].text}`;
            document.body.appendChild(announcement);

            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 1000);
        });
    }
}

// Copy to clipboard function
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert(`Job ID "${text}" copied to clipboard!`, 'success');

            // Add visual feedback
            const jobIdContainers = document.querySelectorAll('.job-id-container');
            jobIdContainers.forEach(container => {
                if (container.querySelector('.fw-bold').textContent.trim() === text) {
                    container.classList.add('copy-success');
                    setTimeout(() => {
                        container.classList.remove('copy-success');
                    }, 500);
                }
            });
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showAlert('Failed to copy Job ID to clipboard', 'warning');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.cssText = 'position: fixed; top: -1000px; left: -1000px;';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showAlert(`Job ID "${text}" copied to clipboard!`, 'success');
        } catch (err) {
            console.error('Fallback copy failed: ', err);
            showAlert('Failed to copy Job ID to clipboard', 'warning');
        }
        document.body.removeChild(textArea);
    }
}

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

// Enhanced project statistics
function updateProjectStats() {
    const projects = document.querySelectorAll('.project-row');
    const stats = {
        total: projects.length,
        active: 0,
        completed: 0,
        onHold: 0,
        cancelled: 0
    };

    projects.forEach(project => {
        const status = project.getAttribute('data-status');
        switch(status) {
            case 'active':
                stats.active++;
                break;
            case 'completed':
                stats.completed++;
                break;
            case 'on_hold':
                stats.onHold++;
                break;
            case 'cancelled':
                stats.cancelled++;
                break;
        }
    });

    console.log('Project Statistics:', stats);
    return stats;
}

// Export project list functionality
function exportProjectList() {
    const projects = [];
    const projectRows = document.querySelectorAll('.project-row');

    projectRows.forEach(row => {
        const jobId = row.querySelector('.fw-bold').textContent.trim();
        const projectName = row.querySelector('.fw-medium').textContent.trim();
        const status = row.getAttribute('data-status');
        const engagementType = row.querySelector('.badge').textContent.trim();

        projects.push({
            jobId,
            projectName,
            status,
            engagementType
        });
    });

    console.log('Exported projects:', projects);
    return projects;
}

// Performance optimization for large project lists
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced search function (if search input exists)
const debouncedSearch = debounce(function(searchTerm) {
    const rows = document.querySelectorAll('.project-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());

        row.style.display = matches ? '' : 'none';
    });
}, 300);

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

    // Ctrl+C on focused job ID to copy
    if (e.ctrlKey && e.key === 'c') {
        const activeElement = document.activeElement;
        if (activeElement && activeElement.classList.contains('job-id-container')) {
            e.preventDefault();
            const jobId = activeElement.querySelector('.fw-bold').textContent.trim();
            copyToClipboard(jobId);
        }
    }

    // Arrow key navigation for project rows
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const activeElement = document.activeElement;
        if (activeElement && activeElement.classList.contains('project-row')) {
            e.preventDefault();
            const rows = Array.from(document.querySelectorAll('.project-row'));
            const currentIndex = rows.indexOf(activeElement);

            if (e.key === 'ArrowDown' && currentIndex < rows.length - 1) {
                rows[currentIndex + 1].focus();
            } else if (e.key === 'ArrowUp' && currentIndex > 0) {
                rows[currentIndex - 1].focus();
            }
        }
    }
});

// Auto-refresh functionality (optional - for real-time updates)
function setupAutoRefresh() {
    let idleTimer;
    let isIdle = false;

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        isIdle = false;
        idleTimer = setTimeout(() => {
            isIdle = true;
        }, 300000); // 5 minutes
    }

    // Reset idle timer on user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetIdleTimer, true);
    });

    // Check for updates every 30 seconds when idle
    setInterval(() => {
        if (isIdle && document.visibilityState === 'visible') {
            console.log('Auto-refresh check (idle state)');
            // Could implement auto-refresh logic here
        }
    }, 30000);

    resetIdleTimer();
}

// Initialize additional functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filter form when selection changes
    const filterSelect = document.getElementById('projectStatusFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', filterProjects);
    }

    // Add search functionality if search input exists
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value);
        });
    }

    // Initialize auto-refresh if needed (uncomment to enable)
    // setupAutoRefresh();

    // Add smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Error handling for failed operations
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    showAlert('An error occurred. Please refresh the page if problems persist.', 'danger');
});

// Handle network connectivity changes
window.addEventListener('online', function() {
    showAlert('Connection restored.', 'success');
});

window.addEventListener('offline', function() {
    showAlert('Connection lost. Some features may not work.', 'warning');
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Page became visible - could refresh data here
        console.log('Page became visible');
    } else {
        // Page became hidden - could pause operations here
        console.log('Page became hidden');
    }
});

// Utility function for responsive table handling
function handleTableResponsiveness() {
    const table = document.querySelector('.table-responsive');
    if (table) {
        const isMobile = window.innerWidth < 768;

        if (isMobile) {
            // Add mobile-specific optimizations
            table.style.fontSize = '0.8rem';
        } else {
            // Reset desktop styles
            table.style.fontSize = '';
        }
    }
}

// Handle window resize for responsive optimizations
window.addEventListener('resize', debounce(handleTableResponsiveness, 250));

// Initialize responsive handling
document.addEventListener('DOMContentLoaded', handleTableResponsiveness);

// Context menu for job IDs (right-click functionality)
document.addEventListener('contextmenu', function(e) {
    const jobIdContainer = e.target.closest('.job-id-container');
    if (jobIdContainer) {
        e.preventDefault();

        const jobId = jobIdContainer.querySelector('.fw-bold').textContent.trim();
        if (jobId && jobId !== 'Not Generated') {
            // Create simple context menu
            const contextMenu = document.createElement('div');
            contextMenu.className = 'position-fixed bg-white border rounded shadow-sm p-2';
            contextMenu.style.cssText = `
                z-index: 10000;
                left: ${e.pageX}px;
                top: ${e.pageY}px;
                min-width: 150px;
            `;

            contextMenu.innerHTML = `
                <div class="d-grid gap-1">
                    <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('${jobId}'); this.parentElement.parentElement.remove();">
                        <i class="fas fa-copy me-1"></i>Copy Job ID
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="this.parentElement.parentElement.remove();">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                </div>
            `;

            document.body.appendChild(contextMenu);

            // Remove context menu when clicking elsewhere
            setTimeout(() => {
                document.addEventListener('click', function removeContextMenu() {
                    if (contextMenu.parentNode) {
                        contextMenu.remove();
                    }
                    document.removeEventListener('click', removeContextMenu);
                }, 100);
            });
        }
    }
});

// Advanced tooltip management
class TooltipManager {
    constructor() {
        this.activeTooltips = new Set();
    }

    show(element, content, options = {}) {
        const tooltip = document.createElement('div');
        tooltip.className = 'position-absolute bg-dark text-white p-2 rounded small';
        tooltip.style.cssText = `
            z-index: 1000;
            top: ${options.top || '-60px'};
            left: ${options.left || '50%'};
            transform: translateX(-50%);
            white-space: nowrap;
            pointer-events: none;
        `;
        tooltip.innerHTML = content;

        element.style.position = 'relative';
        element.appendChild(tooltip);
        this.activeTooltips.add(tooltip);

        return tooltip;
    }

    hide(tooltip) {
        if (tooltip && tooltip.parentNode) {
            tooltip.remove();
            this.activeTooltips.delete(tooltip);
        }
    }

    hideAll() {
        this.activeTooltips.forEach(tooltip => {
            if (tooltip.parentNode) {
                tooltip.remove();
            }
        });
        this.activeTooltips.clear();
    }
}

// Initialize tooltip manager
const tooltipManager = new TooltipManager();

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    tooltipManager.hideAll();
});

// Progressive enhancement for older browsers
function checkBrowserSupport() {
    const features = {
        clipboard: !!navigator.clipboard,
        fetch: !!window.fetch,
        localStorage: !!window.localStorage,
        css3: !!window.CSS && !!window.CSS.supports
    };

    if (!features.clipboard) {
        console.warn('Clipboard API not supported - using fallback');
    }

    return features;
}

// Initialize browser compatibility check
document.addEventListener('DOMContentLoaded', function() {
    const browserSupport = checkBrowserSupport();
    console.log('Browser support:', browserSupport);
});

console.log('Client details page script loaded successfully');
</script>
@endsection
