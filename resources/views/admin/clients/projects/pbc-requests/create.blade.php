@extends('layouts.app')
@section('title', 'Create PBC Request - ' . $project->engagement_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Document Request List</h1>
        <div class="text-muted mb-0">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span><strong>Company:</strong> {{ $client->company_name }}</span>
                <span class="text-muted">|</span>
                <span>
                    <strong>Job ID:</strong>
                    @if($project->job_id)
                        @php $jobIdBreakdown = $project->getJobIdBreakdownAttribute(); @endphp
                        <span class="job-id-display fw-bold text-primary" title="Click to copy">{{ $project->job_id }}</span>
                        @if($jobIdBreakdown)
                            <small class="text-muted d-block d-md-inline ms-md-2">
                                ({{ $jobIdBreakdown['client_initial'] }}: {{ $client->company_name }} |
                                {{ $jobIdBreakdown['job_type_code'] }}: {{ ucfirst($project->engagement_type) }} |
                                FY{{ $jobIdBreakdown['year_of_job'] }})
                            </small>
                        @endif
                    @else
                        <span class="text-danger">{{ $project->job_id ?? '1-01-001' }}</span>
                    @endif
                </span>
                <span class="text-muted">|</span>
                <span><strong>Engagement Name:</strong> {{ $project->engagement_name ?? 'Statutory audit for YE122024' }}</span>
                <span class="text-muted">|</span>
                <span><strong>Type of Engagement:</strong> {{ ucfirst($project->engagement_type ?? 'Audit') }}</span>
                <span class="text-muted">|</span>
                <span><strong>Engagement Period:</strong> {{ $project->engagement_period_start?->format('m/d/Y') ?? '31/12/2024' }}</span>
            </div>
        </div>
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

<!-- Enhanced Project Info Card -->
@if($project->job_id && $project->getJobIdBreakdownAttribute())
    @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="text-muted mb-3">Project Overview</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-building text-primary me-2"></i>
                                <strong>Client:</strong>
                                <span class="ms-2">{{ $client->company_name }}</span>
                                <span class="badge bg-light text-dark ms-2">{{ $breakdown['client_initial'] }}</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-briefcase text-info me-2"></i>
                                <strong>Engagement:</strong>
                                <span class="ms-2">{{ $breakdown['job_type'] }} ({{ $breakdown['job_type_code'] }})</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-check text-danger me-2"></i>
                                <strong>Financial Year:</strong>
                                <span class="ms-2">{{ $breakdown['year_of_job'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar text-success me-2"></i>
                                <strong>Client Since:</strong>
                                <span class="ms-2">{{ $breakdown['year_engaged'] }}</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-hashtag text-warning me-2"></i>
                                <strong>Series:</strong>
                                <span class="ms-2">#{{ $breakdown['series'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="job-id-visual p-3 bg-light rounded">
                            <h6 class="text-muted mb-2">Job ID: {{ $project->job_id }}</h6>
                            <div class="job-id-parts">
                                <span class="job-part client-part" title="Client: {{ $client->company_name }}">{{ $breakdown['client_initial'] }}</span>-<span class="job-part year-part" title="Year Engaged: {{ $breakdown['year_engaged'] }}">{{ substr($breakdown['year_engaged'], -2) }}</span>-<span class="job-part series-part" title="Series: {{ $breakdown['series'] }}">{{ $breakdown['series'] }}</span>-<span class="job-part type-part" title="Type: {{ $breakdown['job_type'] }}">{{ $breakdown['job_type_code'] }}</span>-<span class="job-part job-year-part" title="Job Year: {{ $breakdown['year_of_job'] }}">{{ substr($breakdown['year_of_job'], -2) }}</span>
                            </div>
                            <small class="text-muted d-block mt-2">Click parts for details</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

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

    <!-- Main Request Details Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company</label>
                        <input type="text" class="form-control" value="{{ $client->company_name }}" readonly>
                        @if($project->job_id && $project->getJobIdBreakdownAttribute())
                            @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
                            <div class="form-text">
                                <span class="badge bg-light text-dark">{{ $breakdown['client_initial'] }}</span>
                                <small class="text-muted ms-2">Client code in Job ID</small>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Job ID</label>
                        <div class="input-group">
                            <input type="text" class="form-control job-id-input" value="{{ $project->job_id ?? '1-01-001' }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyJobId()" title="Copy Job ID">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        @if($project->job_id && $project->getJobIdBreakdownAttribute())
                            @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
                            <div class="form-text">
                                <small class="text-info">
                                    Format: <span class="fw-bold">{{ $breakdown['client_initial'] }}</span> (Client) -
                                    <span class="fw-bold">{{ substr($breakdown['year_engaged'], -2) }}</span> (Engaged) -
                                    <span class="fw-bold">{{ $breakdown['series'] }}</span> (Series) -
                                    <span class="fw-bold">{{ $breakdown['job_type_code'] }}</span> (Type) -
                                    <span class="fw-bold">{{ substr($breakdown['year_of_job'], -2) }}</span> (Year)
                                </small>
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Engagement Name</label>
                        <input type="text" class="form-control" value="{{ $project->engagement_name ?? 'Statutory audit for YE122024' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Type of Engagement</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ ucfirst($project->engagement_type ?? 'audit') }}" readonly>
                            @if($project->job_id && $project->getJobIdBreakdownAttribute())
                                @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
                                <span class="input-group-text">
                                    <span class="badge bg-info">{{ $breakdown['job_type_code'] }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Engagement Period</label>
                        <input type="text" class="form-control" value="{{ $project->engagement_period_start?->format('m/d/Y') ?? '31/12/2024' }}" readonly>
                        @if($project->job_id && $project->getJobIdBreakdownAttribute())
                            @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
                            <div class="form-text">
                                <span class="badge bg-danger">FY {{ $breakdown['year_of_job'] }}</span>
                                <small class="text-muted ms-2">Financial Year in Job ID</small>
                            </div>
                        @endif
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

    <!-- Hidden fields for backend processing -->
    <input type="hidden" name="client_id" value="{{ $client->id }}">
    <input type="hidden" name="project_id" value="{{ $project->id }}">
    <!-- Hidden fields to maintain backend compatibility -->
    <input type="hidden" name="title" value="{{ $project->engagement_name ?? 'Statutory audit for YE122024' }}">
    <input type="hidden" name="description" value="">
    <input type="hidden" name="due_date" value="">

</form>

@endsection

@section('styles')
<style>
/* Job ID specific styling */
.job-id-input {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    letter-spacing: 1px;
    color: #0d6efd;
}

.job-id-display {
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    letter-spacing: 1px;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
}

/* Job ID Visual Breakdown */
.job-id-visual {
    border: 2px dashed #dee2e6;
    transition: all 0.3s ease;
}

.job-id-parts {
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 1px;
}

.job-part {
    padding: 0.25rem 0.4rem;
    border-radius: 0.25rem;
    margin: 0 0.1rem;
    transition: all 0.2s ease;
    cursor: help;
}

.client-part {
    background-color: rgba(13, 110, 253, 0.15);
    color: #0d6efd;
}

.year-part {
    background-color: rgba(25, 135, 84, 0.15);
    color: #198754;
}

.series-part {
    background-color: rgba(255, 193, 7, 0.15);
    color: #ffc107;
}

.type-part {
    background-color: rgba(13, 202, 240, 0.15);
    color: #0dcaf0;
}

.job-year-part {
    background-color: rgba(220, 53, 69, 0.15);
    color: #dc3545;
}

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

/* Project info icons */
.fas.text-primary { color: #0d6efd !important; }
.fas.text-success { color: #198754 !important; }
.fas.text-info { color: #0dcaf0 !important; }
.fas.text-warning { color: #ffc107 !important; }
.fas.text-danger { color: #dc3545 !important; }

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

/* Copy button animation */
.btn-copy-success {
    animation: copy-success 0.5s ease-in-out;
}

@keyframes copy-success {
    0% { background-color: transparent; }
    50% { background-color: rgba(25, 135, 84, 0.2); }
    100% { background-color: transparent; }
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

    .job-id-display {
        font-size: 1rem;
        letter-spacing: 0.5px;
    }

    .job-id-parts {
        font-size: 0.9rem;
        letter-spacing: 0.5px;
    }

    .job-part {
        padding: 0.125rem 0.25rem;
        margin: 0 0.05rem;
    }

    .job-id-visual {
        padding: 1rem !important;
    }

    /* Stack project info vertically on mobile */
    .d-flex.flex-wrap.align-items-center.gap-2 {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }

    .d-flex.flex-wrap.align-items-center.gap-2 > span {
        display: block;
        width: 100%;
    }

    .text-muted {
        display: none !important;
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

/* Input group enhancements */
.input-group .btn {
    border-color: #ced4da;
}

/* Form text enhancements */
.form-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.form-text .badge {
    font-size: 0.7rem;
}
</style>
@endsection

@section('scripts')
<script>
let itemIndex = 1;

document.addEventListener('DOMContentLoaded', function() {
    updateSummary();
    initializeJobIdCopy();
    console.log('Page loaded successfully');
    console.log('Form action:', document.getElementById('pbc-request-form').action);
    console.log('CSRF token:', document.querySelector('input[name="_token"]').value);
});

// Initialize Job ID copy functionality
function initializeJobIdCopy() {
    // Main job ID display
    const jobIdDisplay = document.querySelector('.job-id-display');
    if (jobIdDisplay) {
        jobIdDisplay.addEventListener('click', function() {
            const jobId = this.textContent.trim();
            copyToClipboard(jobId);
        });
    }

    // Add click functionality to job parts for detailed info
    const jobParts = document.querySelectorAll('.job-part');
    jobParts.forEach(part => {
        part.addEventListener('click', function() {
            const partValue = this.textContent.trim();
            const partTitle = this.getAttribute('title');
            showAlert(`${partTitle}: ${partValue}`, 'info');
        });
    });
}

// Copy Job ID function
function copyJobId() {
    const jobIdInput = document.querySelector('.job-id-input');
    const copyBtn = event.target.closest('button');

    if (jobIdInput) {
        const jobId = jobIdInput.value;
        copyToClipboard(jobId);

        // Visual feedback
        copyBtn.classList.add('btn-copy-success');
        setTimeout(() => {
            copyBtn.classList.remove('btn-copy-success');
        }, 500);
    }
}

// Copy to clipboard function
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert(`Job ID "${text}" copied to clipboard!`, 'success');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showAlert('Failed to copy Job ID to clipboard', 'warning');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
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
    saveBtn.innerHTML = '<span class="btn-text"><i class="fas fa-save"></i> SAVE</span>';

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

// Update summary function (simplified since visual summary is removed)
function updateSummary() {
    const rows = document.querySelectorAll('.request-item-row');
    console.log(`Summary updated: ${rows.length} items`);
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

    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };

    alertDiv.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
        ${formattedMessage}
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

    // Ctrl+C on job ID elements to copy
    if (e.ctrlKey && e.key === 'c') {
        const activeElement = document.activeElement;
        if (activeElement && (activeElement.classList.contains('job-id-display') || activeElement.classList.contains('job-id-input'))) {
            e.preventDefault();
            const textToCopy = activeElement.textContent || activeElement.value;
            copyToClipboard(textToCopy.trim());
        }
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

// Enhanced job ID interactions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips for job parts
    const jobParts = document.querySelectorAll('.job-part');
    jobParts.forEach(part => {
        // Initialize Bootstrap tooltip if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(part);
        }
    });

    // Add focus/blur handlers for job ID input
    const jobIdInput = document.querySelector('.job-id-input');
    if (jobIdInput) {
        jobIdInput.addEventListener('focus', function() {
            this.select();
        });
    }
});
</script>
@endsection
