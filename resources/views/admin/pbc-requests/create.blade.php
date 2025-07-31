@extends('layouts.app')
@section('title', 'Document Request List')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Document Request List</h1>
        <p class="text-muted mb-0">Create new PBC request for client</p>
    </div>
    <div>
        <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Requests
        </a>
    </div>
</div>

<!-- Main Form Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.pbc-requests.store') }}" id="pbc-form">
            @csrf

            <!-- Company & Job Information Section -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="client-select" class="form-label fw-bold">Company</label>
                    <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required id="client-select">
                        <option value="">Select Company</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}"
                                    data-contact="{{ $client->contact_person }}"
                                    data-email="{{ $client->user->email }}"
                                    {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->company_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="project-select" class="form-label fw-bold">Job ID</label>
                    <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required id="project-select">
                        <option value="">Select Job/Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}"
                                    data-job-id="{{ $project->job_id }}"
                                    data-engagement-name="{{ $project->engagement_name ?? $project->name }}"
                                    data-engagement-type="{{ $project->engagement_type }}"
                                    data-client-id="{{ $project->client_id }}"
                                    data-start-date="{{ $project->engagement_period_start ? $project->engagement_period_start->format('Y-m-d') : '' }}"
                                    data-end-date="{{ $project->engagement_period_end ? $project->engagement_period_end->format('Y-m-d') : '' }}"
                                    data-partner="{{ optional($project->engagementPartner)->name ?? '' }}"
                                    data-manager="{{ optional($project->manager)->name ?? '' }}"
                                    {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->job_id ?? 'No Job ID' }} - {{ $project->engagement_name ?? $project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label for="engagement-name" class="form-label fw-bold">Engagement Name</label>
                    <input type="text" id="engagement-name" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}"
                           required
                           placeholder="e.g., Statutory audit for YE122024">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label for="engagement-type" class="form-label fw-bold">Type of Engagement</label>
                    <input type="text" id="engagement-type" class="form-control" readonly
                           placeholder="Select project to see engagement type">
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <label for="engagement-period" class="form-label fw-bold">Engagement Period</label>
                    <input type="text" id="engagement-period" class="form-control" readonly
                           placeholder="Select project to see engagement period">
                </div>
            </div>

            <!-- Request Details Section -->
            <hr class="my-4">
            <h5 class="mb-3">Request Details</h5>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label for="category" class="form-label fw-bold">Category</label>
                    <select id="category" class="form-select">
                        <option value="">Select Category</option>
                        <option value="CF">Current File</option>
                        <option value="PF">Permanent File</option>
                    </select>
                    <div class="form-text">
                        <small class="text-muted">
                            <strong>Current:</strong> Current year operations<br>
                            <strong>Permanent:</strong> Permanent documents
                        </small>
                    </div>
                </div>

                <div class="col-md-8">
                    <label for="request-description" class="form-label fw-bold">Request Description</label>
                    <textarea id="request-description" class="form-control" rows="3"
                              placeholder="Describe the document requirements"></textarea>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label for="assigned-to" class="form-label fw-bold">Assigned to</label>
                    <select id="assigned-to" class="form-select">
                        <option value="">Select Staff</option>
                        <option value="Client Contact 1">Client Contact 1</option>
                        <option value="Client Contact 2">Client Contact 2</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="due-date" class="form-label fw-bold">Due Date</label>
                    <input type="date" id="due-date" name="due_date"
                           class="form-control @error('due_date') is-invalid @enderror"
                           value="{{ old('due_date') }}"
                           min="{{ date('Y-m-d') }}">
                    @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">&nbsp;</label>
                    <div class="d-grid">
                        <button type="button" class="btn btn-success" id="add-item">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
            </div>

            <!-- Request Items Section -->
            <hr class="my-4">
            <h5 class="mb-3">Request Items</h5>

            <div id="request-items">
                @if(old('items'))
                    @foreach(old('items') as $index => $item)
                        <div class="request-item mb-3 p-3 border rounded bg-light">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label small">Category</label>
                                    <select name="items[{{ $index }}][category]" class="form-select form-select-sm">
                                        <option value="">Select</option>
                                        <option value="CF" {{ ($item['category'] ?? '') === 'CF' ? 'selected' : '' }}>Current File</option>
                                        <option value="PF" {{ ($item['category'] ?? '') === 'PF' ? 'selected' : '' }}>Permanent File</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Request Description <span class="text-danger">*</span></label>
                                    <textarea name="items[{{ $index }}][particulars]" class="form-control form-control-sm"
                                              rows="2" required>{{ $item['particulars'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Requestor</label>
                                    <input type="text" name="items[{{ $index }}][requestor]"
                                           class="form-control form-control-sm"
                                           value="{{ $item['requestor'] ?? 'MNGR 1' }}" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">Required</label>
                                    <select name="items[{{ $index }}][is_required]" class="form-select form-select-sm">
                                        <option value="1" {{ ($item['is_required'] ?? true) ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !($item['is_required'] ?? true) ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">&nbsp;</label>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item d-block">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-3">
                                    <label class="form-label small">Date Requested</label>
                                    <input type="date" name="items[{{ $index }}][date_requested]"
                                           class="form-control form-control-sm"
                                           value="{{ $item['date_requested'] ?? date('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Assigned to</label>
                                    <input type="text" name="items[{{ $index }}][assigned_to]"
                                           class="form-control form-control-sm"
                                           value="{{ $item['assigned_to'] ?? 'Client Staff 1' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Status</label>
                                    <input type="text" class="form-control form-control-sm" value="Pending" readonly>
                                    <input type="hidden" name="items[{{ $index }}][status]" value="pending">
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Default empty state message -->
                    <div id="no-items-message" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No request items added yet</h6>
                        <p class="text-muted small">Use the form above to add request items</p>
                    </div>
                @endif
            </div>

            <!-- Template Selection -->
            <div class="card border-primary mt-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-magic"></i> Quick Templates
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="template-select" class="form-label">Use Template</label>
                            <select name="template_id" class="form-select" id="template-select">
                                <option value="">Create from scratch</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}"
                                            data-description="{{ $template->description }}"
                                            {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }} ({{ $template->templateItems->count() }} items)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Template Description</label>
                            <p id="template-description" class="form-control-plaintext text-muted small">
                                Select a template to see description
                            </p>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-primary w-100" id="load-template">
                                <i class="fas fa-download"></i> Load Template
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <label for="description" class="form-label">Additional Notes</label>
                    <textarea id="description" name="description"
                              class="form-control @error('description') is-invalid @enderror"
                              rows="3"
                              placeholder="Optional description or special instructions">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Header Information</label>
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="text" name="header_info[engagement_partner]"
                                   class="form-control form-control-sm mb-2"
                                   placeholder="Engagement Partner"
                                   id="engagement-partner"
                                   value="{{ old('header_info.engagement_partner') }}">
                        </div>
                        <div class="col-12">
                            <input type="text" name="header_info[engagement_manager]"
                                   class="form-control form-control-sm"
                                   placeholder="Engagement Manager"
                                   id="engagement-manager"
                                   value="{{ old('header_info.engagement_manager') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="fas fa-info-circle"></i>
                    Make sure all required fields are filled before saving
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Request
                    </button>
                    <button type="submit" name="send_immediately" value="1" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Save & Send
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Form styling improvements */
.form-label.fw-bold {
    color: #495057;
    font-size: 0.9rem;
}

.form-control, .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
}

.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Request item styling */
.request-item {
    transition: all 0.2s ease;
    border: 1px solid #e9ecef !important;
}

.request-item:hover {
    border-color: #dee2e6 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Template card styling */
.card.border-primary {
    border-width: 1px !important;
}

/* Button styling */
.btn {
    font-weight: 500;
    border-radius: 0.375rem;
}

.btn-outline-danger:hover {
    transform: scale(0.98);
}

/* No items message */
#no-items-message {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
}

/* Form text improvements */
.form-text {
    margin-top: 0.25rem;
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .request-item .row {
        row-gap: 0.5rem;
    }

    .btn-group-vertical .btn {
        margin-bottom: 0.5rem;
    }
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Success/error states */
.is-valid {
    border-color: #28a745;
}

.is-invalid {
    border-color: #dc3545;
}

/* Custom scrollbar for textarea */
textarea {
    resize: vertical;
    min-height: 60px;
}

/* Icon alignments */
.fas {
    width: 14px;
    text-align: center;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = {{ old('items') ? count(old('items')) : 0 }};

    const projectSelect = document.getElementById('project-select');
    const clientSelect = document.getElementById('client-select');
    const engagementNameInput = document.getElementById('engagement-name');
    const engagementTypeInput = document.getElementById('engagement-type');
    const engagementPeriodInput = document.getElementById('engagement-period');
    const engagementPartnerInput = document.getElementById('engagement-partner');
    const engagementManagerInput = document.getElementById('engagement-manager');
    const templateSelect = document.getElementById('template-select');
    const templateDescription = document.getElementById('template-description');
    const addItemBtn = document.getElementById('add-item');
    const loadTemplateBtn = document.getElementById('load-template');

    // Auto-populate fields when project is selected
    projectSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (selectedOption.value) {
            const jobId = selectedOption.getAttribute('data-job-id');
            const engagementName = selectedOption.getAttribute('data-engagement-name');
            const engagementType = selectedOption.getAttribute('data-engagement-type');
            const clientId = selectedOption.getAttribute('data-client-id');
            const startDate = selectedOption.getAttribute('data-start-date');
            const endDate = selectedOption.getAttribute('data-end-date');
            const partner = selectedOption.getAttribute('data-partner');
            const manager = selectedOption.getAttribute('data-manager');

            // Auto-select client
            if (clientId) {
                clientSelect.value = clientId;
            }

            // Fill engagement details
            engagementNameInput.value = engagementName || '';
            engagementTypeInput.value = engagementType ? engagementType.charAt(0).toUpperCase() + engagementType.slice(1) : '';

            // Format engagement period
            if (startDate && endDate) {
                const start = new Date(startDate).toLocaleDateString();
                const end = new Date(endDate).toLocaleDateString();
                engagementPeriodInput.value = `${start} - ${end}`;
            } else {
                engagementPeriodInput.value = '';
            }

            // Fill header info
            engagementPartnerInput.value = partner || '';
            engagementManagerInput.value = manager || '';
        } else {
            // Clear fields
            engagementNameInput.value = '';
            engagementTypeInput.value = '';
            engagementPeriodInput.value = '';
            engagementPartnerInput.value = '';
            engagementManagerInput.value = '';
        }
    });

    // Update template description
    templateSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const description = selectedOption.getAttribute('data-description');
        templateDescription.textContent = description || 'Select a template to see description';
    });

    // Add new item functionality
    addItemBtn.addEventListener('click', function() {
        const category = document.getElementById('category').value;
        const requestDescription = document.getElementById('request-description').value;
        const assignedTo = document.getElementById('assigned-to').value;
        const dueDate = document.getElementById('due-date').value;

        if (!category || !requestDescription) {
            alert('Please fill in Category and Request Description fields');
            return;
        }

        addRequestItem({
            category: category,
            particulars: requestDescription,
            assigned_to: assignedTo || 'Client Staff 1',
            date_requested: dueDate || new Date().toISOString().split('T')[0],
            is_required: true,
            requestor: 'MNGR 1'
        });

        // Clear form fields
        document.getElementById('category').value = '';
        document.getElementById('request-description').value = '';
        document.getElementById('assigned-to').value = '';
    });

    // Load template functionality
    loadTemplateBtn.addEventListener('click', function() {
        const templateId = templateSelect.value;

        if (!templateId) {
            alert('Please select a template first');
            return;
        }

        loadTemplate(templateId);
    });

    // Remove item functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
            const item = e.target.closest('.request-item');
            if (item) {
                item.remove();
                reindexItems();
                toggleNoItemsMessage();
            }
        }
    });

    // Add request item function
    function addRequestItem(item = {}) {
        const container = document.getElementById('request-items');
        const noItemsMessage = document.getElementById('no-items-message');

        if (noItemsMessage) {
            noItemsMessage.remove();
        }

        const itemHtml = createItemHTML(itemIndex, item);
        const div = document.createElement('div');
        div.innerHTML = itemHtml;
        container.appendChild(div.firstElementChild);

        itemIndex++;
    }

    // Create item HTML
    function createItemHTML(index, item = {}) {
        return `
            <div class="request-item mb-3 p-3 border rounded bg-light">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small">Category</label>
                        <select name="items[${index}][category]" class="form-select form-select-sm">
                            <option value="">Select</option>
                            <option value="CF" ${item.category === 'CF' ? 'selected' : ''}>Current File</option>
                            <option value="PF" ${item.category === 'PF' ? 'selected' : ''}>Permanent File</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Request Description <span class="text-danger">*</span></label>
                        <textarea name="items[${index}][particulars]" class="form-control form-control-sm"
                                  rows="2" required>${escapeHtml(item.particulars || '')}</textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Requestor</label>
                        <input type="text" name="items[${index}][requestor]"
                               class="form-control form-control-sm"
                               value="${escapeHtml(item.requestor || 'MNGR 1')}" readonly>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Required</label>
                        <select name="items[${index}][is_required]" class="form-select form-select-sm">
                            <option value="1" ${item.is_required !== false ? 'selected' : ''}>Yes</option>
                            <option value="0" ${item.is_required === false ? 'selected' : ''}>No</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item d-block">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label small">Date Requested</label>
                        <input type="date" name="items[${index}][date_requested]"
                               class="form-control form-control-sm"
                               value="${item.date_requested || new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Assigned to</label>
                        <input type="text" name="items[${index}][assigned_to]"
                               class="form-control form-control-sm"
                               value="${escapeHtml(item.assigned_to || 'Client Staff 1')}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Status</label>
                        <input type="text" class="form-control form-control-sm" value="Pending" readonly>
                        <input type="hidden" name="items[${index}][status]" value="pending">
                    </div>
                </div>
            </div>
        `;
    }

    // Load template function
    function loadTemplate(templateId) {
        const url = `/admin/pbc-templates/${templateId}/items`;

        loadTemplateBtn.disabled = true;
        loadTemplateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

        fetch(url)
            .then(response => response.json())
            .then(result => {
                const items = result.data || result;

                if (!Array.isArray(items) || items.length === 0) {
                    alert('This template has no items or invalid format');
                    return;
                }

                // Clear existing items
                const container = document.getElementById('request-items');
                container.innerHTML = '';

                // Add template items
                items.forEach((item, index) => {
                    addRequestItem(item);
                });

                alert(`Loaded ${items.length} items from template`);
            })
            .catch(error => {
                console.error('Error loading template:', error);
                alert('Error loading template: ' + error.message);
            })
            .finally(() => {
                loadTemplateBtn.disabled = false;
                loadTemplateBtn.innerHTML = '<i class="fas fa-download"></i> Load Template';
            });
    }

    // Reindex items function
    function reindexItems() {
        const items = document.querySelectorAll('.request-item');
        items.forEach((item, index) => {
            const inputs = item.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                if (input.name && input.name.includes('items[')) {
                    input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
                }
            });
        });
        itemIndex = items.length;
    }

    // Toggle no items message
    function toggleNoItemsMessage() {
        const container = document.getElementById('request-items');
        const items = container.querySelectorAll('.request-item');

        if (items.length === 0) {
            container.innerHTML = `
                <div id="no-items-message" class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No request items added yet</h6>
                    <p class="text-muted small">Use the form above to add request items</p>
                </div>
            `;
        }
    }

    // Escape HTML function
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Form validation
    document.getElementById('pbc-form').addEventListener('submit', function(e) {
        const items = document.querySelectorAll('.request-item');

        if (items.length === 0) {
            e.preventDefault();
            alert('Please add at least one request item before submitting.');
            return false;
        }

        // Validate required fields in items
        let hasError = false;
        items.forEach((item, index) => {
            const particulars = item.querySelector('textarea[name*="particulars"]');
            if (!particulars || !particulars.value.trim()) {
                hasError = true;
                particulars?.classList.add('is-invalid');
            } else {
                particulars?.classList.remove('is-invalid');
            }
        });

        if (hasError) {
            e.preventDefault();
            alert('Please fill in the "Request Description" field for all items.');
            return false;
        }

        console.log('Form validation passed, submitting...');
    });

    // Initialize form if there are old items
    @if(old('items'))
        toggleNoItemsMessage();
    @endif
});
</script>
@endsection
