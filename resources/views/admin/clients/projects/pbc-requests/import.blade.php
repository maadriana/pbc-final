@extends('layouts.app')
@section('title', 'Import PBC Requests - ' . $project->engagement_name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Import PBC Requests</h1>
            <p class="text-muted mb-0">
                {{ $client->company_name }} | {{ $project->engagement_name }} | Job ID: {{ $project->job_id }}
            </p>
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
                            <label class="form-label">Request Title *</label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="e.g., Year-end PBC Request 2024" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Brief description of this PBC request"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control"
                                   min="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Excel File *</label>
                            <input type="file" name="excel_file" class="form-control"
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">
                                Supported formats: .xlsx, .xls, .csv (Max: 10MB)
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" onclick="previewImport()">
                                <i class="fas fa-eye"></i> Preview Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Import Instructions
                    </h5>
                </div>
                <div class="card-body">
                    <h6>File Format Requirements:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Column A: Category (CF or PF)</li>
                        <li><i class="fas fa-check text-success me-2"></i>Column B: Request Description</li>
                        <li><i class="fas fa-check text-success me-2"></i>Column C: Assigned To (optional)</li>
                        <li><i class="fas fa-check text-success me-2"></i>Column D: Due Date (optional)</li>
                        <li><i class="fas fa-check text-success me-2"></i>Column E: Required (TRUE/FALSE)</li>
                    </ul>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Categories:</strong>
                        <br>• <strong>CF</strong> = Confirmed by Firm
                        <br>• <strong>PF</strong> = Provided by Firm
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
                            <i class="fas fa-file-import fa-3x mb-3"></i>
                            <h5>No Data to Preview</h5>
                            <p>Upload a file and click "Preview Import" to see the data that will be imported.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Confirmation Modal -->
<div class="modal fade" id="confirmImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Import</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You are about to import <span id="importCount">0</span> PBC request items.
                </div>
                <p>Are you sure you want to proceed with this import?</p>
                <div id="importSummary"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeImport()">
                    <i class="fas fa-upload"></i> Import Now
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.card {
    border-radius: 0.5rem;
}

.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.preview-table {
    font-size: 0.9rem;
}

.preview-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.preview-table td {
    vertical-align: middle;
}

.badge.bg-primary { background-color: #0d6efd !important; }
.badge.bg-secondary { background-color: #6c757d !important; }

.alert {
    border-radius: 0.5rem;
}

.btn {
    border-radius: 0.375rem;
}

.form-control, .form-select {
    border-radius: 0.375rem;
}

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

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-in;
}
</style>
@endsection

@section('scripts')
<script>
let previewData = null;

function previewImport() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);

    // Validate required fields
    if (!formData.get('title') || !formData.get('excel_file')) {
        showAlert('Please fill in the title and select a file to upload.', 'warning');
        return;
    }

    const previewContent = document.getElementById('previewContent');

    // Show loading
    previewContent.innerHTML = `
        <div class="loading-overlay">
            <div class="text-center">
                <div class="spinner-border spinner-border-lg text-primary" role="status"></div>
                <div class="mt-2">Processing file...</div>
            </div>
        </div>
    `;

    fetch(`{{ route('admin.clients.projects.pbc-requests.import.preview', [$client, $project]) }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            previewData = data.data;
            displayPreview(data.data);
            showAlert(`Preview loaded successfully! Found ${data.count} items.`, 'success');
        } else {
            showError(data.message || 'Failed to process file');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error processing file. Please try again.');
    });
}

function displayPreview(data) {
    const previewContent = document.getElementById('previewContent');

    if (!data || data.length === 0) {
        previewContent.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h5>No Valid Data Found</h5>
                <p>Please check your file format and try again.</p>
            </div>
        `;
        return;
    }

    let tableHtml = `
        <div class="fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Preview: ${data.length} items found</h6>
                <button class="btn btn-success btn-sm" onclick="showImportModal()">
                    <i class="fas fa-check"></i> Confirm Import
                </button>
            </div>
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

    data.forEach((item, index) => {
        if (index < 10) { // Show only first 10 items
            tableHtml += `
                <tr>
                    <td>
                        <span class="badge ${item.category === 'CF' ? 'bg-primary' : 'bg-secondary'}">
                            ${item.category}
                        </span>
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 200px;" title="${item.particulars}">
                            ${item.particulars}
                        </div>
                    </td>
                    <td>${item.assigned_to || '<em class="text-muted">Not specified</em>'}</td>
                    <td>${item.due_date || '<em class="text-muted">No due date</em>'}</td>
                    <td>
                        <span class="badge ${item.is_required ? 'bg-warning' : 'bg-light text-dark'}">
                            ${item.is_required ? 'Required' : 'Optional'}
                        </span>
                    </td>
                </tr>
            `;
        }
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

function showImportModal() {
    if (!previewData) {
        showAlert('No preview data available. Please preview first.', 'warning');
        return;
    }

    document.getElementById('importCount').textContent = previewData.length;

    const categoryCounts = previewData.reduce((counts, item) => {
        counts[item.category] = (counts[item.category] || 0) + 1;
        return counts;
    }, {});

    const summaryHtml = `
        <div class="row text-center">
            <div class="col-6">
                <div class="border rounded p-2">
                    <strong class="text-primary">${categoryCounts.CF || 0}</strong>
                    <br><small>Confirmed by Firm</small>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <strong class="text-secondary">${categoryCounts.PF || 0}</strong>
                    <br><small>Provided by Firm</small>
                </div>
            </div>
        </div>
    `;

    document.getElementById('importSummary').innerHTML = summaryHtml;

    const modal = new bootstrap.Modal(document.getElementById('confirmImportModal'));
    modal.show();
}

function executeImport() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmImportModal'));
    const executeBtn = document.querySelector('#confirmImportModal .btn-primary');

    // Show loading state
    executeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing...';
    executeBtn.disabled = true;

    fetch(`{{ route('admin.clients.projects.pbc-requests.import.execute', [$client, $project]) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            showAlert('Import completed successfully!', 'success');

            // Redirect after delay
            setTimeout(() => {
                window.location.href = `{{ route('admin.clients.projects.pbc-requests.index', [$client, $project]) }}`;
            }, 2000);
        } else {
            showAlert('Import failed: ' + (data.message || 'Unknown error'), 'danger');
            executeBtn.innerHTML = '<i class="fas fa-upload"></i> Import Now';
            executeBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error during import. Please try again.', 'danger');
        executeBtn.innerHTML = '<i class="fas fa-upload"></i> Import Now';
        executeBtn.disabled = false;
    });
}

function showError(message) {
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = `
        <div class="text-center text-danger py-4">
            <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
            <h5>Import Error</h5>
            <p>${message}</p>
            <button class="btn btn-outline-primary" onclick="location.reload()">
                <i class="fas fa-refresh"></i> Try Again
            </button>
        </div>
    `;
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 100px; right: 20px; z-index: 9999; min-width: 350px;';
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// File input change handler
document.querySelector('input[name="excel_file"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        if (fileSize > 10) {
            showAlert('File size exceeds 10MB limit. Please choose a smaller file.', 'warning');
            this.value = '';
        }
    }
});
</script>
@endsection
