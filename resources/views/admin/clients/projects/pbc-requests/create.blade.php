@extends('layouts.app')
@section('title', 'Create PBC Request - ' . $project->engagement_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Document Request List</h1>
        <p class="text-muted mb-0">
            Company: {{ $client->company_name }} |
            Job ID: {{ $project->job_id ?? '1-01-001' }} |
            Engagement Name: {{ $project->engagement_name ?? 'Statutory audit for YE122024' }} |
            Type of Engagement: {{ ucfirst($project->engagement_type ?? 'Audit') }} |
            Engagement Period: {{ $project->engagement_period_start?->format('m/d/Y') ?? '31/12/2024' }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" id="save-btn" onclick="saveRequest()">
            <i class="fas fa-save"></i> SAVE
        </button>
        <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Success/Error Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form id="pbc-request-form" method="POST" action="{{ route('admin.clients.projects.pbc-requests.store', [$client, $project]) }}">
    @csrf

    <!-- Main Request Details -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company</label>
                        <input type="text" class="form-control" value="{{ $client->company_name }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Job ID</label>
                        <input type="text" class="form-control" value="{{ $project->job_id ?? '1-01-001' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Engagement Name</label>
                        <input type="text" class="form-control" value="{{ $project->engagement_name ?? 'Statutory audit for YE122024' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Type of Engagement</label>
                        <input type="text" class="form-control" value="{{ ucfirst($project->engagement_type ?? 'audit') }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Engagement Period</label>
                        <input type="text" class="form-control" value="{{ $project->engagement_period_start?->format('m/d/Y') ?? '31/12/2024' }}" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Items Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Request Items
                </h5>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm" id="template-select" onchange="loadTemplate()">
                        <option value="">Select Template...</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="addRequestItem()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0" id="request-items-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Category</th>
                            <th class="py-3">Request Description</th>
                            <th class="py-3">Assigned to</th>
                            <th class="py-3">Due Date</th>
                            <th class="py-3">Required</th>
                            <th class="py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="request-items-tbody">
                        <!-- Dynamic request items will be added here -->
                        <tr class="request-item-row" data-index="0">
                            <td class="px-4 py-3">
                                <select class="form-control" id="items_0_category" name="items[0][category]" onchange="updateSummary()">
                                    <option value="PF">Permanent File</option>
                                    <option value="CF">Current File</option>
                                </select>
                            </td>
                            <td class="py-3">
                                <textarea class="form-control" id="items_0_particulars" name="items[0][particulars]" rows="2"
                                          placeholder="Enter request description..." onchange="updateSummary()" required></textarea>
                            </td>
                            <td class="py-3">
                                <select class="form-control" id="items_0_assigned_to" name="items[0][assigned_to]">
                                    <option value="Client Contact 1">{{ $client->contact_person ?? 'Client Contact 1' }}</option>
                                    <option value="Client Contact 2">Client Contact 2</option>
                                </select>
                            </td>
                            <td class="py-3">
                                <input type="date" class="form-control" id="items_0_due_date" name="items[0][due_date]">
                            </td>
                            <td class="py-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="items_0_is_required" name="items[0][is_required]" value="1"
                                           checked onchange="updateSummary()">
                                    <label class="form-check-label" for="items_0_is_required">Required</label>
                                </div>
                            </td>
                            <td class="py-3">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRequestItem(0)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Additional Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Request Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="title">Request Title</label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $project->engagement_name ?? 'Statutory audit for YE122024') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3"
                                  placeholder="Optional description for this request...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-primary mb-0" id="total-items">1</h5>
                                <small class="text-muted">Total Items</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end">
                                <h5 class="text-warning mb-0" id="required-items">1</h5>
                                <small class="text-muted">Required</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h5 class="text-info mb-0" id="optional-items">0</h5>
                            <small class="text-muted">Optional</small>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <strong>Categories:</strong>
                        <div class="mt-2">
                            <span class="badge bg-secondary me-2" id="pf-count">PF: 1</span>
                            <span class="badge bg-primary" id="cf-count">CF: 0</span>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <small>This request will be sent to {{ $client->company_name }} for document submission.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields -->
    <input type="hidden" name="client_id" value="{{ $client->id }}">
    <input type="hidden" name="project_id" value="{{ $project->id }}">

</form>

@endsection

@section('styles')
<style>
/* Card enhancements */
.card {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    border-bottom: 1px solid #e9ecef;
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

/* Form styling */
.form-control, .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control[readonly] {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

/* Table styling */
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}



/* Button styling */
.btn {
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}



.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

/* Badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
}

/* Stats styling */
.border-end {
    border-right: 1px solid #dee2e6 !important;
}

/* Authority table */
.text-success {
    color: #198754 !important;
    font-weight: 500;
}

.text-muted {
    color: #6c757d !important;
}

/* Alert styling */
.alert {
    border-radius: 0.5rem;
    border: none;
}

.alert-info {
    background-color: #e3f2fd;
    color: #1976d2;
}

/* Form check styling */
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }

    .border-end {
        border-right: none !important;
        border-bottom: 1px solid #dee2e6 !important;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }
}

/* Animation for new rows */
.new-row {
    animation: slideIn 0.3s ease-in;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Template loading indicator */
.template-loading {
    position: relative;
}

.template-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 10px;
    width: 16px;
    height: 16px;
    margin-top: -8px;
    border: 2px solid #ccc;
    border-top: 2px solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Validation styles */
.is-invalid {
    border-color: #dc3545;
}

.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Button loading state */
.btn-loading {
    position: relative;
    color: transparent !important;
}

.btn-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1rem;
    height: 1rem;
    margin: -0.5rem 0 0 -0.5rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    color: white;
}
</style>
@endsection

@section('scripts')
<script>
let itemIndex = 1;

document.addEventListener('DOMContentLoaded', function() {
    updateSummary();
    console.log('Page loaded successfully');
    console.log('Form action:', document.getElementById('pbc-request-form').action);
    console.log('CSRF token:', document.querySelector('input[name="_token"]').value);
});

// MAIN SAVE FUNCTION - COMPLETELY REWRITTEN
function saveRequest() {
    console.log('=== SAVE REQUEST STARTED ===');

    const form = document.getElementById('pbc-request-form');
    const saveBtn = document.getElementById('save-btn');

    if (!form) {
        console.error('Form not found');
        showAlert('Form not found. Please refresh the page.', 'danger');
        return;
    }

    // Validate form first
    if (!validateForm()) {
        console.log('Form validation failed');
        return;
    }

    // Show loading state
    const originalBtnContent = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.classList.add('btn-loading');
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    console.log('Form validation passed, submitting...');

    // Debug form data
    const formData = new FormData(form);
    console.log('=== FORM DATA ===');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    console.log('=== END FORM DATA ===');

    // Submit form directly (no AJAX, let Laravel handle it)
    try {
        form.submit();
    } catch (error) {
        console.error('Form submission error:', error);
        showAlert('Error submitting form. Please try again.', 'danger');

        // Restore button
        saveBtn.disabled = false;
        saveBtn.classList.remove('btn-loading');
        saveBtn.innerHTML = originalBtnContent;
    }
}

// ENHANCED Add new request item function
function addRequestItem() {
    console.log('Adding new request item, current index:', itemIndex);

    const tbody = document.getElementById('request-items-tbody');
    if (!tbody) {
        console.error('Table body not found');
        return;
    }

    const newRow = document.createElement('tr');
    newRow.className = 'request-item-row new-row';
    newRow.setAttribute('data-index', itemIndex);

    newRow.innerHTML = `
        <td class="px-4 py-3">
            <select class="form-control" id="items_${itemIndex}_category" name="items[${itemIndex}][category]" onchange="updateSummary()">
                <option value="PF">Permanent File</option>
                <option value="CF">Current File</option>
            </select>
        </td>
        <td class="py-3">
            <textarea class="form-control" id="items_${itemIndex}_particulars" name="items[${itemIndex}][particulars]" rows="2"
                      placeholder="Enter request description..." onchange="updateSummary()" required></textarea>
        </td>
        <td class="py-3">
            <select class="form-control" id="items_${itemIndex}_assigned_to" name="items[${itemIndex}][assigned_to]">
                <option value="Client Contact 1">{{ $client->contact_person ?? 'Client Contact 1' }}</option>
                <option value="Client Contact 2">Client Contact 2</option>
            </select>
        </td>
        <td class="py-3">
            <input type="date" class="form-control" id="items_${itemIndex}_due_date" name="items[${itemIndex}][due_date]">
        </td>
        <td class="py-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="items_${itemIndex}_is_required" name="items[${itemIndex}][is_required]" value="1"
                       checked onchange="updateSummary()">
                <label class="form-check-label" for="items_${itemIndex}_is_required">Required</label>
            </div>
        </td>
        <td class="py-3">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRequestItem(${itemIndex})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(newRow);
    itemIndex++;
    updateSummary();

    console.log('New item added successfully. Next index:', itemIndex);
}

// Remove request item
function removeRequestItem(index) {
    const rows = document.querySelectorAll('.request-item-row');

    if (rows.length <= 1) {
        showAlert('At least one request item is required.', 'warning');
        return;
    }

    const row = document.querySelector(`[data-index="${index}"]`);
    if (row) {
        row.remove();
        updateSummary();
        console.log('Item removed, index:', index);
    }
}

// ENHANCED Load template function
function loadTemplate() {
    const select = document.getElementById('template-select');
    const templateId = select.value;

    if (!templateId) {
        return;
    }

    console.log('Loading template:', templateId);
    select.classList.add('template-loading');

    // Use the project-specific template URL
    const url = `{{ url('/admin/pbc-templates') }}/${templateId}/items`;

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => {
        console.log('Template response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Template data received:', data);

        if (data.success && data.data && data.data.length > 0) {
            // Clear current items
            const tbody = document.getElementById('request-items-tbody');
            tbody.innerHTML = '';
            itemIndex = 0;

            // Add template items
            data.data.forEach((item, index) => {
                addTemplateItem(item, index);
            });

            itemIndex = data.data.length;
            updateSummary();
            showAlert(`Loaded ${data.data.length} items from template.`, 'success');
        } else {
            showAlert('No items found in this template.', 'warning');
        }
    })
    .catch(error => {
        console.error('Error loading template:', error);
        showAlert('Error loading template: ' + error.message, 'danger');
    })
    .finally(() => {
        select.classList.remove('template-loading');
    });
}

// Add template item function
function addTemplateItem(item, index) {
    const tbody = document.getElementById('request-items-tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'request-item-row';
    newRow.setAttribute('data-index', index);

    const clientContactPerson = `{{ $client->contact_person ?? 'Client Contact 1' }}`;

    newRow.innerHTML = `
        <td class="px-4 py-3">
            <select class="form-control" id="items_${index}_category" name="items[${index}][category]" onchange="updateSummary()">
                <option value="PF" ${item.category === 'PF' ? 'selected' : ''}>Permanent File</option>
                <option value="CF" ${item.category === 'CF' ? 'selected' : ''}>Current File</option>
            </select>
        </td>
        <td class="py-3">
            <textarea class="form-control" id="items_${index}_particulars" name="items[${index}][particulars]" rows="2"
                      onchange="updateSummary()" required>${item.particulars || ''}</textarea>
        </td>
        <td class="py-3">
            <select class="form-control" id="items_${index}_assigned_to" name="items[${index}][assigned_to]">
                <option value="Client Contact 1">${clientContactPerson}</option>
                <option value="Client Contact 2">Client Contact 2</option>
            </select>
        </td>
        <td class="py-3">
            <input type="date" class="form-control" id="items_${index}_due_date" name="items[${index}][due_date]">
        </td>
        <td class="py-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="items_${index}_is_required" name="items[${index}][is_required]" value="1"
                       ${item.is_required ? 'checked' : ''} onchange="updateSummary()">
                <label class="form-check-label" for="items_${index}_is_required">Required</label>
            </div>
        </td>
        <td class="py-3">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRequestItem(${index})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(newRow);
}

// Update summary function
function updateSummary() {
    const rows = document.querySelectorAll('.request-item-row');
    let totalItems = rows.length;
    let requiredItems = 0;
    let pfCount = 0;
    let cfCount = 0;

    rows.forEach(row => {
        const categorySelect = row.querySelector('select[name*="[category]"]');
        const requiredCheckbox = row.querySelector('input[name*="[is_required]"]');

        if (categorySelect && requiredCheckbox) {
            const category = categorySelect.value;
            const isRequired = requiredCheckbox.checked;

            if (category === 'PF') pfCount++;
            if (category === 'CF') cfCount++;
            if (isRequired) requiredItems++;
        }
    });

    const optionalItems = totalItems - requiredItems;

    // Update summary display
    const totalElement = document.getElementById('total-items');
    const requiredElement = document.getElementById('required-items');
    const optionalElement = document.getElementById('optional-items');
    const pfElement = document.getElementById('pf-count');
    const cfElement = document.getElementById('cf-count');

    if (totalElement) totalElement.textContent = totalItems;
    if (requiredElement) requiredElement.textContent = requiredItems;
    if (optionalElement) optionalElement.textContent = optionalItems;
    if (pfElement) pfElement.textContent = `PF: ${pfCount}`;
    if (cfElement) cfElement.textContent = `CF: ${cfCount}`;
}

// ENHANCED Validate form function
function validateForm() {
    console.log('Validating form...');

    const rows = document.querySelectorAll('.request-item-row');
    let isValid = true;
    let errorMessages = [];

    // Check if we have at least one request item
    if (rows.length === 0) {
        errorMessages.push('At least one request item is required.');
        isValid = false;
    }

    // Check title field
    const title = document.querySelector('input[name="title"]');
    if (title && !title.value.trim()) {
        title.classList.add('is-invalid');
        errorMessages.push('Request title is required.');
        isValid = false;
    } else if (title) {
        title.classList.remove('is-invalid');
    }

    // Validate each request item
    rows.forEach((row, index) => {
        const particulars = row.querySelector('textarea[name*="[particulars]"]');
        if (particulars && !particulars.value.trim()) {
            particulars.classList.add('is-invalid');
            errorMessages.push(`Request description is required for item ${index + 1}.`);
            isValid = false;
        } else if (particulars) {
            particulars.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        const message = errorMessages.length === 1 ? errorMessages[0] :
                       `Please fix the following issues:\n• ${errorMessages.join('\n• ')}`;
        showAlert(message, 'danger');
        console.log('Form validation failed:', errorMessages);
    } else {
        console.log('Form validation passed');
    }

    return isValid;
}

// ENHANCED Show alert function
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.dynamic-alert');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed dynamic-alert`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';

    // Handle multi-line messages
    const formattedMessage = message.replace(/\n/g, '<br>');

    alertDiv.innerHTML = `
        <strong>${type.charAt(0).toUpperCase() + type.slice(1)}:</strong> ${formattedMessage}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Debug function for troubleshooting
function debugFormState() {
    const form = document.getElementById('pbc-request-form');
    const formData = new FormData(form);

    console.log('=== FORM DEBUG STATE ===');
    console.log('Form element:', form);
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);
    console.log('CSRF token:', document.querySelector('input[name="_token"]')?.value);

    console.log('Form data entries:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }

    console.log('Request items count:', document.querySelectorAll('.request-item-row').length);
    console.log('=== END FORM DEBUG ===');

    return {
        form: form,
        formData: formData,
        action: form.action,
        method: form.method,
        csrfToken: document.querySelector('input[name="_token"]')?.value,
        itemsCount: document.querySelectorAll('.request-item-row').length
    };
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S to save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        saveRequest();
    }

    // Ctrl+D for debug (in development)
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        debugFormState();
    }
});

// Form submission event listener for debugging
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pbc-request-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMIT EVENT ===');
            console.log('Form is being submitted');
            console.log('Action:', this.action);
            console.log('Method:', this.method);
            console.log('Current URL:', window.location.href);
            console.log('=== END SUBMIT EVENT ===');
        });

        // Also add beforeunload for unsaved changes warning
        let formChanged = false;
        form.addEventListener('input', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // Clear the flag when form is submitted
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    }
});

// Auto-save draft functionality (optional)
function saveDraft() {
    try {
        const form = document.getElementById('pbc-request-form');
        const formData = new FormData(form);
        const draftData = {};

        for (let [key, value] of formData.entries()) {
            draftData[key] = value;
        }

        // Save to session storage (not localStorage to avoid artifact restrictions)
        sessionStorage.setItem('pbc_request_draft', JSON.stringify(draftData));
        console.log('Draft saved to session storage');
    } catch (error) {
        console.error('Error saving draft:', error);
    }
}

// Load draft on page load
function loadDraft() {
    try {
        const draftData = sessionStorage.getItem('pbc_request_draft');
        if (draftData) {
            const data = JSON.parse(draftData);
            console.log('Draft data found:', data);
            // Could implement draft restoration here
        }
    } catch (error) {
        console.error('Error loading draft:', error);
    }
}

// Clear draft after successful submission
function clearDraft() {
    try {
        sessionStorage.removeItem('pbc_request_draft');
        console.log('Draft cleared');
    } catch (error) {
        console.error('Error clearing draft:', error);
    }
}

// Initialize auto-save for drafts
document.addEventListener('DOMContentLoaded', function() {
    loadDraft();

    const form = document.getElementById('pbc-request-form');
    if (form) {
        // Auto-save draft every 30 seconds
        let draftTimeout;
        form.addEventListener('input', function() {
            clearTimeout(draftTimeout);
            draftTimeout = setTimeout(saveDraft, 30000);
        });
    }
});
</script>
@endsection
