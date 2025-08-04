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
                            <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required onchange="updateJobIdPreview()">
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
                               id="job_id_field"
                               class="form-control @error('job_id') is-invalid @enderror"
                               value="{{ old('job_id') }}"
                               placeholder="Will be auto-generated"
                               readonly>
                        <div class="form-text">
                            <div id="job-id-format" class="text-muted">
                                Format: <code>CLIENT-YR_ENGAGED-SERIES-TYPE-JOB_YR</code>
                            </div>
                            <div id="job-id-breakdown" class="text-info mt-1" style="display: none;">
                                <small class="d-block"><strong>Breakdown:</strong></small>
                                <small class="d-block">• <span id="client-part">CLIENT</span>: 3-letter client initial</small>
                                <small class="d-block">• <span id="year-engaged-part">YR_ENGAGED</span>: Year client first engaged (2-digit)</small>
                                <small class="d-block">• <span id="series-part">SERIES</span>: Sequential number (3-digit)</small>
                                <small class="d-block">• <span id="type-part">TYPE</span>: Engagement type (A/AC/T/S/O)</small>
                                <small class="d-block">• <span id="job-year-part">JOB_YR</span>: Year of engagement (2-digit)</small>
                            </div>
                        </div>
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
                                id="engagement_type_field"
                                class="form-select @error('engagement_type') is-invalid @enderror"
                                required
                                onchange="updateJobIdPreview()">
                            <option value="">Select Type...</option>
                            <option value="audit" {{ old('engagement_type') == 'audit' ? 'selected' : '' }}>Audit (A)</option>
                            <option value="accounting" {{ old('engagement_type') == 'accounting' ? 'selected' : '' }}>Accounting (AC)</option>
                            <option value="tax" {{ old('engagement_type') == 'tax' ? 'selected' : '' }}>Tax (T)</option>
                            <option value="special_engagement" {{ old('engagement_type') == 'special_engagement' ? 'selected' : '' }}>Special Engagement (S)</option>
                            <option value="others" {{ old('engagement_type') == 'others' ? 'selected' : '' }}>Others (O)</option>
                        </select>
                        @error('engagement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Engagement Period (Year)</label>
                        <input type="number"
                               name="engagement_period"
                               id="engagement_period_field"
                               class="form-control @error('engagement_period') is-invalid @enderror"
                               value="{{ old('engagement_period', date('Y')) }}"
                               min="2020"
                               max="2030"
                               placeholder="e.g., 2024"
                               onchange="updateJobIdPreview()">
                        <div class="form-text">This will be used as the job year in the Job ID</div>
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

    <!-- Enhanced Preview Section -->
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
                            <td>
                                <div id="preview-job-id">
                                    <em class="text-muted">Will be auto-generated</em>
                                </div>
                                <div id="preview-job-breakdown" class="mt-1" style="display: none;">
                                    <small class="text-info">
                                        <span id="breakdown-client" class="fw-bold text-primary">ABC</span>-<span id="breakdown-year-engaged" class="fw-bold text-success">22</span>-<span id="breakdown-series" class="fw-bold text-warning">001</span>-<span id="breakdown-type" class="fw-bold text-info">A</span>-<span id="breakdown-job-year" class="fw-bold text-danger">24</span>
                                    </small>
                                </div>
                            </td>
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

/* Job ID specific styling */
#job_id_field {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    letter-spacing: 1px;
}

#job-id-breakdown {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 0.375rem;
    border-left: 4px solid #0d6efd;
}

#job-id-breakdown small {
    line-height: 1.4;
}

/* Preview breakdown styling */
#preview-job-breakdown {
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
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

/* Job ID generation animation */
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

/* Auto-fill animation */
.auto-filled {
    background-color: #e8f5e8 !important;
    transition: background-color 0.3s ease;
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

    #job-id-breakdown {
        padding: 0.5rem;
    }

    #preview-job-breakdown {
        font-size: 1rem;
    }
}

/* Enhanced code styling */
code {
    background-color: #f8f9fa;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    color: #e83e8c;
}

/* Breakdown color coding */
.breakdown-part {
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    margin: 0 0.125rem;
}

.breakdown-client { background-color: rgba(13, 110, 253, 0.1); }
.breakdown-year { background-color: rgba(25, 135, 84, 0.1); }
.breakdown-series { background-color: rgba(255, 193, 7, 0.1); }
.breakdown-type { background-color: rgba(13, 202, 240, 0.1); }
.breakdown-job-year { background-color: rgba(220, 53, 69, 0.1); }
</style>
@endsection

@section('scripts')
<script>
// Global variables for client data
let clientsData = @json($clients ?? []);

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form listeners
    initializeFormListeners();

    // Update preview on page load
    updatePreview();

    // Show job ID breakdown initially
    toggleJobIdBreakdown(true);

    // Initialize job ID preview if client is preselected
    @if($preselectedClient)
        updateJobIdPreview();
    @endif
});

// Initialize all form event listeners
function initializeFormListeners() {
    // Engagement name preview and sync with hidden name field
    const engagementNameField = document.querySelector('input[name="engagement_name"]');
    if (engagementNameField) {
        engagementNameField.addEventListener('input', function() {
            updatePreview();
            // Sync engagement name with hidden name field
            const hiddenNameField = document.getElementById('hidden-name');
            if (hiddenNameField) {
                hiddenNameField.value = this.value;
            }
        });
    }

    // Engagement type preview and Job ID update
    const engagementTypeField = document.querySelector('select[name="engagement_type"]');
    if (engagementTypeField) {
        engagementTypeField.addEventListener('change', function() {
            updatePreview();
            updateJobIdPreview();
        });
    }

    // Engagement period preview and Job ID update
    const engagementPeriodField = document.querySelector('input[name="engagement_period"]');
    if (engagementPeriodField) {
        engagementPeriodField.addEventListener('input', function() {
            updatePreview();
            updateJobIdPreview();
        });
    }

    // Team assignment previews
    const teamFields = ['engagement_partner_id', 'manager_id', 'associate_1', 'associate_2'];
    teamFields.forEach(fieldName => {
        const field = document.querySelector(`select[name="${fieldName}"]`);
        if (field) {
            field.addEventListener('change', updatePreview);
        }
    });

    // Client selection (if not pre-selected)
    const clientField = document.querySelector('select[name="client_id"]');
    if (clientField) {
        clientField.addEventListener('change', function() {
            updatePreview();
            updateJobIdPreview();
        });
    }

    // Form validation on input
    document.querySelectorAll('input[required], select[required]').forEach(field => {
        field.addEventListener('blur', validateField);
        field.addEventListener('input', validateField);
    });
}

// Enhanced Job ID preview function with new format
function updateJobIdPreview() {
    const clientSelect = document.querySelector('select[name="client_id"]');
    const engagementTypeSelect = document.getElementById('engagement_type_field');
    const engagementPeriodInput = document.getElementById('engagement_period_field');
    const jobIdField = document.getElementById('job_id_field');
    const previewJobId = document.getElementById('preview-job-id');
    const previewBreakdown = document.getElementById('preview-job-breakdown');

    // Get selected values
    const clientId = clientSelect ? clientSelect.value : @json($preselectedClient->id ?? null);
    const engagementType = engagementTypeSelect ? engagementTypeSelect.value : '';
    const engagementPeriod = engagementPeriodInput ? engagementPeriodInput.value : new Date().getFullYear();

    if (!clientId || !engagementType) {
        jobIdField.value = '';
        previewJobId.innerHTML = '<em class="text-muted">Select client and engagement type</em>';
        previewBreakdown.style.display = 'none';
        updateJobIdBreakdownDetails('', '', '', '', '');
        return;
    }

    // Get client data
    let selectedClient = null;
    @if($preselectedClient)
        selectedClient = @json($preselectedClient);
    @else
        selectedClient = clientsData.find(client => client.id == clientId);
    @endif

    if (!selectedClient) {
        jobIdField.value = '';
        previewJobId.innerHTML = '<em class="text-muted">Client not found</em>';
        previewBreakdown.style.display = 'none';
        return;
    }

    // Show generating state
    jobIdField.classList.add('job-id-generating');
    previewJobId.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

    // Generate job ID components
    setTimeout(() => {
        const clientInitial = getClientInitial(selectedClient.company_name);
        const yearEngaged = getYearEngaged(selectedClient);
        const series = '001'; // Will be calculated on backend
        const typeCode = getEngagementTypeCode(engagementType);
        const jobYear = String(engagementPeriod).slice(-2).padStart(2, '0');

        const jobId = `${clientInitial}-${yearEngaged}-${series}-${typeCode}-${jobYear}`;

        // Update fields
        jobIdField.value = jobId;
        jobIdField.classList.remove('job-id-generating');
        jobIdField.classList.add('auto-filled');

        // Update preview
        previewJobId.innerHTML = `<span class="preview-value fw-bold">${jobId}</span>`;
        previewBreakdown.style.display = 'block';

        // Update breakdown components
        updateJobIdBreakdownDetails(clientInitial, yearEngaged, series, typeCode, jobYear);

        // Update breakdown explanation
        updateJobIdBreakdownExplanation(clientInitial, yearEngaged, typeCode, selectedClient, engagementType);

        // Remove auto-filled class after animation
        setTimeout(() => {
            jobIdField.classList.remove('auto-filled');
        }, 2000);
    }, 800);
}

// Get 3-letter client initial
function getClientInitial(companyName) {
    if (!companyName) return 'ABC';

    // Remove common business terms and get meaningful letters
    const cleanName = companyName
        .replace(/\b(Corporation|Corp|Company|Co|Inc|Incorporated|Ltd|Limited|LLC|LLP)\b/gi, '')
        .replace(/[^a-zA-Z\s]/g, '')
        .trim();

    const words = cleanName.split(/\s+/).filter(word => word.length > 0);

    if (words.length === 0) {
        return companyName.substring(0, 3).toUpperCase().padEnd(3, 'X');
    } else if (words.length === 1) {
        return words[0].substring(0, 3).toUpperCase().padEnd(3, 'X');
    } else if (words.length === 2) {
        return (words[0].charAt(0) + words[1].substring(0, 2)).toUpperCase().padEnd(3, 'X');
    } else {
        return (words[0].charAt(0) + words[1].charAt(0) + words[2].charAt(0)).toUpperCase();
    }
}

// Get year engaged (2-digit)
function getYearEngaged(client) {
    // Use year_engaged if available, otherwise use creation year
    const year = client.year_engaged || new Date(client.created_at).getFullYear() || new Date().getFullYear();
    return String(year).slice(-2).padStart(2, '0');
}

// Get engagement type code
function getEngagementTypeCode(engagementType) {
    const codes = {
        'audit': 'A',
        'accounting': 'AC',
        'tax': 'T',
        'special_engagement': 'S',
        'others': 'O'
    };
    return codes[engagementType] || 'A';
}

// Update breakdown visual components
function updateJobIdBreakdownDetails(clientInitial, yearEngaged, series, typeCode, jobYear) {
    const elements = {
        'breakdown-client': clientInitial,
        'breakdown-year-engaged': yearEngaged,
        'breakdown-series': series,
        'breakdown-type': typeCode,
        'breakdown-job-year': jobYear
    };

    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });

    // Also update the breakdown explanation parts
    const explanationElements = {
        'client-part': clientInitial,
        'year-engaged-part': yearEngaged,
        'series-part': series,
        'type-part': typeCode,
        'job-year-part': jobYear
    };

    Object.entries(explanationElements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });
}

// Update detailed breakdown explanation
function updateJobIdBreakdownExplanation(clientInitial, yearEngaged, typeCode, client, engagementType) {
    const fullYearEngaged = 2000 + parseInt(yearEngaged);
    const engagementTypeDisplay = getEngagementTypeDisplay(engagementType);

    const explanations = [
        `${clientInitial}: ${client.company_name} initial`,
        `${yearEngaged}: Client engaged since ${fullYearEngaged}`,
        `001: First ${engagementTypeDisplay.toLowerCase()} engagement`,
        `${typeCode}: ${engagementTypeDisplay} engagement`,
        `${document.getElementById('engagement_period_field')?.value || new Date().getFullYear().toString().slice(-2)}: Year ${document.getElementById('engagement_period_field')?.value || new Date().getFullYear()}`
    ];

    // Update tooltip or additional info if needed
    const jobIdField = document.getElementById('job_id_field');
    if (jobIdField) {
        jobIdField.title = explanations.join(' | ');
    }
}

// Get engagement type display name
function getEngagementTypeDisplay(engagementType) {
    const displays = {
        'audit': 'Audit',
        'accounting': 'Accounting',
        'tax': 'Tax',
        'special_engagement': 'Special Engagement',
        'others': 'Others'
    };
    return displays[engagementType] || 'Audit';
}

// Toggle job ID breakdown visibility
function toggleJobIdBreakdown(show = null) {
    const breakdown = document.getElementById('job-id-breakdown');
    if (!breakdown) return;

    if (show === null) {
        breakdown.style.display = breakdown.style.display === 'none' ? 'block' : 'none';
    } else {
        breakdown.style.display = show ? 'block' : 'none';
    }
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

// Save project function
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

// Validate entire form
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

// Auto-completion based on engagement type and period
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
                suggestion = `${getEngagementTypeDisplay(engagementType)} engagement for ${period}`;
        }

        engagementNameField.value = suggestion;
        engagementNameField.classList.add('auto-filled');
        updatePreview();

        // Update hidden name field
        const hiddenNameField = document.getElementById('hidden-name');
        if (hiddenNameField) {
            hiddenNameField.value = suggestion;
        }

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

// Enhanced Job ID tooltip
function addJobIdTooltip() {
    const jobIdField = document.getElementById('job_id_field');
    if (jobIdField) {
        jobIdField.addEventListener('mouseenter', function() {
            if (this.value) {
                const parts = this.value.split('-');
                if (parts.length === 5) {
                    const tooltip = `
                        Client: ${parts[0]} |
                        Engaged: 20${parts[1]} |
                        Series: ${parts[2]} |
                        Type: ${parts[3]} |
                        Year: 20${parts[4]}
                    `;
                    this.setAttribute('data-bs-toggle', 'tooltip');
                    this.setAttribute('title', tooltip);
                }
            }
        });
    }
}

// Initialize tooltip
document.addEventListener('DOMContentLoaded', function() {
    addJobIdTooltip();
});

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

        // Reset job ID
        document.getElementById('job_id_field').value = '';
        document.getElementById('preview-job-id').innerHTML = '<em class="text-muted">Will be auto-generated</em>';
        document.getElementById('preview-job-breakdown').style.display = 'none';

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

// Debug function for job ID generation
function debugJobIdGeneration() {
    const clientSelect = document.querySelector('select[name="client_id"]');
    const engagementType = document.getElementById('engagement_type_field').value;
    const engagementPeriod = document.getElementById('engagement_period_field').value;

    console.log('=== JOB ID DEBUG ===');
    console.log('Client ID:', clientSelect ? clientSelect.value : 'Preselected');
    console.log('Engagement Type:', engagementType);
    console.log('Engagement Period:', engagementPeriod);

    if (clientSelect && clientSelect.value) {
        const selectedClient = clientsData.find(c => c.id == clientSelect.value);
        console.log('Selected Client:', selectedClient);
        console.log('Client Initial:', getClientInitial(selectedClient.company_name));
        console.log('Year Engaged:', getYearEngaged(selectedClient));
        console.log('Type Code:', getEngagementTypeCode(engagementType));
        console.log('Job Year:', String(engagementPeriod).slice(-2).padStart(2, '0'));
    }
    console.log('=== END DEBUG ===');
}

// Add debug button in development mode
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    document.addEventListener('DOMContentLoaded', function() {
        const debugBtn = document.createElement('button');
        debugBtn.type = 'button';
        debugBtn.className = 'btn btn-outline-info btn-sm position-fixed';
        debugBtn.style.cssText = 'bottom: 20px; left: 20px; z-index: 9999;';
        debugBtn.innerHTML = 'Debug Job ID';
        debugBtn.onclick = debugJobIdGeneration;
        document.body.appendChild(debugBtn);
    });
}
</script>
@endsection
