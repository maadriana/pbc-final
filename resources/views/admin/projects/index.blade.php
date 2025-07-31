@extends('layouts.app')
@section('title', 'Project Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">PROJECT Management</h1>
        <p class="text-muted mb-0">Manage all projects and engagements</p>
    </div>
</div>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="mb-0">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small text-muted fw-bold">Search Projects</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by project name, job ID, client..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Filter by Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Projects Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">JOB ID</th>
                        <th class="py-3">Type of Engagement</th>
                        <th class="py-3">Engagement Name</th>
                        <th class="py-3">Client Name</th>
                        <th class="py-3">Engagement Period</th>
                        <th class="py-3">Engagement Partner</th>
                        <th class="py-3">Pending Request</th>
                        <th class="py-3">Submitted Request</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    @php
                        // Calculate request statistics
                        $totalRequests = $project->pbcRequests->sum(function($request) {
                            return $request->items->count();
                        });

                        $pendingRequests = $project->pbcRequests->sum(function($request) {
                            return $request->items->filter(function($item) {
                                return $item->getCurrentStatus() === 'pending';
                            })->count();
                        });

                        $submittedRequests = $project->pbcRequests->sum(function($request) {
                            return $request->items->filter(function($item) {
                                return in_array($item->getCurrentStatus(), ['uploaded', 'approved']);
                            })->count();
                        });

                        // Get engagement period
                        $engagementPeriod = '';
                        if ($project->engagement_period_start && $project->engagement_period_end) {
                            $engagementPeriod = $project->engagement_period_start->format('Y') . '-' . $project->engagement_period_end->format('Y');
                        } elseif ($project->engagement_period_start) {
                            $engagementPeriod = $project->engagement_period_start->format('Y');
                        } else {
                            $engagementPeriod = $project->created_at->format('Y');
                        }
                    @endphp
                    <tr>
                        <!-- Job ID -->
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="project-icon me-2">
                                    @if($project->engagement_type === 'audit')
                                        <i class="fas fa-search-dollar text-primary"></i>
                                    @elseif($project->engagement_type === 'accounting')
                                        <i class="fas fa-calculator text-success"></i>
                                    @elseif($project->engagement_type === 'tax')
                                        <i class="fas fa-file-invoice-dollar text-warning"></i>
                                    @else
                                        <i class="fas fa-briefcase text-info"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold">
                                        <a href="{{ route('admin.projects.show', $project) }}" class="text-decoration-none">
                                            {{ $project->job_id ?? sprintf('PRJ-%05d', $project->id) }}
                                        </a>
                                    </div>
                                    <small class="text-muted">{{ ucfirst($project->status) }}</small>
                                </div>
                            </div>
                        </td>

                        <!-- Type of Engagement -->
                        <td class="py-3">
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
                                {{ ucfirst(str_replace('_', ' ', $project->engagement_type)) }}
                            </span>
                        </td>

                        <!-- Engagement Name -->
                        <td class="py-3">
                            <div class="text-truncate" style="max-width: 200px;" title="{{ $project->engagement_name ?? $project->name }}">
                                <div class="fw-semibold">{{ $project->engagement_name ?? $project->name }}</div>
                                @if($project->description)
                                    <small class="text-muted">{{ Str::limit($project->description, 40) }}</small>
                                @endif
                            </div>
                        </td>

                        <!-- Client Name -->
                        <td class="py-3">
                            @if($project->client)
                                <div class="fw-semibold">{{ $project->client->company_name }}</div>
                                <small class="text-muted">{{ $project->client->contact_person ?? 'No contact' }}</small>
                            @else
                                <span class="text-muted">No client assigned</span>
                            @endif
                        </td>

                        <!-- Engagement Period -->
                        <td class="py-3">
                            <div class="fw-semibold">{{ $engagementPeriod }}</div>
                            @if($project->engagement_period_start && $project->engagement_period_end)
                                <small class="text-muted">
                                    {{ $project->engagement_period_start->format('M Y') }} - {{ $project->engagement_period_end->format('M Y') }}
                                </small>
                            @endif
                        </td>

                        <!-- Engagement Partner -->
                        <td class="py-3">
                            <div class="fw-semibold">{{ $project->engagementPartner->name ?? 'EYM' }}</div>
                            <small class="text-muted">{{ $project->manager->name ?? 'MNGR 1' }}</small>
                        </td>

                        <!-- Pending Request -->
                        <td class="py-3">
                            <div class="text-center">
                                @if($pendingRequests > 0)
                                    <span class="badge bg-warning fs-6">{{ $pendingRequests }}</span>
                                @else
                                    <span class="badge bg-light text-dark fs-6">0</span>
                                @endif
                            </div>
                        </td>

                        <!-- Submitted Request -->
                        <td class="py-3">
                            <div class="text-center">
                                @if($submittedRequests > 0)
                                    <span class="badge bg-success fs-6">{{ $submittedRequests }}</span>
                                @else
                                    <span class="badge bg-light text-dark fs-6">0</span>
                                @endif
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-3">
                            <div class="btn-group" role="group">
                                <!-- View Button -->
                                <a href="{{ route('admin.projects.show', $project) }}"
                                   class="btn btn-sm btn-success"
                                   title="View Project">
                                    View
                                </a>

                                <!-- Edit Button -->
                                @if(auth()->user()->canCreateProjects())
                                    <a href="{{ route('admin.projects.edit', $project) }}"
                                       class="btn btn-sm btn-warning"
                                       title="Edit Project">
                                        Edit
                                    </a>
                                @endif

                                <!-- Delete Button -->
                                @if(auth()->user()->isSystemAdmin())
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal{{ $project->id }}"
                                            title="Delete Project">
                                        Delete
                                    </button>
                                @endif
                            </div>

                            <!-- Delete Confirmation Modal -->
                            @if(auth()->user()->isSystemAdmin())
                            <div class="modal fade" id="deleteModal{{ $project->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                                Delete Project
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-danger">
                                                <strong>Warning:</strong> This action cannot be undone!
                                            </div>
                                            <p>Are you sure you want to delete this project?</p>
                                            <div class="bg-light p-3 rounded">
                                                <strong>Project Details:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li><strong>Job ID:</strong> {{ $project->job_id ?? 'N/A' }}</li>
                                                    <li><strong>Name:</strong> {{ $project->engagement_name ?? $project->name }}</li>
                                                    <li><strong>Client:</strong> {{ $project->client->company_name ?? 'No client' }}</li>
                                                    <li><strong>Type:</strong> {{ ucfirst($project->engagement_type) }}</li>
                                                    <li><strong>Total Requests:</strong> {{ $totalRequests }}</li>
                                                    <li><strong>Status:</strong> {{ ucfirst($project->status) }}</li>
                                                </ul>
                                            </div>
                                            @if($totalRequests > 0)
                                                <div class="alert alert-warning mt-3">
                                                    <strong>Note:</strong> This project has {{ $totalRequests }} PBC request item(s) that will also be deleted.
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Delete Project
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-briefcase fa-3x mb-3"></i>
                                <h5>No Projects Found</h5>
                                <p>No projects match your current search criteria.</p>
                                @if(auth()->user()->canCreateProjects())
                                    <div class="mt-3">
                                        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create First Project
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
@if($projects->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $projects->appends(request()->query())->links() }}
</div>
@endif


@endsection

@section('styles')
<style>
/* Table styling improvements */
.table {
    font-size: 0.9rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f8f9fa;
}



/* Project icon styling */
.project-icon .fas {
    font-size: 1.25rem;
    width: 20px;
    text-align: center;
}

/* Badge improvements */
.badge {
    font-size: 0.75em;
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.badge.fs-6 {
    font-size: 1rem !important;
    padding: 0.5rem 0.75rem;
    min-width: 2rem;
}

/* Button group styling */
.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
    font-size: 0.875rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* Action button colors */
.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}


.card-body {
    padding: 1rem;
}

/* Search form styling */
.form-label.small {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

/* Text truncation */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Statistics cards */
.card-body.py-3 {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
}

.card-body h4 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.card-body .fas {
    opacity: 0.8;
}

/* Modal improvements */
.modal-content {
    border-radius: 0.5rem;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
}

/* Alert styling in modals */
.modal-body .alert {
    border-radius: 0.375rem;
}

/* Empty state styling */
.fa-briefcase {
    color: #6c757d;
    opacity: 0.5;
}

/* Status badge colors */
.bg-primary { background-color: #007bff !important; }
.bg-success { background-color: #28a745 !important; }
.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-secondary { background-color: #6c757d !important; }

/* Engagement type specific colors */
.badge.bg-primary { background-color: #007bff !important; } /* Audit */
.badge.bg-success { background-color: #28a745 !important; } /* Accounting */
.badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; } /* Tax */
.badge.bg-info { background-color: #17a2b8 !important; } /* Special */
.badge.bg-secondary { background-color: #6c757d !important; } /* Others */

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .text-truncate {
        max-width: 120px !important;
    }

    .card-body h4 {
        font-size: 1.25rem;
    }

    .project-icon {
        display: none;
    }
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}


/* Form control improvements */
.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Icon alignments */
.fas {
    width: 14px;
    text-align: center;
}

/* Project status indicators */
.fw-bold a {
    color: #007bff;
    text-decoration: none;
}



/* Badge animations for pending/submitted counts */
.badge.bg-warning {
    animation: pulse-warning 2s infinite;
}

@keyframes pulse-warning {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}

.badge.bg-success {
    animation: pulse-success 3s infinite;
}

@keyframes pulse-success {
    0% { opacity: 1; }
    50% { opacity: 0.9; }
    100% { opacity: 1; }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enhanced search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Implement live search highlighting
                highlightSearchResults(this.value);
            }, 500);
        });
    }

    // Button loading states
    document.querySelectorAll('.btn-group .btn').forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit' && !this.disabled) {
                this.classList.add('loading');
                this.disabled = true;

                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 3000);
            }
        });
    });

    // Row click enhancement (excluding buttons)
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('.btn-group') && !e.target.closest('button')) {
                const viewBtn = this.querySelector('.btn-success');
                if (viewBtn) {
                    window.location.href = viewBtn.href;
                }
            }
        });
    });

    // Auto-refresh for statistics every 5 minutes
    setInterval(function() {
        const pendingBadges = document.querySelectorAll('.badge.bg-warning');
        if (pendingBadges.length > 0) {
            console.log('Refreshing project statistics...');
            // You can implement partial refresh here
        }
    }, 300000); // 5 minutes

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput?.focus();
        }

        // Escape to clear search
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.value = '';
            clearSearchHighlights();
        }

        // Ctrl/Cmd + N for new project
        if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !e.shiftKey) {
            e.preventDefault();
            const createBtn = document.querySelector('a[href*="projects/create"]');
            if (createBtn) {
                window.location.href = createBtn.href;
            }
        }
    });
});

// Export projects function
function exportProjects() {
    // Get current filters
    const searchTerm = document.querySelector('input[name="search"]')?.value || '';
    const statusFilter = document.querySelector('select[name="status"]')?.value || '';

    // Build export URL with filters
    const params = new URLSearchParams({
        export: 'true',
        search: searchTerm,
        status: statusFilter
    });

    // Create temporary link for download
    const exportUrl = `/admin/projects/export?${params.toString()}`;

    // Show loading state
    const exportBtn = event.target.closest('button');
    const originalText = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    exportBtn.disabled = true;

    // Simulate export (replace with actual implementation)
    setTimeout(() => {
        exportBtn.innerHTML = originalText;
        exportBtn.disabled = false;

        // For now, just show a message
        showAlert('Export functionality will be implemented soon', 'info');

        // Uncomment when export is implemented:
        // window.location.href = exportUrl;
    }, 2000);
}

// Highlight search results
function highlightSearchResults(searchTerm) {
    clearSearchHighlights();

    if (!searchTerm.trim()) return;

    const rows = document.querySelectorAll('.table tbody tr:not(.empty-state)');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let hasMatch = false;

        cells.forEach(cell => {
            const text = cell.textContent.toLowerCase();
            if (text.includes(searchTerm.toLowerCase())) {
                hasMatch = true;
                // Highlight the matching text
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                const html = cell.innerHTML;
                const highlightedHtml = html.replace(regex, '<mark>$1</mark>');
                if (html !== highlightedHtml) {
                    cell.innerHTML = highlightedHtml;
                }
            }
        });

        // Show/hide rows based on search match
        if (hasMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Clear search highlights
function clearSearchHighlights() {
    const marks = document.querySelectorAll('mark');
    marks.forEach(mark => {
        mark.outerHTML = mark.innerHTML;
    });

    const rows = document.querySelectorAll('.table tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Filter by engagement type
function filterByEngagementType(type) {
    const rows = document.querySelectorAll('.table tbody tr:not(.empty-state)');

    rows.forEach(row => {
        const engagementBadge = row.querySelector('.badge');
        const badgeText = engagementBadge ? engagementBadge.textContent.toLowerCase() : '';

        if (type === 'all' || badgeText.includes(type.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Filter by status
function filterByStatus(status) {
    const rows = document.querySelectorAll('.table tbody tr:not(.empty-state)');

    rows.forEach(row => {
        const statusText = row.querySelector('small.text-muted')?.textContent.toLowerCase() || '';

        if (status === 'all' || statusText.includes(status.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Bulk actions
function bulkDeleteProjects() {
    const selectedProjects = document.querySelectorAll('input[name="project_ids[]"]:checked');

    if (selectedProjects.length === 0) {
        showAlert('Please select at least one project to delete.', 'warning');
        return;
    }

    if (confirm(`Are you sure you want to delete ${selectedProjects.length} selected project(s)? This action cannot be undone.`)) {
        // Implementation for bulk delete
        showAlert('Bulk delete functionality not implemented yet', 'warning');
    }
}

function bulkUpdateStatus(newStatus) {
    const selectedProjects = document.querySelectorAll('input[name="project_ids[]"]:checked');

    if (selectedProjects.length === 0) {
        showAlert('Please select at least one project to update.', 'warning');
        return;
    }

    if (confirm(`Are you sure you want to change the status of ${selectedProjects.length} selected project(s) to "${newStatus}"?`)) {
        // Implementation for bulk status update
        showAlert('Bulk status update functionality not implemented yet', 'warning');
    }
}

// Statistics refresh
function refreshStatistics() {
    console.log('Refreshing project statistics...');

    // Get current counts from visible rows
    const visibleRows = document.querySelectorAll('.table tbody tr:not([style*="display: none"]):not(.empty-state)');

    const stats = {
        total: visibleRows.length,
        active: 0,
        completed: 0,
        onHold: 0,
        audit: 0,
        accounting: 0
    };

    visibleRows.forEach(row => {
        const statusText = row.querySelector('small.text-muted')?.textContent.toLowerCase() || '';
        const engagementText = row.querySelector('.badge')?.textContent.toLowerCase() || '';

        if (statusText.includes('active')) stats.active++;
        if (statusText.includes('completed')) stats.completed++;
        if (statusText.includes('hold')) stats.onHold++;
        if (engagementText.includes('audit')) stats.audit++;
        if (engagementText.includes('accounting')) stats.accounting++;
    });

    // Update statistics cards (this is a basic implementation)
    console.log('Statistics:', stats);
}

// Advanced search functionality
function advancedSearch() {
    const searchModal = new bootstrap.Modal(document.getElementById('advancedSearchModal'));
    searchModal.show();
}

// Project quick actions
function quickCreateProject() {
    const createUrl = document.querySelector('a[href*="projects/create"]')?.href;
    if (createUrl) {
        window.location.href = createUrl;
    }
}

function quickViewProject(projectId) {
    const viewUrl = `/admin/projects/${projectId}`;
    window.location.href = viewUrl;
}

// Auto-save search preferences
function saveSearchPreferences() {
    const searchTerm = document.querySelector('input[name="search"]')?.value || '';
    const statusFilter = document.querySelector('select[name="status"]')?.value || '';

    const preferences = {
        search: searchTerm,
        status: statusFilter,
        timestamp: Date.now()
    };

    localStorage.setItem('project_search_preferences', JSON.stringify(preferences));
}

function loadSearchPreferences() {
    const saved = localStorage.getItem('project_search_preferences');

    if (saved) {
        try {
            const preferences = JSON.parse(saved);

            // Only load if saved within last hour
            if (Date.now() - preferences.timestamp < 3600000) {
                const searchInput = document.querySelector('input[name="search"]');
                const statusSelect = document.querySelector('select[name="status"]');

                if (searchInput && preferences.search) {
                    searchInput.value = preferences.search;
                }

                if (statusSelect && preferences.status) {
                    statusSelect.value = preferences.status;
                }
            }
        } catch (e) {
            console.error('Error loading search preferences:', e);
        }
    }
}

// Save preferences on form change
document.addEventListener('change', function(e) {
    if (e.target.name === 'search' || e.target.name === 'status') {
        saveSearchPreferences();
    }
});

// Load preferences on page load
window.addEventListener('load', function() {
    loadSearchPreferences();
});

// Print functionality
function printProjects() {
    window.print();
}

// Add print styles when printing
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script>

<!-- Print Styles -->
<style media="print">
.printing .btn,
.printing .modal,
.printing .pagination,
.printing .card:last-child {
    display: none !important;
}

.printing .table {
    font-size: 0.8rem;
}

.printing .card {
    box-shadow: none;
    border: 1px solid #dee2e6;
}
</style>
@endsection
