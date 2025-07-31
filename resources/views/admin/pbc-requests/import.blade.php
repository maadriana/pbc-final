@extends('layouts.app')
@section('title', 'Import PBC Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Import Request</h1>
        <p class="text-muted mb-0">Upload Excel file to bulk create PBC requests</p>
    </div>
    <div>
        <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Requests
        </a>
    </div>
</div>

<!-- Instructions Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle"></i> Import Instructions
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="step-guide">
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">1</div>
                        <div>
                            <strong>Download the template</strong> using the button on the right
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">2</div>
                        <div>
                            <strong>Fill in your data</strong> following the template format
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">3</div>
                        <div>
                            <strong>Select project and client</strong> for the import
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold;">4</div>
                        <div>
                            <strong>Upload your file</strong> and preview before final import
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h6 class="text-primary">Expected Excel Columns:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Category</span>
                                    <small class="text-muted">CF (Current File) or PF (Permanent File)</small>
                                </li>
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Request Description</span>
                                    <small class="text-muted">Document particulars</small>
                                </li>
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Requestor</span>
                                    <small class="text-muted">MTC staff name (MNGR 1, EYM, etc.)</small>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Date Requested</span>
                                    <small class="text-muted">Request date (DD/MM/YYYY)</small>
                                </li>
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Assigned to</span>
                                    <small class="text-muted">Client contact person</small>
                                </li>
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Status</span>
                                    <small class="text-muted">Current status</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="border rounded p-4 bg-light">
                    <i class="fas fa-download fa-3x text-success mb-3"></i>
                    <h5>Download Template</h5>
                    <p class="text-muted small">Excel template with sample data and user examples</p>
                    <a href="{{ route('admin.pbc-requests.import.template') }}"
                       class="btn btn-success btn-lg">
                        <i class="fas fa-file-excel"></i> Download Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Form -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="card-title mb-0">
            <i class="fas fa-upload text-primary"></i> Upload Import File
        </h5>
    </div>
    <div class="card-body">
        <div id="alert-container"></div>

        <form id="importForm" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <!-- Project Selection -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="project_id" class="form-label fw-bold">
                            Project/Job <span class="text-danger">*</span>
                        </label>
                        <select name="project_id"
                                id="project_id"
                                class="form-select"
                                required>
                            <option value="">Select Project...</option>
                            @foreach($projects ?? [] as $project)
                                <option value="{{ $project->id }}"
                                        data-client-id="{{ $project->client_id }}"
                                        data-job-id="{{ $project->job_id ?? 'N/A' }}">
                                    {{ $project->job_id ?? 'No Job ID' }} - {{ $project->engagement_name ?? $project->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Select the project/job for which you want to import requests</div>
                    </div>
                </div>

                <!-- Client Selection -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="client_id" class="form-label fw-bold">
                            Client <span class="text-danger">*</span>
                        </label>
                        <select name="client_id"
                                id="client_id"
                                class="form-select"
                                required>
                            <option value="">Select Client...</option>
                            @foreach($clients ?? [] as $client)
                                <option value="{{ $client->id }}">
                                    {{ $client->company_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">This will be auto-selected based on your project choice</div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <!-- File Upload -->
                <div class="col-12">
                    <div class="form-group">
                        <label for="excel_file" class="form-label fw-bold">
                            Excel File <span class="text-danger">*</span>
                        </label>
                        <div class="upload-area border-2 border-dashed rounded p-4 text-center" id="uploadArea">
                            <input type="file"
                                   name="excel_file"
                                   id="excel_file"
                                   class="form-control d-none"
                                   accept=".xlsx,.xls,.csv"
                                   required>
                            <div id="uploadContent">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Drop your Excel file here or click to browse</h5>
                                <p class="text-muted">Supported formats: .xlsx, .xls, .csv (Max: 10MB)</p>
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('excel_file').click()">
                                    <i class="fas fa-folder-open"></i> Browse Files
                                </button>
                            </div>
                            <div id="fileInfo" class="d-none">
                                <i class="fas fa-file-excel fa-2x text-success mb-2"></i>
                                <div id="fileName" class="fw-bold"></div>
                                <div id="fileSize" class="text-muted small"></div>
                                <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="clearFile()">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Options -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label fw-bold">Import Options</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-check-lg p-3 border rounded">
                                    <input type="radio"
                                           name="import_mode"
                                           value="preview"
                                           id="mode_preview"
                                           class="form-check-input"
                                           checked>
                                    <label for="mode_preview" class="form-check-label">
                                        <strong class="text-primary">Preview First</strong>
                                        <div class="small text-muted">Review data before importing (Recommended)</div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-check-lg p-3 border rounded">
                                    <input type="radio"
                                           name="import_mode"
                                           value="direct"
                                           id="mode_direct"
                                           class="form-check-input">
                                    <label for="mode_direct" class="form-check-label">
                                        <strong class="text-warning">Direct Import</strong>
                                        <div class="small text-muted">Import immediately without preview</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Make sure your Excel file follows the template format
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-eye"></i> Preview Import
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Recent Import Activity (Admin only) -->
@if(auth()->user()->isSystemAdmin())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom">
        <h6 class="card-title mb-0">
            <i class="fas fa-history text-secondary"></i> Recent Import Activity
        </h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <div class="p-3">
                    <h4 class="text-primary mb-1">{{ $importStats['total_imported_today'] ?? 0 }}</h4>
                    <small class="text-muted">Imported Today</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border-start border-end">
                    <h4 class="text-warning mb-1">{{ $importStats['pending_imports'] ?? 0 }}</h4>
                    <small class="text-muted">Pending Preview</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3">
                    <h4 class="text-success mb-1">Active</h4>
                    <small class="text-muted">Import System</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('styles')
<style>
/* Upload area styling */
.upload-area {
    transition: all 0.3s ease;
    cursor: pointer;
    background-color: #f8f9fa;
}

.upload-area:hover {
    background-color: #e9ecef;
    border-color: #007bff !important;
}

.upload-area.dragover {
    background-color: #e3f2fd;
    border-color: #2196f3 !important;
}

/* Step guide styling */
.step-number {
    font-size: 0.875rem;
    flex-shrink: 0;
}

/* Form check improvements */
.form-check-lg .form-check-input {
    width: 1.25rem;
    height: 1.25rem;
}

.form-check-lg .form-check-label {
    padding-left: 0.5rem;
}

.form-check:hover {
    background-color: #f8f9fa;
}

/* File info styling */
#fileInfo {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Button enhancements */
.btn {
    font-weight: 500;
    transition: all 0.2s;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
}

/* Alert container */
#alert-container {
    margin-bottom: 1rem;
}

/* Form validation styling */
.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectSelect = document.getElementById('project_id');
    const clientSelect = document.getElementById('client_id');
    const fileInput = document.getElementById('excel_file');
    const uploadArea = document.getElementById('uploadArea');
    const uploadContent = document.getElementById('uploadContent');
    const fileInfo = document.getElementById('fileInfo');
    const importForm = document.getElementById('importForm');
    const submitBtn = document.getElementById('submitBtn');
    const importModeInputs = document.querySelectorAll('input[name="import_mode"]');

    // Auto-select client when project is selected
    projectSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const clientId = selectedOption.getAttribute('data-client-id');

        if (clientId) {
            clientSelect.value = clientId;
        }
    });

    // Update submit button text based on import mode
    importModeInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'preview') {
                submitBtn.innerHTML = '<i class="fas fa-eye"></i> Preview Import';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Import Directly';
            }
        });
    });

    // File upload handling
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFileInfo(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            showFileInfo(this.files[0]);
        }
    });

    function showFileInfo(file) {
        // Validate file
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                          'application/vnd.ms-excel',
                          'text/csv'];

        if (!validTypes.includes(file.type)) {
            showAlert('Please select a valid Excel (.xlsx, .xls) or CSV file.', 'danger');
            return;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB
            showAlert('File size cannot exceed 10MB.', 'danger');
            return;
        }

        // Show file info
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);

        uploadContent.classList.add('d-none');
        fileInfo.classList.remove('d-none');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form submission
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        const formData = new FormData(this);
        const importMode = document.querySelector('input[name="import_mode"]:checked').value;

        const url = importMode === 'preview'
            ? '{{ route("admin.pbc-requests.import.preview") }}'
            : '{{ route("admin.pbc-requests.import.bulk") }}';

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (importMode === 'preview') {
                    showPreviewModal(data.data);
                } else {
                    showAlert('Import completed successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.pbc-requests.index") }}';
                    }, 2000);
                }
            } else {
                showAlert(data.message || 'Import failed', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred during import', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = importMode === 'preview'
                ? '<i class="fas fa-eye"></i> Preview Import'
                : '<i class="fas fa-upload"></i> Import Directly';
        });
    });

    function validateForm() {
        const project = projectSelect.value;
        const client = clientSelect.value;
        const file = fileInput.files[0];

        if (!project) {
            showAlert('Please select a project', 'warning');
            return false;
        }

        if (!client) {
            showAlert('Please select a client', 'warning');
            return false;
        }

        if (!file) {
            showAlert('Please select a file to upload', 'warning');
            return false;
        }

        return true;
    }

    function showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        alertContainer.innerHTML = '';
        alertContainer.appendChild(alertDiv);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    function showPreviewModal(data) {
        // This would show a preview modal - for now just redirect
        showAlert(`Preview: ${data.stats.total_requests} requests with ${data.stats.total_items} items ready to import`, 'info');

        // You can implement a modal here or redirect to preview page
        setTimeout(() => {
            window.location.href = '{{ route("admin.pbc-requests.index") }}';
        }, 3000);
    }

    window.clearFile = function() {
        fileInput.value = '';
        uploadContent.classList.remove('d-none');
        fileInfo.classList.add('d-none');
    };

    window.resetForm = function() {
        importForm.reset();
        clearFile();
        document.getElementById('alert-container').innerHTML = '';
        submitBtn.innerHTML = '<i class="fas fa-eye"></i> Preview Import';
    };
});
</script>
@endsection
