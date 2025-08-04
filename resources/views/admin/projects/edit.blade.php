@extends('layouts.app')
@section('title', 'Edit Project')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Edit Project: {{ $project->engagement_name ?? $project->name }}</h1>
        @if($project->job_id)
            @php $jobIdBreakdown = $project->getJobIdBreakdownAttribute(); @endphp
            <p class="text-muted mb-0">
                Job ID: <span class="job-id-display fw-bold text-primary" title="Click to copy">{{ $project->job_id }}</span>
                @if($jobIdBreakdown)
                    <small class="text-muted ms-2">
                        ({{ $jobIdBreakdown['client_initial'] }}: {{ $project->client->company_name ?? 'Client' }} |
                        {{ $jobIdBreakdown['job_type_code'] }}: {{ ucfirst($project->engagement_type) }} |
                        FY{{ $jobIdBreakdown['year_of_job'] }})
                    </small>
                @endif
            </p>
        @endif
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="saveProject()">
            <i class="fas fa-save"></i> Update Project
        </button>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Projects
        </a>
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

<!-- Enhanced Project Info Card -->
@if($project->job_id && $project->getJobIdBreakdownAttribute())
    @php $breakdown = $project->getJobIdBreakdownAttribute(); @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>Job ID Breakdown
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
                                <span class="ms-2">{{ $project->client->company_name ?? 'N/A' }}</span>
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
                            <h6 class="text-muted mb-2">Job ID Structure</h6>
                            <div class="job-id-parts">
                                <span class="job-part client-part" title="Client: {{ $project->client->company_name ?? 'N/A' }}">{{ $breakdown['client_initial'] }}</span>-<span class="job-part year-part" title="Year Engaged: {{ $breakdown['year_engaged'] }}">{{ substr($breakdown['year_engaged'], -2) }}</span>-<span class="job-part series-part" title="Series: {{ $breakdown['series'] }}">{{ $breakdown['series'] }}</span>-<span class="job-part type-part" title="Type: {{ $breakdown['job_type'] }}">{{ $breakdown['job_type_code'] }}</span>-<span class="job-part job-year-part" title="Job Year: {{ $breakdown['year_of_job'] }}">{{ substr($breakdown['year_of_job'], -2) }}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="copyJobId()">
                                <i class="fas fa-copy me-1"></i>Copy Job ID
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<form id="project-form" method="POST" action="{{ route('admin.projects.update', $project) }}">
    @csrf
    @method('PUT')

    <!-- Hidden field to specify redirect location -->
    <input type="hidden" name="redirect_to" value="index">

    <div class="row">
        <!-- Left Column - Project Details -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Project Information</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Job ID <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="text"
                                   name="job_id"
                                   id="job_id_field"
                                   class="form-control job-id-input @error('job_id') is-invalid @enderror"
                                   value="{{ old('job_id', $project->job_id) }}"
                                   placeholder="e.g., ABC-22-001-A-24"
                                   onchange="validateJobId()">
                            <button class="btn btn-outline-secondary" type="button" onclick="regenerateJobId()" title="Regenerate Job ID">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            <small class="text-info">
                                Format: CLIENT-YEAR_ENGAGED-SERIES-TYPE-JOB_YEAR
                                <button type="button" class="btn btn-link btn-sm p-0" onclick="toggleJobIdHelp()">
                                    <i class="fas fa-question-circle"></i>
                                </button>
                            </small>
                            <div id="job-id-help" style="display: none;" class="mt-2 p-2 bg-light rounded">
                                <small class="d-block"><strong>Job ID Components:</strong></small>
                                <small class="d-block">• <strong>CLIENT:</strong> 3-letter client initial</small>
                                <small class="d-block">• <strong>YEAR_ENGAGED:</strong> Year client first engaged (2-digit)</small>
                                <small class="d-block">• <strong>SERIES:</strong> Sequential number (3-digit)</small>
                                <small class="d-block">• <strong>TYPE:</strong> A=Audit, AC=Accounting, T=Tax, S=Special, O=Others</small>
                                <small class="d-block">• <strong>JOB_YEAR:</strong> Year of engagement (2-digit)</small>
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
                               value="{{ old('engagement_name', $project->engagement_name ?? $project->name) }}"
                               required>
                        @error('engagement_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="3">{{ old('description', $project->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Client <span class="text-danger">*</span>
                        </label>
                        <select name="client_id"
                                class="form-select @error('client_id') is-invalid @enderror"
                                required
                                onchange="updateJobIdPreview()">
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->company_name }}
                                    @if($client->getClientInitial())
                                        ({{ $client->getClientInitial() }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Engagement Type <span class="text-danger">*</span>
                        </label>
                        <select name="engagement_type"
                                class="form-select @error('engagement_type') is-invalid @enderror"
                                required
                                onchange="updateJobIdPreview()">
                            <option value="">Select Engagement Type</option>
                            <option value="audit" {{ old('engagement_type', $project->engagement_type) == 'audit' ? 'selected' : '' }}>Audit (A)</option>
                            <option value="accounting" {{ old('engagement_type', $project->engagement_type) == 'accounting' ? 'selected' : '' }}>Accounting (AC)</option>
                            <option value="tax" {{ old('engagement_type', $project->engagement_type) == 'tax' ? 'selected' : '' }}>Tax (T)</option>
                            <option value="special_engagement" {{ old('engagement_type', $project->engagement_type) == 'special_engagement' ? 'selected' : '' }}>Special Engagement (S)</option>
                            <option value="others" {{ old('engagement_type', $project->engagement_type) == 'others' ? 'selected' : '' }}>Others (O)</option>
                        </select>
                        @error('engagement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Project Start Date</label>
                                <input type="date"
                                       name="start_date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', $project->start_date ? $project->start_date->format('Y-m-d') : '') }}">
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Project End Date</label>
                                <input type="date"
                                       name="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date', $project->end_date ? $project->end_date->format('Y-m-d') : '') }}">
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Engagement Period Start</label>
                                <input type="date"
                                       name="engagement_period_start"
                                       class="form-control @error('engagement_period_start') is-invalid @enderror"
                                       value="{{ old('engagement_period_start', $project->engagement_period_start ? $project->engagement_period_start->format('Y-m-d') : '') }}"
                                       onchange="updateJobIdPreview()">
                                @error('engagement_period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Engagement Period End</label>
                                <input type="date"
                                       name="engagement_period_end"
                                       class="form-control @error('engagement_period_end') is-invalid @enderror"
                                       value="{{ old('engagement_period_end', $project->engagement_period_end ? $project->engagement_period_end->format('Y-m-d') : '') }}">
                                @error('engagement_period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $project->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="on_hold" {{ old('status', $project->status) == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $project->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Team Assignment -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Team Assignment</h4>
                </div>
                <div class="card-body">
                    @if(isset($staffByRole) && $staffByRole)
                        @php
                            $currentAssignments = $project->assignments->keyBy('role');
                        @endphp

                        <div class="mb-3">
                            <label class="form-label fw-bold">Engagement Partner</label>
                            <select name="engagement_partner" class="form-select">
                                <option value="">Select Engagement Partner</option>
                                @foreach($staffByRole['engagement_partner'] ?? [] as $user)
                                    <option value="{{ $user->id }}" {{
                                        old('engagement_partner', $currentAssignments->get('engagement_partner')?->user_id ?? $project->engagement_partner_id) == $user->id ? 'selected' : ''
                                    }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Manager</label>
                            <select name="manager" class="form-select">
                                <option value="">Select Manager</option>
                                @foreach($staffByRole['manager'] ?? [] as $user)
                                    <option value="{{ $user->id }}" {{
                                        old('manager', $currentAssignments->get('manager')?->user_id ?? $project->manager_id) == $user->id ? 'selected' : ''
                                    }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Associate 1</label>
                            <select name="associate_1" class="form-select">
                                <option value="">Select Associate 1</option>
                                @foreach($staffByRole['associate'] ?? [] as $user)
                                    <option value="{{ $user->id }}" {{
                                        old('associate_1', $currentAssignments->get('associate_1')?->user_id) == $user->id ? 'selected' : ''
                                    }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Associate 2</label>
                            <select name="associate_2" class="form-select">
                                <option value="">Select Associate 2</option>
                                @foreach($staffByRole['associate'] ?? [] as $user)
                                    <option value="{{ $user->id }}" {{
                                        old('associate_2', $currentAssignments->get('associate_2')?->user_id) == $user->id ? 'selected' : ''
                                    }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <p>No staff members available for assignment.</p>
                        </div>
                    @endif

                    <div class="alert alert-warning">
                        <small><strong>Note:</strong> Changing team assignments will affect access to this project's PBC requests.</small>
                    </div>

                    @if($project->assignments->count() > 0)
                        <div class="card mt-3 bg-light">
                            <div class="card-header bg-transparent">
                                <small class="fw-bold">Current Team</small>
                            </div>
                            <div class="card-body">
                                @foreach($project->assignments as $assignment)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>
                                            <strong>{{ $assignment->role_display_name }}:</strong>
                                            {{ $assignment->user->name }}
                                        </span>
                                        <small class="text-muted">{{ $assignment->created_at->format('M d, Y') }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Project
                            </button>
                            <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            @if($project->client)
                                <a href="{{ route('admin.clients.projects.pbc-requests.index', [$project->client, $project]) }}" class="btn btn-info">
                                    <i class="fas fa-file-alt me-2"></i>View PBC Requests
                                </a>
                            @endif
                            <button type="button" class="btn btn-warning" onclick="resetForm()">
                                <i class="fas fa-undo me-2"></i>Reset Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    background-color: #fff !important;
}

.card-header.bg-info {
    background-color: #0dcaf0 !important;
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

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
}

.alert-info {
    background-color: #e3f2fd;
    color: #1976d2;
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

/* Project info icons */
.fas.text-primary { color: #0d6efd !important; }
.fas.text-success { color: #198754 !important; }
.fas.text-info { color: #0dcaf0 !important; }
.fas.text-warning { color: #ffc107 !important; }
.fas.text-danger { color: #dc3545 !important; }

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

/* Job ID regeneration animation */
.job-id-regenerating {
    position: relative;
}

.job-id-regenerating::after {
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
    .card-body {
        padding: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
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

    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
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

/* Help text styling */
#job-id-help {
    background-color: #f8f9fa !important;
    border: 1px solid #dee2e6;
    border-left: 4px solid #0d6efd;
}

#job-id-help small {
    line-height: 1.4;
}

/* Team assignment styling */
.bg-light .card-body {
    background-color: #f8f9fa !important;
}

/* Action buttons enhancement */
.card-body .d-flex {
    flex-wrap: wrap;
}

/* Focus improvements */
.form-control:focus, .form-select:focus {
    border-width: 2px;
}

/* Job ID validation styling */
.job-id-valid {
    border-color: #198754 !important;
    background-color: rgba(25, 135, 84, 0.05);
}

.job-id-invalid {
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.05);
}
/* Enhanced tooltips */
[title] {
    cursor: help;
}

/* Button group styling */
.d-flex.gap-2 .btn {
    white-space: nowrap;
}

/* Smooth transitions */
* {
    transition: all 0.2s ease;
}

/* Focus visible improvements */
.btn:focus-visible,
.form-control:focus-visible,
.form-select:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}
</style>
@endsection

@section('scripts')
<script>
// Global variables for client data
let clientsData = @json($clients ?? []);
let originalJobId = '{{ $project->job_id }}';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form listeners
    initializeFormListeners();

    // Initialize job ID copy functionality
    initializeJobIdCopy();

    // Validate current job ID
    validateJobId();

    console.log('Edit form loaded successfully');
});

// Initialize all form event listeners
function initializeFormListeners() {
    // Job ID validation on input
    const jobIdField = document.getElementById('job_id_field');
    if (jobIdField) {
        jobIdField.addEventListener('input', validateJobId);
        jobIdField.addEventListener('blur', validateJobId);
    }

    // Client selection change
    const clientSelect = document.querySelector('select[name="client_id"]');
    if (clientSelect) {
        clientSelect.addEventListener('change', updateJobIdPreview);
    }

    // Engagement type change
    const engagementTypeSelect = document.querySelector('select[name="engagement_type"]');
    if (engagementTypeSelect) {
        engagementTypeSelect.addEventListener('change', updateJobIdPreview);
    }

    // Engagement period change
    const engagementPeriodStart = document.querySelector('input[name="engagement_period_start"]');
    if (engagementPeriodStart) {
        engagementPeriodStart.addEventListener('change', updateJobIdPreview);
    }

    // Form validation on input
    document.querySelectorAll('input[required], select[required]').forEach(field => {
        field.addEventListener('blur', validateField);
        field.addEventListener('input', validateField);
    });
}

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
    const jobIdField = document.getElementById('job_id_field');
    if (jobIdField) {
        const jobId = jobIdField.value;
        copyToClipboard(jobId);
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

// Validate Job ID format
function validateJobId() {
    const jobIdField = document.getElementById('job_id_field');
    if (!jobIdField) return;

    const jobId = jobIdField.value.trim();

    if (!jobId) {
        jobIdField.classList.remove('job-id-valid', 'job-id-invalid');
        return;
    }

    // Job ID format: CLIENT-YY-SSS-T-YY
    const jobIdPattern = /^[A-Z]{3}-\d{2}-\d{3}-(A|AC|T|S|O)-\d{2}$/;

    if (jobIdPattern.test(jobId)) {
        jobIdField.classList.remove('job-id-invalid', 'is-invalid');
        jobIdField.classList.add('job-id-valid', 'is-valid');

        // Parse and validate components
        const parts = jobId.split('-');
        const clientCode = parts[0];
        const yearEngaged = parts[1];
        const series = parts[2];
        const typeCode = parts[3];
        const jobYear = parts[4];

        // Additional validation
        const currentYear = new Date().getFullYear();
        const fullYearEngaged = 2000 + parseInt(yearEngaged);
        const fullJobYear = 2000 + parseInt(jobYear);

        let validationMessage = '';

        if (fullYearEngaged > currentYear) {
            validationMessage += 'Year engaged cannot be in the future. ';
        }

        if (fullJobYear > currentYear + 5) {
            validationMessage += 'Job year seems too far in the future. ';
        }

        if (validationMessage) {
            showValidationTooltip(jobIdField, validationMessage, 'warning');
        } else {
            hideValidationTooltip(jobIdField);
        }

    } else {
        jobIdField.classList.remove('job-id-valid', 'is-valid');
        jobIdField.classList.add('job-id-invalid', 'is-invalid');

        showValidationTooltip(jobIdField, 'Invalid Job ID format. Expected: ABC-22-001-A-24', 'error');
    }
}

// Show validation tooltip
function showValidationTooltip(element, message, type) {
    // Remove existing tooltip
    hideValidationTooltip(element);

    const tooltip = document.createElement('div');
    tooltip.className = `validation-tooltip alert alert-${type === 'error' ? 'danger' : 'warning'} position-absolute`;
    tooltip.style.cssText = 'top: 100%; left: 0; z-index: 1000; font-size: 0.8rem; margin-top: 0.25rem; padding: 0.25rem 0.5rem;';
    tooltip.textContent = message;

    element.style.position = 'relative';
    element.parentNode.appendChild(tooltip);

    // Auto-hide after 5 seconds
    setTimeout(() => hideValidationTooltip(element), 5000);
}

// Hide validation tooltip
function hideValidationTooltip(element) {
    const existingTooltip = element.parentNode.querySelector('.validation-tooltip');
    if (existingTooltip) {
        existingTooltip.remove();
    }
}

// Regenerate Job ID
function regenerateJobId() {
    const jobIdField = document.getElementById('job_id_field');
    const clientSelect = document.querySelector('select[name="client_id"]');
    const engagementTypeSelect = document.querySelector('select[name="engagement_type"]');
    const engagementPeriodStart = document.querySelector('input[name="engagement_period_start"]');

    if (!clientSelect.value || !engagementTypeSelect.value) {
        showAlert('Please select client and engagement type first.', 'warning');
        return;
    }

    if (!confirm('This will generate a new Job ID. Are you sure you want to continue?')) {
        return;
    }

    // Show loading state
    jobIdField.classList.add('job-id-regenerating');
    jobIdField.disabled = true;

    // Get selected client
    const selectedClient = clientsData.find(client => client.id == clientSelect.value);
    if (!selectedClient) {
        showAlert('Selected client not found.', 'danger');
        jobIdField.classList.remove('job-id-regenerating');
        jobIdField.disabled = false;
        return;
    }

    // Generate new Job ID
    setTimeout(() => {
        const clientInitial = getClientInitial(selectedClient.company_name);
        const yearEngaged = getYearEngaged(selectedClient);
        const series = '001'; // Will be recalculated on backend
        const typeCode = getEngagementTypeCode(engagementTypeSelect.value);
        const jobYear = getJobYear(engagementPeriodStart.value);

        const newJobId = `${clientInitial}-${yearEngaged}-${series}-${typeCode}-${jobYear}`;

        jobIdField.value = newJobId;
        jobIdField.classList.remove('job-id-regenerating');
        jobIdField.classList.add('auto-filled');
        jobIdField.disabled = false;

        validateJobId();

        showAlert('Job ID regenerated successfully!', 'success');

        // Remove auto-filled class after animation
        setTimeout(() => {
            jobIdField.classList.remove('auto-filled');
        }, 2000);
    }, 1500);
}

// Update Job ID preview (for editing existing projects)
function updateJobIdPreview() {
    // This function can be used to suggest updates to the Job ID
    // when key fields change, but won't automatically change it
    console.log('Job ID preview update triggered');
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

// Get job year (2-digit)
function getJobYear(engagementPeriodStart) {
    let year;
    if (engagementPeriodStart) {
        year = new Date(engagementPeriodStart).getFullYear();
    } else {
        year = new Date().getFullYear();
    }
    return String(year).slice(-2).padStart(2, '0');
}

// Toggle Job ID help
function toggleJobIdHelp() {
    const helpDiv = document.getElementById('job-id-help');
    if (helpDiv) {
        if (helpDiv.style.display === 'none') {
            helpDiv.style.display = 'block';
            helpDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            helpDiv.style.display = 'none';
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
        saveBtn.innerHTML = '<span class="btn-text"><i class="fas fa-save"></i> Update Project</span>';
    }

    // Submit form
    setTimeout(() => {
        form.submit();
    }, 500);
}

// Reset form function
function resetForm() {
    if (confirm('Are you sure you want to reset all changes? This will revert to the last saved values.')) {
        location.reload();
    }
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

    // Special validation for Job ID
    const jobIdField = document.getElementById('job_id_field');
    if (jobIdField && !jobIdField.classList.contains('job-id-valid')) {
        if (jobIdField.value.trim()) {
            isValid = false;
            showAlert('Please enter a valid Job ID format.', 'danger');
        }
    }

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

    // Engagement period validation
    const engagementStartField = document.querySelector('input[name="engagement_period_start"]');
    const engagementEndField = document.querySelector('input[name="engagement_period_end"]');

    if (engagementStartField && engagementEndField && engagementStartField.value && engagementEndField.value) {
        if (new Date(engagementStartField.value) > new Date(engagementEndField.value)) {
            engagementEndField.classList.add('is-invalid');
            showAlert('Engagement period end must be after start date.', 'danger');
            isValid = false;
        }
    }

    if (!isValid) {
        showAlert('Please fix the validation errors before saving.', 'danger');

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

    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };

    alertDiv.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
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

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveProject();
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

    // F1 to toggle Job ID help
    if (e.key === 'F1') {
        e.preventDefault();
        toggleJobIdHelp();
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

// Handle form submission errors (if redirected back with errors)
window.addEventListener('load', function() {
    const hasErrors = document.querySelector('.alert-danger');
    if (hasErrors) {
        hasErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Auto-focus first invalid field on page load
setTimeout(() => {
    const firstInvalid = document.querySelector('.is-invalid');
    if (firstInvalid) {
        firstInvalid.focus();
    }
}, 100);

// Initialize tooltips if Bootstrap is available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>
@endsection
