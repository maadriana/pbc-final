@extends('layouts.app')
@section('title', 'Create Project')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Create Job</h1>
        @if($preselectedClient)
            <p class="text-muted mb-0">Creating new project for <strong>{{ $preselectedClient->company_name }}</strong></p>
        @else
            <p class="text-muted mb-0">Create a new project engagement</p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="saveProject()">
            <i class="fas fa-save"></i> SAVE
        </button>
        @if($preselectedClient)
            <a href="{{ route('admin.clients.show', $preselectedClient) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Client
            </a>
        @else
            <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        @endif
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="project-form" method="POST" action="{{ route('admin.projects.store') }}">
    @csrf

    <!-- Hidden field to auto-set name from engagement_name -->
    <input type="hidden" name="name" id="hidden-name" value="{{ old('engagement_name') }}">
    <!-- Hidden field for description since we removed it from UI -->
    <input type="hidden" name="description" value="">

    <!-- Main Project Details Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Company <span class="text-danger">*</span>
                        </label>
                        @if($preselectedClient)
                            <input type="text" class="form-control" value="{{ $preselectedClient->company_name }}" readonly>
                            <input type="hidden" name="client_id" value="{{ $preselectedClient->id }}">
                            <div class="form-text text-success">
                                <i class="fas fa-lock"></i> Pre-selected from client details
                            </div>
                        @else
                            <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                                <option value="">Select Company...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Job ID <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="job_id"
                               class="form-control @error('job_id') is-invalid @enderror"
                               value="{{ old('job_id') }}"
                               placeholder="e.g., 1-01-001"
                               readonly>
                        <div class="form-text">Job ID will be auto-generated based on engagement type</div>
                        @error('job_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Engagement Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="engagement_name"
                               class="form-control @error('engagement_name') is-invalid @enderror"
                               value="{{ old('engagement_name') }}"
                               placeholder="e.g., Statutory audit for YE122024"
                               required>
                        @error('engagement_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Type of Engagement <span class="text-danger">*</span>
                        </label>
                        <select name="engagement_type"
                                class="form-select @error('engagement_type') is-invalid @enderror"
                                required
                                onchange="updateJobId()">
                            <option value="">Select Type...</option>
                            <option value="audit" {{ old('engagement_type') == 'audit' ? 'selected' : '' }}>Audit</option>
                            <option value="accounting" {{ old('engagement_type') == 'accounting' ? 'selected' : '' }}>Accounting</option>
                            <option value="tax" {{ old('engagement_type') == 'tax' ? 'selected' : '' }}>Tax</option>
                            <option value="special_engagement" {{ old('engagement_type') == 'special_engagement' ? 'selected' : '' }}>Special Engagement</option>
                            <option value="others" {{ old('engagement_type') == 'others' ? 'selected' : '' }}>Others</option>
                        </select>
                        @error('engagement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Engagement Period</label>
                        <input type="text"
                               name="engagement_period"
                               class="form-control @error('engagement_period') is-invalid @enderror"
                               value="{{ old('engagement_period', '2024') }}"
                               placeholder="e.g., 2024">
                        @error('engagement_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <div class="card bg-light h-100">
                        <div class="card-header">
                            <h6 class="mb-0">Team Assignment</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Partner</label>
                                <select name="engagement_partner_id" class="form-select">
                                    <option value="">Select Partner...</option>
                                    @if(isset($staffByRole['engagement_partner']))
                                        @foreach($staffByRole['engagement_partner'] as $user)
                                            <option value="{{ $user->id }}" {{ old('engagement_partner_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Manager</label>
                                <select name="manager_id" class="form-select">
                                    <option value="">Select Manager...</option>
                                    @if(isset($staffByRole['manager']))
                                        @foreach($staffByRole['manager'] as $user)
                                            <option value="{{ $user->id }}" {{ old('manager_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Staff 1</label>
                                <select name="associate_1" class="form-select">
                                    <option value="">Select Staff 1...</option>
                                    @if(isset($staffByRole['associate']))
                                        @foreach($staffByRole['associate'] as $user)
                                            <option value="{{ $user->id }}" {{ old('associate_1') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Staff 2</label>
                                <select name="associate_2" class="form-select">
                                    <option value="">Select Staff 2...</option>
                                    @if(isset($staffByRole['associate']))
                                        @foreach($staffByRole['associate'] as $user)
                                            <option value="{{ $user->id }}" {{ old('associate_2') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <small>Team members can be updated later from the project details page.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Timeline Card (Full Width) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header">
            <h6 class="mb-0">Project Timeline</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date"
                               name="start_date"
                               class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date') }}">
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date"
                               name="end_date"
                               class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date') }}">
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i>
                        <small><strong>Next Steps:</strong> After creating this project, you can create PBC requests and manage documents.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-eye"></i> Project Preview
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Company:</td>
                            <td id="preview-company">
                                @if($preselectedClient)
                                    {{ $preselectedClient->company_name }}
                                @else
                                    <em class="text-muted">Select company above</em>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Job ID:</td>
                            <td id="preview-job-id"><em class="text-muted">Will be auto-generated</em></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Engagement:</td>
                            <td id="preview-engagement"><em class="text-muted">Enter engagement name above</em></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Type:</td>
                            <td id="preview-type"><em class="text-muted">Select engagement type above</em></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Period:</td>
                            <td id="preview-period"><em class="text-muted">Enter engagement period above</em></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6>Team Assignment</h6>
                    <ul class="list-unstyled">
                        <li><strong>Partner:</strong> <span id="preview-partner"><em class="text-muted">Not assigned</em></span></li>
                        <li><strong>Manager:</strong> <span id="preview-manager"><em class="text-muted">Not assigned</em></span></li>
                        <li><strong>Staff 1:</strong> <span id="preview-staff1"><em class="text-muted">Not assigned</em></span></li>
                        <li><strong>Staff 2:</strong> <span id="preview-staff2"><em class="text-muted">Not assigned</em></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</form>

@endsection

@section('styles')
<style>
/* Form styling */
.form-label.fw-bold {
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

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

/* Card styling */
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

.card.bg-light {
    background-color: #f8f9fa !important;
}

/* Button styling */
.btn {
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
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

.alert-success {
    background-color: #e8f5e8;
    color: #2e7d32;
}

/* Form text styling */
.form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.form-text.text-success {
    color: #198754 !important;
}

/* Required asterisk */
.text-danger {
    color: #dc3545 !important;
}

/* Validation states */
.is-valid {
    border-color: #198754;
}

.is-invalid {
    border-color: #dc3545;
}

.valid-feedback {
    color: #198754;
    font-size: 0.8rem;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.8rem;
}

/* Preview table */
.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

.table-borderless .fw-bold {
    width: 120px;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Button loading state */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading .btn-text {
    opacity: 0;
}

.btn-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin-top: -10px;
    margin-left: -10px;
    border: 2px solid transparent;
    border-top: 2px solid #fff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
    }

    .table-borderless .fw-bold {
        width: 100px;
        font-size: 0.9rem;
    }
}

/* Icon alignment */
.fas {
    width: 16px;
    text-align: center;
}

/* Auto-fill animation */
.auto-filled {
    background-color: #e8f5e8 !important;
    transition: background-color 0.3s ease;
}

/* Job ID generation indicator */
.job-id-generating {
    position: relative;
}

.job-id-generating::after {
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

/* Preview section enhancements */
#preview-section {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
}

.preview-value {
    font-weight: 500;
    color: #495057;
}

.preview-empty {
    font-style: italic;
    color: #6c757d;
}

/* Form section spacing */
.row.g-4 {
    --bs-gutter-x: 1.5rem;
    --bs-gutter-y: 1.5rem;
}

/* Enhanced select styling */
.form-select option {
    padding: 0.5rem;
}

/* Success states */
.form-control.is-valid, .form-select.is-valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 1.06 1.06'/%3e%3c/svg%3e");
}

/* Focus improvements */
.form-control:focus, .form-select:focus {
    border-width: 2px;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form listeners
    initializeFormListeners();

    // Update preview on page load
    updatePreview();

    // Auto-generate job ID when engagement type changes
    updateJobId();
});

// Initialize all form event listeners
function initializeFormListeners() {
    // Engagement name preview and sync with hidden name field
    const engagementNameField = document.querySelector('input[name="engagement_name"]');
    if (engagementNameField) {
        engagementNameField.addEventListener('input', function() {
            updatePreview();
            // ADDED: Sync engagement name with hidden name field
            const hiddenNameField = document.getElementById('hidden-name');
            if (hiddenNameField) {
                hiddenNameField.value = this.value;
            }
        });
    }

    // Engagement type preview
    const engagementTypeField = document.querySelector('select[name="engagement_type"]');
    if (engagementTypeField) {
        engagementTypeField.addEventListener('change', function() {
            updatePreview();
            updateJobId();
        });
    }

    // Engagement period preview
    const engagementPeriodField = document.querySelector('input[name="engagement_period"]');
    if (engagementPeriodField) {
        engagementPeriodField.addEventListener('input', updatePreview);
    }

    // Team assignment previews
    const partnerField = document.querySelector('select[name="engagement_partner_id"]');
    if (partnerField) {
        partnerField.addEventListener('change', updatePreview);
    }

    const managerField = document.querySelector('select[name="manager_id"]');
    if (managerField) {
        managerField.addEventListener('change', updatePreview);
    }

    const associate1Field = document.querySelector('select[name="associate_1"]');
    if (associate1Field) {
        associate1Field.addEventListener('change', updatePreview);
    }

    const associate2Field = document.querySelector('select[name="associate_2"]');
    if (associate2Field) {
        associate2Field.addEventListener('change', updatePreview);
    }

    // Client selection (if not pre-selected)
    const clientField = document.querySelector('select[name="client_id"]');
    if (clientField) {
        clientField.addEventListener('change', updatePreview);
    }

    // Form validation on input
    document.querySelectorAll('input[required], select[required]').forEach(field => {
        field.addEventListener('blur', validateField);
        field.addEventListener('input', validateField);
    });
}

// Update job ID based on engagement type
function updateJobId() {
    const engagementTypeField = document.querySelector('select[name="engagement_type"]');
    const jobIdField = document.querySelector('input[name="job_id"]');

    if (!engagementTypeField || !jobIdField) return;

    const engagementType = engagementTypeField.value;

    if (!engagementType) {
        jobIdField.value = '';
        const previewJobId = document.getElementById('preview-job-id');
        if (previewJobId) {
            previewJobId.innerHTML = '<em class="text-muted">Select engagement type first</em>';
        }
        return;
    }

    // Show loading state
    jobIdField.classList.add('job-id-generating');

    // Simulate job ID generation (in real app, this would be an API call)
    setTimeout(() => {
        const typeMap = {
            'audit': '1',
            'accounting': '2',
            'tax': '3',
            'special_engagement': '4',
            'others': '5'
        };

        const typeCode = typeMap[engagementType] || '9';
        const year = new Date().getFullYear().toString().slice(-2);
        const sequence = Math.floor(Math.random() * 999) + 1; // In real app, get from database

        const jobId = `${typeCode}-${year}-${sequence.toString().padStart(3, '0')}`;

        jobIdField.value = jobId;
        jobIdField.classList.remove('job-id-generating');
        jobIdField.classList.add('auto-filled');

        // Update preview
        const previewJobId = document.getElementById('preview-job-id');
        if (previewJobId) {
            previewJobId.innerHTML = `<span class="preview-value">${jobId}</span>`;
        }

        // Remove auto-filled class after animation
        setTimeout(() => {
            jobIdField.classList.remove('auto-filled');
        }, 2000);
    }, 1000);
}

// Update the preview section
function updatePreview() {
    // Company
    const clientSelect = document.querySelector('select[name="client_id"]');
    const companyPreview = document.getElementById('preview-company');
    if (companyPreview && clientSelect && clientSelect.value) {
        const selectedOption = clientSelect.options[clientSelect.selectedIndex];
        companyPreview.innerHTML = `<span class="preview-value">${selectedOption.text}</span>`;
    } else if (companyPreview && !document.querySelector('input[name="client_id"][type="hidden"]')) {
        companyPreview.innerHTML = '<em class="text-muted">Select company above</em>';
    }

    // Engagement name
    const engagementNameField = document.querySelector('input[name="engagement_name"]');
    const engagementPreview = document.getElementById('preview-engagement');
    if (engagementPreview && engagementNameField) {
        const engagementName = engagementNameField.value;
        engagementPreview.innerHTML = engagementName
            ? `<span class="preview-value">${engagementName}</span>`
            : '<em class="text-muted">Enter engagement name above</em>';
    }

    // Engagement type
    const engagementTypeField = document.querySelector('select[name="engagement_type"]');
    const typePreview = document.getElementById('preview-type');
    if (typePreview && engagementTypeField) {
        const typeText = engagementTypeField.options[engagementTypeField.selectedIndex]?.text || '';
        typePreview.innerHTML = typeText
            ? `<span class="preview-value">${typeText}</span>`
            : '<em class="text-muted">Select engagement type above</em>';
    }

    // Engagement period
    const engagementPeriodField = document.querySelector('input[name="engagement_period"]');
    const periodPreview = document.getElementById('preview-period');
    if (periodPreview && engagementPeriodField) {
        const engagementPeriod = engagementPeriodField.value;
        periodPreview.innerHTML = engagementPeriod
            ? `<span class="preview-value">${engagementPeriod}</span>`
            : '<em class="text-muted">Enter engagement period above</em>';
    }

    // Team assignments
    updateTeamPreview('engagement_partner_id', 'preview-partner');
    updateTeamPreview('manager_id', 'preview-manager');
    updateTeamPreview('associate_1', 'preview-staff1');
    updateTeamPreview('associate_2', 'preview-staff2');
}

// Update team member preview
function updateTeamPreview(selectName, previewId) {
    const select = document.querySelector(`select[name="${selectName}"]`);
    const preview = document.getElementById(previewId);

    if (select && preview) {
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            preview.innerHTML = `<span class="preview-value">${selectedOption.text}</span>`;
        } else {
            preview.innerHTML = '<em class="text-muted">Not assigned</em>';
        }
    }
}

// Validate individual field
function validateField(e) {
    const field = e.target;
    const value = field.value.trim();

    if (field.hasAttribute('required') && !value) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    } else if (value) {
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
    }
}

// FIXED: Save project function
function saveProject() {
    const form = document.getElementById('project-form');
    if (!form) {
        console.error('Form not found');
        return;
    }

    // Validate form
    if (!validateForm()) {
        return;
    }

    // Show loading state
    const saveBtn = document.querySelector('button[onclick="saveProject()"]');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.classList.add('btn-loading');
        saveBtn.innerHTML = '<span class="btn-text"><i class="fas fa-save"></i> SAVE</span>';
    }

    // Add a small delay to show the loading state
    setTimeout(() => {
        form.submit();
    }, 500);
}

// FIXED: Validate entire form
function validateForm() {
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            isValid = false;
        } else {
            field.classList.add('is-valid');
            field.classList.remove('is-invalid');
        }
    });

    // Date validation
    const startDateField = document.querySelector('input[name="start_date"]');
    const endDateField = document.querySelector('input[name="end_date"]');

    if (startDateField && endDateField && startDateField.value && endDateField.value) {
        if (new Date(startDateField.value) > new Date(endDateField.value)) {
            endDateField.classList.add('is-invalid');
            showAlert('End date must be after start date.', 'danger');
            isValid = false;
        }
    }

    if (!isValid) {
        showAlert('Please fill in all required fields correctly.', 'danger');

        // Scroll to first invalid field
        const firstInvalid = document.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
    }

    return isValid;
}

// Show alert function
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-fixed');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed alert-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Auto-save draft functionality (optional)
function saveDraft() {
    const formData = new FormData(document.getElementById('project-form'));
    const draftData = {};

    for (let [key, value] of formData.entries()) {
        draftData[key] = value;
    }

    try {
        localStorage.setItem('project_form_draft', JSON.stringify(draftData));
    } catch (e) {
        console.warn('Could not save draft to localStorage:', e);
    }
}

// Load draft on page load (optional)
function loadDraft() {
    try {
        const draftData = localStorage.getItem('project_form_draft');

        if (draftData) {
            const data = JSON.parse(draftData);

            Object.keys(data).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field && field.type !== 'hidden' && !field.value) {
                    field.value = data[key];

                    // Trigger change event for selects
                    if (field.tagName === 'SELECT') {
                        field.dispatchEvent(new Event('change'));
                    }
                }
            });

            showAlert('Draft loaded successfully', 'info');
            updatePreview();
        }
    } catch (e) {
        console.warn('Error loading draft:', e);
    }
}

// Save draft on form changes (optional)
const form = document.getElementById('project-form');
if (form) {
    form.addEventListener('input', function() {
        clearTimeout(window.draftTimeout);
        window.draftTimeout = setTimeout(saveDraft, 2000);
    });

    // Clear draft on successful submission
    form.addEventListener('submit', function() {
        try {
            localStorage.removeItem('project_form_draft');
        } catch (e) {
            console.warn('Could not clear draft:', e);
        }
    });
}

// Form auto-completion
function autoCompleteEngagement() {
    const engagementTypeField = document.querySelector('select[name="engagement_type"]');
    const periodField = document.querySelector('input[name="engagement_period"]');
    const engagementNameField = document.querySelector('input[name="engagement_name"]');

    if (!engagementTypeField || !periodField || !engagementNameField) return;

    const engagementType = engagementTypeField.value;
    const period = periodField.value;

    if (engagementType && period && !engagementNameField.value) {
        let suggestion = '';

        switch(engagementType) {
            case 'audit':
                suggestion = `Statutory audit for YE${period}`;
                break;
            case 'accounting':
                suggestion = `Accounting services for ${period}`;
                break;
            case 'tax':
                suggestion = `Tax compliance for ${period}`;
                break;
            case 'special_engagement':
                suggestion = `Special engagement for ${period}`;
                break;
            default:
                suggestion = `${engagementType} engagement for ${period}`;
        }

        engagementNameField.value = suggestion;
        engagementNameField.classList.add('auto-filled');
        updatePreview();

        setTimeout(() => {
            engagementNameField.classList.remove('auto-filled');
        }, 2000);
    }
}

// Trigger auto-completion when both type and period are set
const engagementTypeField = document.querySelector('select[name="engagement_type"]');
const periodField = document.querySelector('input[name="engagement_period"]');

if (engagementTypeField) {
    engagementTypeField.addEventListener('change', autoCompleteEngagement);
}

if (periodField) {
    periodField.addEventListener('input', autoCompleteEngagement);
}

// Handle form submission errors (if redirected back with errors)
window.addEventListener('load', function() {
    const hasErrors = document.querySelector('.alert-danger');
    if (hasErrors) {
        hasErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Additional helper functions for better UX
function resetForm() {
    const form = document.getElementById('project-form');
    if (form) {
        form.reset();

        // Clear validation classes
        document.querySelectorAll('.is-valid, .is-invalid').forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });

        // Reset preview
        updatePreview();

        showAlert('Form reset successfully', 'info');
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveProject();
    }

    // Escape to reset form (optional)
    if (e.key === 'Escape' && e.shiftKey) {
        e.preventDefault();
        if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
            resetForm();
        }
    }
});

// Form change detection for unsaved changes warning
let formChanged = false;

document.getElementById('project-form').addEventListener('input', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        const message = 'You have unsaved changes. Are you sure you want to leave?';
        e.returnValue = message;
        return message;
    }
});

// Clear form changed flag on successful submission
document.getElementById('project-form').addEventListener('submit', function() {
    formChanged = false;
});

// Auto-focus first required field
setTimeout(() => {
    const firstRequiredField = document.querySelector('input[required], select[required]');
    if (firstRequiredField && !firstRequiredField.value) {
        firstRequiredField.focus();
    }
}, 100);
</script>
@endsection
