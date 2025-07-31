@extends('layouts.app')
@section('title', 'Client Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Client Management</h1>
        <p class="text-muted mb-0">Manage client companies and contact information</p>
    </div>
    <div>
        @if(auth()->user()->canManageClients())
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Client
            </a>
        @endif
    </div>
</div>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="mb-0">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search Clients</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by company name or contact person..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Filter Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Clients</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="company_name" {{ request('sort') == 'company_name' ? 'selected' : '' }}>Company Name</option>
                        <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Date Created</option>
                        <option value="updated_at" {{ request('sort') == 'updated_at' ? 'selected' : '' }}>Last Updated</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Clients Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="py-3">Company</th>
                        <th class="py-3">Contact Person</th>
                        <th class="py-3">Email</th>
                        <th class="py-3">Number</th>
                        <th class="py-3">Ongoing Jobs</th>
                        <th class="py-3">Completed Jobs</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    @php
                        // UPDATED: Use the new helper methods from the fixed Client model
                        $ongoingJobs = $client->getOngoingProjectsCount();
                        $completedJobs = $client->getCompletedProjectsCount();
                        $totalPbcRequests = $client->pbcRequests()->count();
                        $completedPbcRequests = $client->pbcRequests()->where('status', 'completed')->count();
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <span class="fw-bold">{{ $client->id }}</span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                     style="width: 40px; height: 40px;">
                                    <span class="text-white fw-bold">
                                        {{ strtoupper(substr($client->company_name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $client->company_name }}</div>
                                    <small class="text-muted">{{ $client->address ? Str::limit($client->address, 30) : 'No address' }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>
                                <div class="fw-medium">{{ $client->contact_person ?: 'Not specified' }}</div>
                                <small class="text-muted">Primary Contact</small>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>
                                <div>{{ $client->user->email }}</div>
                                <small class="text-muted">
                                </small>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>
                                {{ $client->phone ?: 'Not provided' }}
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="text-center">
                                @if($ongoingJobs > 0)
                                    <span class="badge bg-info fs-6">{{ $ongoingJobs }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="text-center">
                                @if($completedJobs > 0)
                                    <span class="badge bg-success fs-6">{{ $completedJobs }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="btn-group" role="group">
                                <!-- View Button -->
                                <a href="{{ route('admin.clients.show', $client) }}"
                                   class="btn btn-outline-primary btn-sm"
                                   title="View Client Details">
                                    <i class="fas fa-eye"></i> View
                                </a>

                                <!-- UPDATED: Create Job Button - matches wireframe -->
                                <a href="{{ route('admin.projects.create', ['client_id' => $client->id]) }}"
                                   class="btn btn-outline-success btn-sm"
                                   title="Create New Job for this Client">
                                    <i class="fas fa-plus"></i> Create Job
                                </a>

                                <!-- Edit Button -->
                                @if(auth()->user()->canManageClients())
                                    <a href="{{ route('admin.clients.edit', $client) }}"
                                       class="btn btn-outline-warning btn-sm"
                                       title="Edit Client">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif

                                <!-- Delete Button -->
                                @if(auth()->user()->isSystemAdmin())
                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteClientModal{{ $client->id }}"
                                            title="Delete Client">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>

                            <!-- Delete Confirmation Modal -->
                            @if(auth()->user()->isSystemAdmin())
                                <div class="modal fade" id="deleteClientModal{{ $client->id }}" tabindex="-1"
                                     aria-labelledby="deleteClientModalLabel{{ $client->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteClientModalLabel{{ $client->id }}">
                                                    Delete Client
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete client <strong>"{{ $client->company_name }}"</strong>?</p>

                                                <div class="alert alert-info">
                                                    <small>
                                                        <strong>Client Details:</strong>
                                                        <ul class="mb-0">
                                                            <li>Contact: {{ $client->contact_person ?: 'Not specified' }}</li>
                                                            <li>Email: {{ $client->user->email }}</li>
                                                            <li>Total Projects: {{ $ongoingJobs + $completedJobs }}</li>
                                                            <li>PBC Requests: {{ $totalPbcRequests }}</li>
                                                        </ul>
                                                    </small>
                                                </div>

                                                @if($ongoingJobs > 0 || $totalPbcRequests > 0)
                                                    <div class="alert alert-warning">
                                                        <small>
                                                            <strong>Warning:</strong> This client has active projects or PBC requests.
                                                            Deleting will also remove associated data. This action cannot be undone.
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">
                                                        Delete Client
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
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-building fa-3x mb-3"></i>
                                <h5>No Clients Found</h5>
                                <p>{{ request('search') ? 'No clients match your search criteria.' : 'Get started by creating your first client.' }}</p>
                                @if(auth()->user()->canManageClients())
                                    <div class="mt-3">
                                        <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create Client
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
@if($clients->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $clients->appends(request()->query())->links() }}
</div>
@endif

@endsection

@section('styles')
<style>
/* Enhanced table styling */
.table {
    font-size: 0.9rem;
}

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


/* Company avatar styling */
.rounded-circle {
    font-size: 0.875rem;
}

/* Badge improvements */
.badge {
    font-size: 0.75em;
    font-weight: 500;
}

.badge.fs-6 {
    font-size: 0.875rem !important;
}

/* Button group styling */
.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

/* UPDATED: Enhanced button styling for Create Job */
.btn-outline-success {
    border-color: #198754;
    color: #198754;
}

/* Statistics cards */
.card-body .bg-primary,
.card-body .bg-success,
.card-body .bg-info,
.card-body .bg-warning {
    width: 60px;
    height: 60px;
}

/* Search form styling */
.form-label.small {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
    transition: box-shadow 0.2s;
}

/* Modal improvements */
.modal-content {
    border-radius: 0.5rem;
}

/* Dropdown menu styling */
.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}


/* Empty state styling */
.fa-building {
    color: #6c757d;
    opacity: 0.5;
}

/* Button enhancements */
.btn {
    font-weight: 500;
    transition: all 0.2s;
}


/* Action button group improvements */
.btn-group .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .statistics-cards .col-md-3 {
        margin-bottom: 1rem;
    }

    /* Stack buttons vertically on mobile */
    .btn-group {
        flex-direction: column;
        width: 100%;
    }

    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0 !important;
    }
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Status indicators */
.badge.bg-success {
    background-color: #198754 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000 !important;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
    color: #000 !important;
}

/* Animation for statistics */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card-body h3 {
    animation: countUp 0.5s ease-out;
}


/* Quick action styling */
.dropdown-item .fas {
    width: 16px;
    text-align: center;
}

.dropdown-item .text-success {
    color: #198754 !important;
}

/* Create Job button special styling */
.btn-outline-success .fas {
    color: #198754;
}

/* Icon alignment in buttons */
.btn .fas {
    margin-right: 0.25rem;
}

.btn-sm .fas {
    margin-right: 0.125rem;
}

/* Sort indicator styling */
.sort-icon {
    font-size: 0.8rem;
    opacity: 0.7;
}

/* No results row styling */
.no-results-row td {
    background-color: #f8f9fa;
    border-top: 2px solid #e9ecef;
}

/* Enhanced accessibility */
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

/* Loading overlay */
#loading-overlay {
    backdrop-filter: blur(2px);
}

.spinner-border {
    width: 3rem;
    height: 3rem;
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

    // Auto-refresh data every 5 minutes
    setInterval(function() {
        // You can implement auto-refresh here if needed
        console.log('Auto-refresh check');
    }, 300000);

    // Initialize table enhancements
    initializeTableEnhancements();

    // Run animation on page load
    setTimeout(animateStats, 500);

    // Initialize accessibility enhancements
    enhanceAccessibility();

    // Handle mobile layout
    handleMobileLayout();

    // Initialize sortable columns
    initializeSortableColumns();
});

// Initialize table enhancements
function initializeTableEnhancements() {
    // Add loading states to action buttons
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        if (btn.tagName === 'A') {
            btn.addEventListener('click', function() {
                // Don't add loading to dropdown toggles
                if (!this.classList.contains('dropdown-toggle')) {
                    this.classList.add('loading');
                    this.style.pointerEvents = 'none';

                    // Add spinner
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.add('fa-spin');
                    }
                }
            });
        }
    });

}

// Initialize sortable columns
function initializeSortableColumns() {
    const sortableColumns = [1, 2, 5, 6]; // Company, Contact, Ongoing Jobs, Completed Jobs

    sortableColumns.forEach(columnIndex => {
        const th = document.querySelector(`th:nth-child(${columnIndex + 1})`);
        if (th) {
            th.style.cursor = 'pointer';
            th.title = 'Click to sort';

            th.addEventListener('click', () => {
                const isNumeric = columnIndex >= 5; // Job columns are numeric
                sortTable(columnIndex, isNumeric);
            });
        }
    });
}

// Export functions
function exportToExcel() {
    window.location.href = '{{ route("admin.clients.index") }}?export=excel&' + getQueryString();
}

function exportToPdf() {
    window.location.href = '{{ route("admin.clients.index") }}?export=pdf&' + getQueryString();
}

function exportToCsv() {
    window.location.href = '{{ route("admin.clients.index") }}?export=csv&' + getQueryString();
}

function getQueryString() {
    const params = new URLSearchParams(window.location.search);
    params.delete('page'); // Remove pagination from export
    return params.toString();
}

// Search form enhancements
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    setTimeout(function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-search"></i>';
    }, 2000);
});

// Enhanced search with debouncing
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value;

    searchTimeout = setTimeout(() => {
        if (searchValue.length >= 3 || searchValue.length === 0) {
            // Auto-submit search after 1 second of inactivity
            this.closest('form').submit();
        }
    }, 1000);
});

// Quick actions
function quickViewClient(clientId) {
    // Add loading state
    showLoadingOverlay();
    window.location.href = `/admin/clients/${clientId}`;
}

function quickEditClient(clientId) {
    showLoadingOverlay();
    window.location.href = `/admin/clients/${clientId}/edit`;
}

function quickCreateJob(clientId) {
    showLoadingOverlay();
    window.location.href = `/admin/projects/create?client_id=${clientId}`;
}

// Show loading overlay
function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading...</p>
            </div>
        </div>
    `;
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        backdrop-filter: blur(2px);
    `;

    document.body.appendChild(overlay);
}

// Client statistics animation
function animateStats() {
    const statNumbers = document.querySelectorAll('.card-body h3');

    statNumbers.forEach(function(stat) {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = finalValue / 30;

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

// Real-time filtering (optional enhancement)
function filterTableRows(searchTerm) {
    const rows = document.querySelectorAll('tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm.toLowerCase()) || searchTerm === '';

        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });

    // Show/hide no results message
    toggleNoResultsMessage(visibleCount === 0 && searchTerm !== '');
}

// Toggle no results message
function toggleNoResultsMessage(show) {
    let noResultsRow = document.querySelector('.no-results-row');

    if (show && !noResultsRow) {
        noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.innerHTML = `
            <td colspan="8" class="text-center py-4">
                <div class="text-muted">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p class="mb-0">No clients match your search criteria.</p>
                </div>
            </td>
        `;
        document.querySelector('tbody').appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
        noResultsRow.remove();
    }
}

// Table sorting enhancement
function sortTable(columnIndex, isNumeric = false) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // Skip if no data rows
    if (rows.length === 0 || rows[0].querySelector('.no-results-row, .empty-state')) {
        return;
    }

    const isAscending = tbody.dataset.sortOrder !== 'asc';
    tbody.dataset.sortOrder = isAscending ? 'asc' : 'desc';

    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();

        let comparison = 0;
        if (isNumeric) {
            comparison = parseInt(aVal) - parseInt(bVal);
        } else {
            comparison = aVal.localeCompare(bVal);
        }

        return isAscending ? comparison : -comparison;
    });

    // Clear tbody and append sorted rows
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));

    // Update sort indicators
    updateSortIndicators(columnIndex, isAscending);
}

// Update sort indicators in table headers
function updateSortIndicators(activeColumn, isAscending) {
    document.querySelectorAll('th').forEach((th, index) => {
        const existingIcon = th.querySelector('.sort-icon');
        if (existingIcon) {
            existingIcon.remove();
        }

        if (index === activeColumn) {
            const icon = document.createElement('i');
            icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} sort-icon ms-1`;
            th.appendChild(icon);
        }
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N for new client
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const createBtn = document.querySelector('a[href*="clients/create"]');
        if (createBtn) {
            createBtn.click();
        }
    }

    // Ctrl+F to focus search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.querySelector('input[name="search"]').focus();
    }

    // ESC to clear search
    if (e.key === 'Escape') {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput.value) {
            searchInput.value = '';
            searchInput.closest('form').submit();
        }
    }
});

// Enhanced modal interactions
document.addEventListener('show.bs.modal', function(e) {
    const modal = e.target;

    // Add entrance animation
    modal.style.transform = 'scale(0.8)';
    modal.style.opacity = '0';

    setTimeout(() => {
        modal.style.transform = 'scale(1)';
        modal.style.opacity = '1';
        modal.style.transition = 'all 0.3s ease';
    }, 10);
});

// Success/error message handling
function showMessage(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
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

// Enhanced accessibility
function enhanceAccessibility() {
    // Add ARIA labels to buttons without text
    document.querySelectorAll('button i.fas, a i.fas').forEach(icon => {
        const button = icon.closest('button, a');
        if (button && !button.hasAttribute('aria-label') && !button.textContent.trim()) {
            const iconClass = icon.className;
            let label = 'Action';

            if (iconClass.includes('fa-eye')) label = 'View details';
            else if (iconClass.includes('fa-edit')) label = 'Edit';
            else if (iconClass.includes('fa-trash')) label = 'Delete';
            else if (iconClass.includes('fa-plus')) label = 'Create new';
            else if (iconClass.includes('fa-info-circle')) label = 'More information';

            button.setAttribute('aria-label', label);
        }
    });

    // Add live region for dynamic content updates
    if (!document.getElementById('live-region')) {
        const liveRegion = document.createElement('div');
        liveRegion.id = 'live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.className = 'sr-only';
        document.body.appendChild(liveRegion);
    }
}

// Mobile responsive enhancements
function handleMobileLayout() {
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        // Stack action buttons vertically on mobile
        document.querySelectorAll('.btn-group').forEach(group => {
            group.classList.add('btn-group-vertical');
            group.classList.remove('btn-group');
        });

        // Adjust table display for mobile
        const table = document.querySelector('.table-responsive table');
        if (table) {
            table.style.fontSize = '0.8rem';
        }
    }
}

// Handle mobile layout on resize
window.addEventListener('resize', handleMobileLayout);

// Performance monitoring
function trackPagePerformance() {
    if ('performance' in window) {
        window.addEventListener('load', () => {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            if (loadTime > 3000) {
                console.warn('Page load time is slow:', loadTime + 'ms');
            }
        });
    }
}

// Initialize performance tracking
trackPagePerformance();

// Bulk actions (for future implementation)
function toggleBulkActions() {
    const checkboxes = document.querySelectorAll('input[name="selected_clients[]"]');
    const bulkActions = document.getElementById('bulk-actions');
    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

    if (checkedCount > 0) {
        bulkActions.style.display = 'block';
    } else {
        bulkActions.style.display = 'none';
    }
}

// Batch operations (future enhancement)
function initializeBatchOperations() {
    // Add checkboxes for batch selection (if needed)
    const hasCheckboxes = document.querySelector('input[type="checkbox"][name="selected_clients[]"]');

    if (hasCheckboxes) {
        // Select all functionality
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="selected_clients[]"]');
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleBulkActions();
            });
        }

        // Individual checkbox handlers
        document.querySelectorAll('input[name="selected_clients[]"]').forEach(cb => {
            cb.addEventListener('change', toggleBulkActions);
        });
    }
}

// Enhanced error handling for AJAX operations
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    showMessage('An unexpected error occurred. Please try again.', 'danger');
});

// Auto-save search preferences
function saveSearchPreferences() {
    const searchForm = document.querySelector('form');
    const formData = new FormData(searchForm);
    const preferences = {};

    for (let [key, value] of formData.entries()) {
        preferences[key] = value;
    }

    localStorage.setItem('client_search_preferences', JSON.stringify(preferences));
}

// Load search preferences
function loadSearchPreferences() {
    const saved = localStorage.getItem('client_search_preferences');
    if (saved) {
        try {
            const preferences = JSON.parse(saved);
            Object.keys(preferences).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field && preferences[key]) {
                    field.value = preferences[key];
                }
            });
        } catch (e) {
            console.error('Error loading search preferences:', e);
        }
    }
}

// Save preferences on form change
document.querySelector('form').addEventListener('change', saveSearchPreferences);

// Load preferences on page load
// loadSearchPreferences(); // Uncomment if you want to persist search preferences

// Initialize tooltips with enhanced options
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title], [data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            placement: 'top',
            trigger: 'hover focus',
            delay: { show: 500, hide: 100 }
        });
    });
}

// Call enhanced tooltip initialization
initializeTooltips();
</script>
@endsection
