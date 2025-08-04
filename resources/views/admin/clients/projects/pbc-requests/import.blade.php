@extends('layouts.app')
@section('title', 'Import PBC Requests - ' . $project->engagement_name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Import PBC Requests</h1>
            <div class="text-muted mb-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span><strong>{{ $client->company_name }}</strong></span>
                    <span class="text-muted">|</span>
                    <span><strong>{{ $project->engagement_name }}</strong></span>
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
                            <span class="text-danger">Not Generated</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.projects.pbc-requests.import.template', [$client, $project]) }}"
               class="btn btn-outline-info">
                <i class="fas fa-download"></i> Download Template
            </a>
            <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Requests
            </a>
        </div>
    </div>

    <!-- Enhanced Project Info Card -->
    @if($project->job_id && $project->getJobIdBreakdownAttribute())
        @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Project Context
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    <strong>Client:</strong>
                                    <span class="ms-2">{{ $client->company_name }}</span>
                                    <span class="badge bg-light text-dark ms-2">{{ $breakdown['client_initial'] }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar text-success me-2"></i>
                                    <strong>Engaged Since:</strong>
                                    <span class="ms-2">{{ $breakdown['year_engaged'] }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-briefcase text-info me-2"></i>
                                    <strong>Engagement Type:</strong>
                                    <span class="ms-2">{{ $breakdown['job_type'] }} ({{ $breakdown['job_type_code'] }})</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-hashtag text-warning me-2"></i>
                                    <strong>Series:</strong>
                                    <span class="ms-2">#{{ $breakdown['series'] }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar-check text-danger me-2"></i>
                                    <strong>Financial Year:</strong>
                                    <span class="ms-2">{{ $breakdown['year_of_job'] }}</span>
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
                                <small class="text-muted d-block mt-2">Click to copy</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Import Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-upload me-2"></i>Import Details
                    </h5>
                </div>
                <div class="card-body">
                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="project_id" value="{{ $project->id }}">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Request Title *</label>
                            <input type="text"
                                   name="title"
                                   class="form-control"
                                   placeholder="e.g., Year-end PBC Request 2024"
                                   value="{{ $project->engagement_name ?? 'PBC Request' }} - {{ date('Y') }}"
                                   required>
                            <div class="form-text">
                                <small class="text-muted">
                                    Default title based on engagement name and current year
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Brief description of this PBC request">PBC Request for {{ $project->engagement_name ?? 'project' }} (Job ID: {{ $project->job_id ?? 'TBD' }})</textarea>
                            <div class="form-text">
                                <small class="text-muted">
                                    Includes project context and Job ID for reference
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Due Date</label>
                            <input type="date"
                                   name="due_date"
                                   class="form-control"
                                   min="{{ date('Y-m-d') }}"
                                   value="{{ date('Y-m-d', strtotime('+2 weeks')) }}">
                            <div class="form-text">
                                <small class="text-muted">
                                    Default: 2 weeks from today. Adjust as needed.
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Excel File *</label>
                            <input type="file"
                                   name="excel_file"
                                   class="form-control form-control-lg"
                                   accept=".xlsx,.xls,.csv"
                                   required
                                   onchange="validateFile(this)">
                            <div class="form-text">
                                <small class="text-success d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Supported formats:</strong> .xlsx, .xls, .csv (Max: 10MB)
                                </small>
                                <small class="text-muted d-block">
                                    Use the template provided above for best results
                                </small>
                            </div>
                            <div id="fileValidation" class="mt-2" style="display: none;"></div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button"
                                    class="btn btn-primary btn-lg"
                                    onclick="previewImport()"
                                    id="previewBtn">
                                <i class="fas fa-eye me-2"></i>Preview Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Import Instructions
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="text-success">File Format Requirements:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Column A:</strong> Category (CF or PF)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Column B:</strong> Request Description
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Column C:</strong> Assigned To (optional)
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Column D:</strong> Due Date (optional)
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Column E:</strong> Required (TRUE/FALSE)
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Categories:</strong>
                                <br>• <strong>CF</strong> = Confirmed by Firm
                                <br>• <strong>PF</strong> = Provided by Firm
                            </div>
                            <div class="col-md-6">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Tips:</strong>
                                <br>• First row should contain headers
                                <br>• Leave cells empty if optional
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-download me-2"></i>
                        <strong>Need a template?</strong>
                        <a href="{{ route('admin.clients.projects.pbc-requests.import.template', [$client, $project]) }}"
                           class="btn btn-sm btn-outline-info ms-2">
                            Download Excel Template
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>Import Preview
                    </h5>
                </div>
                <div class="card-body">
                    <div id="previewContent">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-file-import fa-4x mb-3 opacity-50"></i>
                            <h5>No Data to Preview</h5>
                            <p class="mb-3">Upload a file and click "Preview Import" to see the data that will be imported.</p>
                            <div class="alert alert-light">
                                <small class="text-muted">
                                    <strong>What happens during preview:</strong><br>
                                    • File format validation<br>
                                    • Data structure analysis<br>
                                    • Error detection and reporting<br>
                                    • Import statistics calculation
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Card (Hidden initially) -->
            <div id="progressCard" class="card border-0 shadow-sm mt-4" style="display: none;">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Import Progress
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Processing...</span>
                            <span class="small" id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div id="progressBar"
                                 class="progress-bar progress-bar-striped progress-bar-animated"
                                 role="progressbar"
                                 style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="progressStatus" class="text-muted small">
                        Initializing import process...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Confirmation Modal -->
<div class="modal fade" id="confirmImportModal" tabindex="-1" aria-labelledby="confirmImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmImportModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Confirm Import
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-8">
                            <i class="fas fa-info-circle me-2"></i>
                            You are about to import <strong><span id="importCount">0</span></strong> PBC request items.
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-info fs-6" id="importJobId">{{ $project->job_id ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Project Details:</h6>
                        <ul class="list-unstyled small">
                            <li><strong>Client:</strong> {{ $client->company_name }}</li>
                            <li><strong>Engagement:</strong> {{ $project->engagement_name }}</li>
                            <li><strong>Job ID:</strong> {{ $project->job_id ?? 'Not Generated' }}</li>
                            @if($project->job_id && $project->getJobIdBreakdownAttribute())
                                @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
                                <li><strong>Type:</strong> {{ $breakdown['job_type'] }} ({{ $breakdown['job_type_code'] }})</li>
                                <li><strong>Year:</strong> {{ $breakdown['year_of_job'] }}</li>
                            @endif
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Import Summary:</h6>
                        <div id="importSummary">
                            <!-- Summary will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Before proceeding:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Ensure all data is correct and complete</li>
                        <li>This action cannot be easily undone</li>
                        <li>Existing PBC requests will not be affected</li>
                        <li>Duplicate items will be detected and skipped</li>
                    </ul>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmCheckbox" required>
                    <label class="form-check-label" for="confirmCheckbox">
                        I have reviewed the data and confirm this import
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="executeImport()" id="executeImportBtn" disabled>
                    <i class="fas fa-upload me-2"></i>Import Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Import Successful
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h4 class="text-success">Import Completed!</h4>
                <p class="mb-3">
                    <strong><span id="successCount">0</span></strong> PBC request items have been successfully imported.
                </p>
                <div class="alert alert-success">
                    <small>
                        You can now view and manage these requests in the PBC Requests section.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}"
                   class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>View PBC Requests
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Job ID Display Styling */
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
    cursor: pointer;
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

/* Card styling */
.card {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
}

.card-header.bg-info {
    background: linear-gradient(135deg, #0dcaf0 0%, #0aa5c0 100%);
}

.card-header.bg-success {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%);
}

.card-header.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
}

/* Form styling */
.form-control, .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1.1rem;
}

/* Preview table styling */
.preview-table {
    font-size: 0.9rem;
}

.preview-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    color: #495057;
}

.preview-table td {
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

/* Badge styling */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}

.badge.bg-primary { background-color: #0d6efd !important; }
.badge.bg-secondary { background-color: #6c757d !important; }
.badge.bg-success { background-color: #198754 !important; }
.badge.bg-warning { background-color: #ffc107 !important; }
.badge.bg-danger { background-color: #dc3545 !important; }
.badge.bg-info { background-color: #0dcaf0 !important; }

/* Alert styling */
.alert {
    border-radius: 0.5rem;
    border: none;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    color: #856404;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
}

/* Button styling */
.btn {
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

/* Loading overlay */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 0.5rem;
}

.spinner-border-lg {
    width: 3rem;
    height: 3rem;
}

/* Progress styling */
.progress {
    height: 8px;
    border-radius: 1rem;
    background-color: rgba(0,0,0,0.1);
}

.progress-bar {
    border-radius: 1rem;
    transition: width 0.3s ease;
}

/* Modal enhancements */
.modal-content {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header.bg-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
}

.modal-header.bg-success {
    background: linear-gradient(135deg, #198754 0%, #146c43 100%) !important;
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* File validation styling */
.file-valid {
    border-color: #198754 !important;
    background-color: rgba(25, 135, 84, 0.05);
}

.file-invalid {
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.05);
}

/* Animation classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Project info icons */
.fas.text-primary { color: #0d6efd !important; }
.fas.text-success { color: #198754 !important; }
.fas.text-info { color: #0dcaf0 !important; }
.fas.text-warning { color: #ffc107 !important; }
.fas.text-danger { color: #dc3545 !important; }

/* Loading states */
.btn-loading {
    position: relative;
    color: transparent !important;
}

.btn-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 1.2rem;
    height: 1.2rem;
    margin: -0.6rem 0 0 -0.6rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    color: white;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive design */
@media (max-width: 768px) {
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

    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }

    .modal-lg {
        max-width: 95%;
        margin: 1rem auto;
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

/* Success icon animation */
.success-icon {
    animation: successPulse 0.6s ease-in-out;
}

@keyframes successPulse {
    0% { transform: scale(0.8); opacity: 0; }
    50% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

/* Custom checkbox styling */
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Enhanced form text */
.form-text {
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.form-text .badge {
    font-size: 0.7rem;
}
</style>
@endsection

@section('scripts')
<script>
let previewData = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize job ID copy functionality
    initializeJobIdCopy();

    // Initialize confirmation checkbox
    const confirmCheckbox = document.getElementById('confirmCheckbox');
    const executeBtn = document.getElementById('executeImportBtn');

    if (confirmCheckbox && executeBtn) {
        confirmCheckbox.addEventListener('change', function() {
            executeBtn.disabled = !this.checked;
        });
    }

    console.log('Import page loaded successfully');
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
        jobIdDisplay.title = 'Click to copy Job ID';
    }

    // Job ID visual
    const jobIdVisual = document.querySelector('.job-id-visual');
    if (jobIdVisual) {
        jobIdVisual.addEventListener('click', function() {
            const jobId = '{{ $project->job_id ?? "N/A" }}';
            if (jobId !== 'N/A') {
                copyToClipboard(jobId);
            }
        });
    }

    // Add click functionality to job parts for detailed info
    const jobParts = document.querySelectorAll('.job-part');
    jobParts.forEach(part => {
        part.addEventListener('click', function(e) {
            e.stopPropagation();
            const partValue = this.textContent.trim();
            const partTitle = this.getAttribute('title');
            showAlert(`${partTitle}: ${partValue}`, 'info');
        });
    });
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

// File validation function
function validateFile(input) {
    const file = input.files[0];
    const validationDiv = document.getElementById('fileValidation');

    if (!file) {
        validationDiv.style.display = 'none';
        input.classList.remove('file-valid', 'file-invalid');
        return;
    }

    let isValid = true;
    let messages = [];

    // File size validation (10MB)
    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
        isValid = false;
        messages.push(`File size (${formatFileSize(file.size)}) exceeds 10MB limit`);
    }

    // File type validation
    const allowedTypes = ['.xlsx', '.xls', '.csv'];
    const fileName = file.name.toLowerCase();
    const hasValidExtension = allowedTypes.some(ext => fileName.endsWith(ext));

    if (!hasValidExtension) {
        isValid = false;
        messages.push('File type not supported. Please use .xlsx, .xls, or .csv files');
    }

    // Update UI
    if (isValid) {
        input.classList.remove('file-invalid');
        input.classList.add('file-valid');
        validationDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                File validated successfully: ${file.name} (${formatFileSize(file.size)})
            </div>
        `;
    } else {
        input.classList.remove('file-valid');
        input.classList.add('file-invalid');
        validationDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${messages.join('<br>')}
            </div>
        `;
    }

    validationDiv.style.display = 'block';
    return isValid;
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Preview import function
function previewImport() {
    console.log('Starting preview import...');

    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    const previewBtn = document.getElementById('previewBtn');
    const previewContent = document.getElementById('previewContent');

    // Validate required fields
    if (!formData.get('title') || !formData.get('excel_file')) {
        showAlert('Please fill in the title and select a file to upload.', 'warning');
        return;
    }

    // Validate file
    const fileInput = document.querySelector('input[name="excel_file"]');
    if (!validateFile(fileInput)) {
        showAlert('Please fix file validation errors before proceeding.', 'danger');
        return;
    }

    // Show loading state
    previewBtn.disabled = true;
    previewBtn.classList.add('btn-loading');
    previewBtn.innerHTML = '<span class="btn-text"><i class="fas fa-eye me-2"></i>Preview Import</span>';

    previewContent.innerHTML = `
        <div class="loading-overlay">
            <div class="text-center">
                <div class="spinner-border spinner-border-lg text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-3">
                    <h6>Processing file...</h6>
                    <small class="text-muted">Analyzing structure and validating data</small>
                </div>
            </div>
        </div>
    `;

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                     document.querySelector('input[name="_token"]')?.value;

    fetch(`{{ route('admin.clients.projects.pbc-requests.import.preview', [$client, $project]) }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Preview response:', data);

        if (data.success) {
            previewData = data.data;
            displayPreview(data.data, data.stats || {});
            showAlert(`Preview loaded successfully! Found ${data.count || data.data.length} items.`, 'success');
        } else {
            throw new Error(data.message || 'Failed to process file');
        }
    })
    .catch(error => {
        console.error('Preview error:', error);
        showError(error.message || 'Error processing file. Please try again.');
    })
    .finally(() => {
        // Reset button state
        previewBtn.disabled = false;
        previewBtn.classList.remove('btn-loading');
        previewBtn.innerHTML = '<i class="fas fa-eye me-2"></i>Preview Import';
    });
}

// Display preview function
function displayPreview(data, stats = {}) {
    const previewContent = document.getElementById('previewContent');

    if (!data || data.length === 0) {
        previewContent.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-3 text-warning"></i>
                <h5>No Valid Data Found</h5>
                <p>Please check your file format and try again.</p>
                <div class="alert alert-warning mt-3">
                    <small>
                        <strong>Common issues:</strong><br>
                        • Empty file or worksheet<br>
                        • Missing required columns<br>
                        • All rows contain invalid data
                    </small>
                </div>
            </div>
        `;
        return;
    }

    // Calculate statistics
    const totalItems = data.length;
    const cfCount = data.filter(item => item.category === 'CF').length;
    const pfCount = data.filter(item => item.category === 'PF').length;
    const requiredCount = data.filter(item => item.is_required).length;
    const optionalCount = totalItems - requiredCount;

    let tableHtml = `
        <div class="fade-in">
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center border-primary">
                        <div class="card-body">
                            <h4 class="text-primary mb-1">${totalItems}</h4>
                            <small class="text-muted">Total Items</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-info">
                        <div class="card-body">
                            <h4 class="text-info mb-1">${cfCount}</h4>
                            <small class="text-muted">Current File</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-secondary">
                        <div class="card-body">
                            <h4 class="text-secondary mb-1">${pfCount}</h4>
                            <small class="text-muted">Permanent File</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center border-warning">
                        <div class="card-body">
                            <h4 class="text-warning mb-1">${requiredCount}</h4>
                            <small class="text-muted">Required</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Preview: ${totalItems} items found</h6>
                <button class="btn btn-success" onclick="showImportModal()">
                    <i class="fas fa-check me-2"></i>Confirm Import
                </button>
            </div>

            <!-- Preview Table -->
            <div class="table-responsive">
                <table class="table table-sm preview-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Required</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    // Show first 10 items
    const displayItems = data.slice(0, 10);
    displayItems.forEach((item, index) => {
        tableHtml += `
            <tr>
                <td>
                    <span class="badge ${item.category === 'CF' ? 'bg-primary' : 'bg-secondary'}">
                        ${item.category || 'CF'}
                    </span>
                </td>
                <td>
                    <div class="text-truncate" style="max-width: 300px;" title="${item.particulars || ''}">
                        ${item.particulars || 'No description'}
                    </div>
                </td>
                <td>
                    <small class="text-muted">
                        ${item.assigned_to || 'Not specified'}
                    </small>
                </td>
                <td>
                    <small class="text-muted">
                        ${item.due_date || 'No due date'}
                    </small>
                </td>
                <td>
                    <span class="badge ${item.is_required ? 'bg-warning' : 'bg-light text-dark'}">
                        ${item.is_required ? 'Required' : 'Optional'}
                    </span>
                </td>
            </tr>
        `;
    });

    if (data.length > 10) {
        tableHtml += `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <em>... and ${data.length - 10} more items</em>
                </td>
            </tr>
        `;
    }

    tableHtml += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    previewContent.innerHTML = tableHtml;
}

// Show import modal
function showImportModal() {
    if (!previewData) {
        showAlert('No preview data available. Please preview first.', 'warning');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('confirmImportModal'));

    // Update modal content
    document.getElementById('importCount').textContent = previewData.length;
    document.getElementById('importJobId').textContent = '{{ $project->job_id ?? "N/A" }}';

    // Update summary
    const categoryCounts = previewData.reduce((counts, item) => {
        counts[item.category] = (counts[item.category] || 0) + 1;
        return counts;
    }, {});

    const requiredCount = previewData.filter(item => item.is_required).length;
    const optionalCount = previewData.length - requiredCount;

    const summaryHtml = `
        <div class="row text-center">
            <div class="col-6">
                <div class="border rounded p-2 mb-2">
                    <strong class="text-primary">${categoryCounts.CF || 0}</strong>
                    <br><small>Current File</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2 mb-2">
                    <strong class="text-secondary">${categoryCounts.PF || 0}</strong>
                    <br><small>Permanent File</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <strong class="text-warning">${requiredCount}</strong>
                    <br><small>Required</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <strong class="text-info">${optionalCount}</strong>
                    <br><small>Optional</small>
                </div>
            </div>
        </div>
    `;

    document.getElementById('importSummary').innerHTML = summaryHtml;

    // Reset checkbox
    document.getElementById('confirmCheckbox').checked = false;
    document.getElementById('executeImportBtn').disabled = true;

    modal.show();
}

// Execute import function
function executeImport() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmImportModal'));
    const executeBtn = document.getElementById('executeImportBtn');
    const progressCard = document.getElementById('progressCard');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');

    if (!previewData) {
        showAlert('No preview data available. Please preview first.', 'danger');
        return;
    }

    // Show loading state
    executeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing...';
    executeBtn.disabled = true;

    // Show progress card
    progressCard.style.display = 'block';
    progressCard.scrollIntoView({ behavior: 'smooth' });

    // Simulate progress updates
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;

        progressBar.style.width = progress + '%';
        progressPercent.textContent = Math.round(progress) + '%';

        if (progress < 30) {
            progressStatus.textContent = 'Validating data...';
        } else if (progress < 60) {
            progressStatus.textContent = 'Creating PBC request...';
        } else if (progress < 90) {
            progressStatus.textContent = 'Importing items...';
        }
    }, 200);

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                     document.querySelector('input[name="_token"]')?.value;

    fetch(`{{ route('admin.clients.projects.pbc-requests.import.execute', [$client, $project]) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            preview_data: previewData,
            title: document.querySelector('input[name="title"]').value,
            description: document.querySelector('textarea[name="description"]').value,
            due_date: document.querySelector('input[name="due_date"]').value
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Import response:', data);

        clearInterval(progressInterval);

        // Complete progress
        progressBar.style.width = '100%';
        progressPercent.textContent = '100%';
        progressStatus.textContent = 'Import completed successfully!';
        progressBar.classList.remove('bg-primary');
        progressBar.classList.add('bg-success');

        if (data.success) {
            // Hide confirmation modal
            modal.hide();

            // Show success modal
            setTimeout(() => {
                document.getElementById('successCount').textContent = data.imported_count || previewData.length;
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            }, 500);
        } else {
            throw new Error(data.message || 'Import failed');
        }
    })
    .catch(error => {
        console.error('Import error:', error);

        clearInterval(progressInterval);

        // Show error state
        progressBar.classList.remove('bg-primary');
        progressBar.classList.add('bg-danger');
        progressStatus.textContent = 'Import failed: ' + error.message;

        showAlert('Import failed: ' + error.message, 'danger');

        // Reset button
        executeBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Import Now';
        executeBtn.disabled = false;
    });
}

// Show error function
function showError(message) {
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = `
        <div class="text-center text-danger py-4">
            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
            <h5>Import Error</h5>
            <p class="mb-3">${message}</p>
            <div class="alert alert-danger">
                <small>
                    <strong>Troubleshooting tips:</strong><br>
                    • Check that your file follows the required format<br>
                    • Ensure all required columns are present<br>
                    • Verify that the file is not corrupted<br>
                    • Try downloading and using the template
                </small>
            </div>
            <button class="btn btn-outline-primary" onclick="location.reload()">
                <i class="fas fa-refresh me-2"></i>Try Again
            </button>
        </div>
    `;
}

// Enhanced alert function
function showAlert(message, type) {
    console.log(`Alert [${type}]:`, message);

    // Remove any existing alerts first
    const existingAlerts = document.querySelectorAll('.alert.position-fixed');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px;';

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
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+C on job ID to copy
    if (e.ctrlKey && e.key === 'c') {
        const activeElement = document.activeElement;
        if (activeElement && activeElement.classList.contains('job-id-display')) {
            e.preventDefault();
            copyToClipboard(activeElement.textContent.trim());
        }
    }

    // Escape to close modals
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }

    // Enter to trigger preview (when form is focused)
    if (e.key === 'Enter' && e.ctrlKey) {
        e.preventDefault();
        previewImport();
    }
});

// File input drag and drop enhancement
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="excel_file"]');
    const formCard = fileInput?.closest('.card-body');

    if (fileInput && formCard) {
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            formCard.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            formCard.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            formCard.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            formCard.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            formCard.style.border = '2px dashed #0d6efd';
        }

        function unhighlight() {
            formCard.style.backgroundColor = '';
            formCard.style.border = '';
        }

        formCard.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                validateFile(fileInput);
                showAlert('File dropped successfully!', 'success');
            }
        }
    }
});

// Auto-fill title based on project details
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.querySelector('input[name="title"]');
    const descriptionTextarea = document.querySelector('textarea[name="description"]');

    if (titleInput && !titleInput.value.trim()) {
        const projectName = '{{ $project->engagement_name ?? "PBC Request" }}';
        const currentYear = new Date().getFullYear();
        titleInput.value = `${projectName} - ${currentYear}`;
    }

    if (descriptionTextarea && !descriptionTextarea.value.trim()) {
        const projectName = '{{ $project->engagement_name ?? "project" }}';
        const jobId = '{{ $project->job_id ?? "TBD" }}';
        descriptionTextarea.value = `PBC Request for ${projectName} (Job ID: ${jobId})`;
    }
});
</script>
@endsection
