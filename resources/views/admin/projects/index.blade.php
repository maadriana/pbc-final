@extends('layouts.app')
@section('title', 'Project Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Project Management</h1>
        <p class="text-muted mb-0">Manage all projects and engagements</p>
    </div>
    @if(auth()->user()->canCreateProjects())
        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Project
        </a>
    @endif
</div>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search Projects</label>
                <input type="text" name="search" class="form-control" placeholder="Search by project name, ID, client..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Filter by Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Filter by Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="audit" {{ request('type') == 'audit' ? 'selected' : '' }}>Audit</option>
                    <option value="accounting" {{ request('type') == 'accounting' ? 'selected' : '' }}>Accounting</option>
                    <option value="tax" {{ request('type') == 'tax' ? 'selected' : '' }}>Tax</option>
                    <option value="special_engagement" {{ request('type') == 'special_engagement' ? 'selected' : '' }}>Special Engagement</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Projects Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-briefcase text-primary me-2"></i>
                All Projects ({{ $projects->total() ?? 0 }})
            </h5>
            <div class="text-muted small">
                Showing {{ $projects->firstItem() ?? 0 }} to {{ $projects->lastItem() ?? 0 }} of {{ $projects->total() ?? 0 }} results
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
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3">Partner</th>
                        <th class="px-4 py-3">Pending</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    @php
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
                        <td class="px-4 py-3">
                            <span class="fw-bold text-primary">{{ $project->job_id ?? sprintf('PRJ-%05d', $project->id) }}</span>
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
                            @if($project->client)
                                <div>
                                    <div class="fw-medium">{{ $project->client->company_name }}</div>
                                    <small class="text-muted">{{ $project->client->contact_person ?? 'No contact' }}</small>
                                </div>
                            @else
                                <span class="text-muted">No client assigned</span>
                            @endif
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
                                {{ ucfirst(str_replace('_', ' ', $project->engagement_type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-muted small">
                                {{ $engagementPeriod }}
                                @if($project->engagement_period_start && $project->engagement_period_end)
                                    <br>
                                    <span class="text-muted">{{ $project->engagement_period_start->format('M Y') }} - {{ $project->engagement_period_end->format('M Y') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="fw-medium">{{ $project->engagementPartner->name ?? 'EYM' }}</div>
                                <small class="text-muted">{{ $project->manager->name ?? 'MNGR 1' }}</small>
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
                                @if($project->client)
                                    <a href="{{ route('admin.clients.projects.pbc-requests.index', [$project->client, $project]) }}" class="btn btn-primary btn-sm" title="View PBC Requests">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @else
                                    <span class="btn btn-secondary btn-sm disabled" title="No client assigned">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                @endif
                                @if(auth()->user()->canCreateProjects())
                                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-warning btn-sm" title="Edit Project">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->isSystemAdmin())
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteProject({{ $project->id }})" title="Delete Project">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-briefcase fa-3x mb-3 opacity-50"></i>
                                <div class="h5">No projects found</div>
                                <small>
                                    @if(request('search') || request('status') || request('type'))
                                        Try adjusting your search criteria or <a href="{{ route('admin.projects.index') }}" class="text-decoration-none">clear filters</a>
                                    @else
                                        Projects will appear here when they are created
                                    @endif
                                </small>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($projects->hasPages())
    <div class="card-footer bg-white border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ $projects->firstItem() ?? 0 }} to {{ $projects->lastItem() ?? 0 }} of {{ $projects->total() ?? 0 }} results
            </div>
            {{ $projects->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this project? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <small><strong>Warning:</strong> This will also remove all associated PBC requests and items.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Project</button>
                </form>
            </div>
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
.form-label {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

/* Empty state styling */
.fa-briefcase {
    color: #6c757d;
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
}

/* Search form styling */
.card-body .row {
    align-items: end;
}

/* Pagination styling */
.pagination {
    margin: 0;
}

.page-link {
    border-radius: 0.375rem;
    margin: 0 2px;
    border: 1px solid #dee2e6;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
@endsection

@section('scripts')
<script>
function deleteProject(projectId) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/admin/projects/${projectId}`;
    deleteModal.show();
}

// Auto-submit search form with debounce
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        this.form.submit();
    }, 500);
});

// Show loading state for search
document.querySelector('form').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.disabled = true;
});
</script>
@endsection
