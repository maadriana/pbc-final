@extends('layouts.app')
@section('title', 'Edit PBC Request Items')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Edit PBC Request Items</h1>
                </div>
                <div>
                    <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Client:</strong><br>
                            <span class="text-muted">{{ $client->company_name }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Project:</strong><br>
                            <span class="text-muted">{{ $project->name }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Job ID:</strong><br>
                            <span class="text-muted">{{ $project->job_id }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Engagement:</strong><br>
                            <span class="text-muted">{{ $project->engagement_type }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Main Form -->
    <form method="POST" action="{{ route('admin.clients.projects.pbc-requests.update', [$client, $project]) }}" id="pbc-form">
        @csrf
        @method('PUT')

        <!-- Existing Items -->
        @if($requests->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Existing Request Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Category</th>
                                    <th>Particulars</th>
                                    <th width="100">Required</th>
                                    <th width="120">Status</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $request)
                                    @foreach($request->items as $item)
                                        <tr>
                                            <td>
                                                @if($item->documents->count() == 0)
                                                    <select name="items[{{ $item->id }}][category]" class="form-select form-select-sm">
                                                        <option value="CF" {{ $item->category == 'CF' ? 'selected' : '' }}>CF</option>
                                                        <option value="PF" {{ $item->category == 'PF' ? 'selected' : '' }}>PF</option>
                                                    </select>
                                                @else
                                                    <span class="badge bg-{{ $item->category == 'CF' ? 'info' : 'primary' }}">
                                                        {{ $item->category }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->documents->count() == 0)
                                                    <textarea name="items[{{ $item->id }}][particulars]"
                                                              class="form-control form-control-sm"
                                                              rows="2"
                                                              required>{{ old("items.{$item->id}.particulars", $item->particulars) }}</textarea>
                                                @else
                                                    <div class="small">
                                                        {{ $item->particulars }}
                                                        <br><small class="text-warning">
                                                            <i class="fas fa-lock"></i> Cannot edit - files uploaded
                                                        </small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->documents->count() == 0)
                                                    <select name="items[{{ $item->id }}][is_required]" class="form-select form-select-sm">
                                                        <option value="1" {{ $item->is_required ? 'selected' : '' }}>Yes</option>
                                                        <option value="0" {{ !$item->is_required ? 'selected' : '' }}>No</option>
                                                    </select>
                                                @else
                                                    <span class="badge bg-{{ $item->is_required ? 'success' : 'secondary' }}">
                                                        {{ $item->is_required ? 'Yes' : 'No' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $status = $item->getCurrentStatus();
                                                    $statusClass = match($status) {
                                                        'pending' => 'secondary',
                                                        'uploaded' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                                @if($item->documents->count() > 0)
                                                    <br><small class="text-muted">{{ $item->documents->count() }} file(s)</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->documents->count() == 0)
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                               name="delete_items[]"
                                                               value="{{ $item->id }}"
                                                               class="form-check-input delete-item-checkbox"
                                                               id="delete_{{ $item->id }}">
                                                        <label class="form-check-label small text-danger" for="delete_{{ $item->id }}">
                                                            Delete
                                                        </label>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">
                                                        <i class="fas fa-file"></i> Has files
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Add New Items Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-plus"></i> Add New Items</h5>
                <button type="button" id="add-new-item" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <div id="new-items-container">
                    <!-- New items will be added here dynamically -->
                </div>

                <!-- Empty state message -->
                <div id="empty-state" class="text-center text-muted py-4">
                    <i class="fas fa-plus-circle fa-3x mb-3"></i>
                    <p>No new items added yet. Click "Add Item" to add new items.</p>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="row mt-4 mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}"
                       class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden template for new items -->
    <div class="d-none" id="new-item-template">
        <div class="row mb-3 new-item-row">
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select class="form-control category-select" required>
                    <option value="">Select</option>
                    <option value="CF">CF</option>
                    <option value="PF">PF</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Particulars</label>
                <textarea class="form-control particulars-textarea"
                          rows="2"
                          required
                          placeholder="Enter detailed description of the request..."></textarea>
            </div>
            <div class="col-md-2">
                <label class="form-label">Required</label>
                <select class="form-control required-select">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger btn-sm d-block remove-new-item">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS Styles -->
<style>
.new-item-row {
    border: 1px solid #e3e6ea;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.new-item-row:hover {
    background-color: #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.delete-item-checkbox:checked + label {
    text-decoration: line-through;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

.form-control-sm, .form-select-sm {
    font-size: 0.875rem;
}

#empty-state {
    display: block;
}

#new-items-container:not(:empty) + #empty-state {
    display: none;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let newItemCounter = 0;
    const container = document.getElementById('new-items-container');
    const template = document.getElementById('new-item-template');
    const addBtn = document.getElementById('add-new-item');
    const form = document.getElementById('pbc-form');

    // Add new item functionality
    function addNewItemRow() {
        if (!container || !template) return;

        const newRow = template.cloneNode(true);
        newRow.classList.remove('d-none');
        newRow.id = `new-item-${newItemCounter}`;

        // Update form field names
        const categorySelect = newRow.querySelector('.category-select');
        const particularsTextarea = newRow.querySelector('.particulars-textarea');
        const requiredSelect = newRow.querySelector('.required-select');

        categorySelect.name = `new_items[${newItemCounter}][category]`;
        particularsTextarea.name = `new_items[${newItemCounter}][particulars]`;
        requiredSelect.name = `new_items[${newItemCounter}][is_required]`;

        // Add remove functionality
        const removeBtn = newRow.querySelector('.remove-new-item');
        removeBtn.addEventListener('click', function() {
            newRow.remove();
            updateEmptyState();
        });

        container.appendChild(newRow);
        newItemCounter++;

        // Focus on the particulars field
        particularsTextarea.focus();
        updateEmptyState();
    }

    // Update empty state visibility
    function updateEmptyState() {
        const emptyState = document.getElementById('empty-state');
        const hasItems = container.children.length > 0;
        emptyState.style.display = hasItems ? 'none' : 'block';
    }

    // Add item button event
    if (addBtn) {
        addBtn.addEventListener('click', addNewItemRow);
    }

    // Form validation before submit
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasValidItems = false;

            // Check existing items
            const existingItems = document.querySelectorAll('textarea[name^="items["]');
            if (existingItems.length > 0) {
                hasValidItems = true;
            }

            // Validate and clean new items
            const newItemRows = document.querySelectorAll('[id^="new-item-"]');
            newItemRows.forEach(row => {
                const particulars = row.querySelector('.particulars-textarea');
                const category = row.querySelector('.category-select');

                if (particulars && category) {
                    if (!particulars.value.trim() || !category.value) {
                        // Remove invalid rows
                        row.remove();
                    } else {
                        hasValidItems = true;
                    }
                }
            });

            // Check if form has any valid items
            if (!hasValidItems) {
                e.preventDefault();
                alert('Please add at least one item or ensure existing items are not empty.');
                return false;
            }

            // Confirm deletion if items are marked for deletion
            const deleteCheckboxes = document.querySelectorAll('.delete-item-checkbox:checked');
            if (deleteCheckboxes.length > 0) {
                const confirmed = confirm(`Are you sure you want to delete ${deleteCheckboxes.length} item(s)?`);
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    // Delete item checkbox styling
    document.querySelectorAll('.delete-item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            if (this.checked) {
                row.style.backgroundColor = '#f8d7da';
                row.style.opacity = '0.7';
            } else {
                row.style.backgroundColor = '';
                row.style.opacity = '';
            }
        });
    });

    // Initialize empty state
    updateEmptyState();

    // Auto-resize textareas
    document.addEventListener('input', function(e) {
        if (e.target.tagName === 'TEXTAREA') {
            e.target.style.height = 'auto';
            e.target.style.height = e.target.scrollHeight + 'px';
        }
    });
});

// Form data validation helper
function validateFormData() {
    const form = document.getElementById('pbc-form');
    const formData = new FormData(form);

    console.log('Form data being submitted:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }

    return true;
}
</script>
@endsection
