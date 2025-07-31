@extends('layouts.app')
@section('title', 'My Documents')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">My Documents</h1>
        <p class="text-muted mb-0">View and manage all your uploaded documents</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="text-end">
            <div class="small text-muted">Rule: Approved/accepted</div>
        </div>
        <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Requests
        </a>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="mb-0">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted fw-bold">Search Documents</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by document name or request..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted fw-bold">Client Name</label>
                    <input type="text" name="client_name" class="form-control"
                           placeholder="Filter by client name..."
                           value="{{ request('client_name', auth()->user()->client->company_name ?? '') }}"
                           readonly>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('client.documents.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Documents Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="py-3">Client Name</th>
                        <th class="py-3">Request Description</th>
                        <th class="py-3">Category</th>
                        <th class="py-3">File Name</th>
                        <th class="py-3">Document Type</th>
                        <th class="py-3">Size</th>
                        <th class="py-3">Date Uploaded</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                    @php
                        $pbcRequest = $document->pbcRequestItem->pbcRequest;
                        $project = $pbcRequest->project;
                        $client = $pbcRequest->client;
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="document-icon me-2">
                                    @if($document->file_extension === 'pdf')
                                        <i class="fas fa-file-pdf text-danger"></i>
                                    @elseif(in_array($document->file_extension, ['doc', 'docx']))
                                        <i class="fas fa-file-word text-primary"></i>
                                    @elseif(in_array($document->file_extension, ['xls', 'xlsx']))
                                        <i class="fas fa-file-excel text-success"></i>
                                    @elseif(in_array($document->file_extension, ['jpg', 'jpeg', 'png']))
                                        <i class="fas fa-file-image text-info"></i>
                                    @else
                                        <i class="fas fa-file text-secondary"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $project->job_id ?? sprintf('DOC-%05d', $document->id) }}</div>
                                    <small class="text-muted">{{ $project->engagement_type ?? 'Document' }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>
                                <div class="fw-semibold">{{ $client->company_name }}</div>
                                <small class="text-muted">{{ $client->contact_person ?? 'N/A' }}</small>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $document->pbcRequestItem->particulars }}">
                                    {{ Str::limit($document->pbcRequestItem->particulars, 40) }}
                                </div>
                                <small class="text-muted">{{ $pbcRequest->title }}</small>
                            </div>
                        </td>
                        <td class="py-3">
                            @php
                                $category = $document->pbcRequestItem->category;
                                $categoryClass = $category === 'CF' ? 'primary' : 'secondary';
                                $categoryLabel = $category === 'CF' ? 'Current File' : 'Permanent File';
                            @endphp
                            <span class="badge bg-{{ $categoryClass }}">
                                {{ $categoryLabel }}
                            </span>
                        </td>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div>
                                    <div class="fw-semibold text-truncate" style="max-width: 150px;" title="{{ $document->original_filename }}">
                                        {{ Str::limit($document->original_filename, 20) }}
                                    </div>
                                    <small class="text-muted">
                                        {{ strtoupper($document->file_extension) }} File
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            @php
                                $documentType = 'Excel'; // Default from wireframe
                                if ($document->file_extension === 'pdf') {
                                    $documentType = 'PDF';
                                } elseif (in_array($document->file_extension, ['doc', 'docx'])) {
                                    $documentType = 'Word';
                                } elseif (in_array($document->file_extension, ['jpg', 'jpeg', 'png'])) {
                                    $documentType = 'Image';
                                }
                            @endphp
                            <span class="badge bg-light text-dark border">{{ $documentType }}</span>
                        </td>
                        <td class="py-3">
                            <div>
                                <div class="fw-semibold">{{ $document->getFileSizeFormatted() }}</div>
                                <small class="text-muted">
                                    @if($document->status === 'approved')
                                        <i class="fas fa-check-circle text-success"></i> Approved
                                    @elseif($document->status === 'rejected')
                                        <i class="fas fa-times-circle text-danger"></i> Rejected
                                    @else
                                        <i class="fas fa-clock text-warning"></i> Pending
                                    @endif
                                </small>
                            </div>
                        </td>
                        <td class="py-3">
                            <div>
                                <div class="fw-semibold">{{ $document->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $document->created_at->format('H:i') }}</small>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="btn-group" role="group">
                                @if($document->status === 'approved')
                                    <!-- View/Download for approved documents -->
                                    <a href="{{ route('documents.download', $document) }}"
                                       class="btn btn-outline-success btn-sm"
                                       title="View/Download">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('documents.download', $document) }}"
                                       class="btn btn-outline-primary btn-sm"
                                       title="Download"
                                       download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                @elseif($document->status === 'rejected')
                                    <!-- Show rejection reason and allow re-upload -->
                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectionModal{{ $document->id }}"
                                            title="View Rejection Reason">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                    <a href="{{ route('client.pbc-requests.show', $pbcRequest) }}"
                                       class="btn btn-outline-warning btn-sm"
                                       title="Re-upload">
                                        <i class="fas fa-upload"></i>
                                    </a>
                                @else
                                    <!-- Pending documents -->
                                    <button type="button"
                                            class="btn btn-outline-warning btn-sm"
                                            disabled
                                            title="Pending Review">
                                        <i class="fas fa-clock"></i>
                                    </button>
                                    <a href="{{ route('client.pbc-requests.show', $pbcRequest) }}"
                                       class="btn btn-outline-primary btn-sm"
                                       title="View Request">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                @endif
                            </div>

                            <!-- Rejection Reason Modal -->
                            @if($document->status === 'rejected')
                            <div class="modal fade" id="rejectionModal{{ $document->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                                Document Rejected
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-danger">
                                                <strong>File:</strong> {{ $document->original_filename }}<br>
                                                <strong>Rejected on:</strong> {{ $document->updated_at->format('M d, Y H:i') }}<br>
                                                <strong>Rejected by:</strong> {{ $document->approver->name ?? 'Admin' }}
                                            </div>
                                            <h6>Reason for rejection:</h6>
                                            <div class="bg-light p-3 rounded">
                                                {{ $document->admin_notes ?? 'No specific reason provided.' }}
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <a href="{{ route('client.pbc-requests.show', $pbcRequest) }}" class="btn btn-primary">
                                                <i class="fas fa-upload"></i> Upload New File
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <h5>No Documents Found</h5>
                                <p>You haven't uploaded any documents yet.</p>
                                <div class="mt-3">
                                    <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> View PBC Requests
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
@if($documents->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $documents->links() }}
</div>
@endif

<!-- Document Statistics -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                <h4 class="text-primary">{{ $documents->total() }}</h4>
                <small class="text-muted">Total Documents</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h4 class="text-success">
                    {{ $documents->where('status', 'approved')->count() }}
                </h4>
                <small class="text-muted">Approved</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                <h4 class="text-warning">
                    {{ $documents->where('status', 'uploaded')->count() }}
                </h4>
                <small class="text-muted">Pending Review</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                <h4 class="text-danger">
                    {{ $documents->where('status', 'rejected')->count() }}
                </h4>
                <small class="text-muted">Rejected</small>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* Table styling improvements */
.table {
    font-size: 0.9rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #f8f9fa;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Document icon styling */
.document-icon .fas {
    font-size: 1.25rem;
    width: 20px;
    text-align: center;
}

/* Badge improvements */
.badge {
    font-size: 0.75em;
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Button group styling */
.btn-group .btn {
    border-radius: 0.25rem !important;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Card enhancements */
.card {
    border-radius: 0.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
}

/* Search form styling */
.form-label.small {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

/* Status indicators */
.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.text-warning {
    color: #ffc107 !important;
}

/* Empty state styling */
.fa-folder-open {
    color: #6c757d;
    opacity: 0.5;
}

/* Text truncation */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Modal improvements */
.modal-content {
    border-radius: 0.5rem;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
}

/* Statistics cards */
.card-body {
    padding: 1.5rem;
}

.card-body .fas {
    opacity: 0.8;
}

/* File type indicators */
.fa-file-pdf { color: #dc3545 !important; }
.fa-file-word { color: #007bff !important; }
.fa-file-excel { color: #28a745 !important; }
.fa-file-image { color: #17a2b8 !important; }
.fa-file { color: #6c757d !important; }

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }

    .btn-group .btn {
        padding: 0.25rem 0.4rem;
    }

    .card-body h4 {
        font-size: 1.5rem;
    }

    .text-truncate {
        max-width: 120px !important;
    }
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-1px);
}

.table tbody tr:hover .btn-group .btn {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Form control improvements */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Alert styling in modals */
.modal-body .alert {
    border-radius: 0.375rem;
}

/* Icon alignment */
.fas {
    width: 14px;
    text-align: center;
}

/* Status text colors */
.fw-semibold {
    font-weight: 600;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-refresh pending documents every 2 minutes
    setInterval(function() {
        const pendingDocuments = document.querySelectorAll('.btn-outline-warning[disabled]');
        if (pendingDocuments.length > 0) {
            // Check for status updates
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    console.log('Document status checked');
                    // You can implement partial page updates here
                }
            })
            .catch(error => console.error('Status check error:', error));
        }
    }, 120000); // 2 minutes

    // File download tracking
    document.querySelectorAll('a[download]').forEach(link => {
        link.addEventListener('click', function() {
            console.log('Document downloaded:', this.href);
            // You can add analytics tracking here
        });
    });

    // Enhanced search functionality
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // You can implement live search here
                console.log('Search query:', this.value);
            }, 500);
        });
    }

    // Modal enhancement for rejection details
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target');
            const modal = document.querySelector(modalId);
            if (modal) {
                // Add any modal-specific functionality here
                console.log('Modal opened:', modalId);
            }
        });
    });

    // Button state management
    document.querySelectorAll('.btn-group .btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.disabled) {
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            }
        });
    });

    // Enhanced error handling for file operations
    window.addEventListener('error', function(e) {
        if (e.target.tagName === 'A' && e.target.href.includes('download')) {
            console.error('Download error:', e);
            alert('Error downloading file. Please try again.');
        }
    });

    // Table row click enhancement
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            // Avoid triggering on button clicks
            if (!e.target.closest('.btn-group') && !e.target.closest('button')) {
                const firstActionBtn = this.querySelector('.btn-group .btn');
                if (firstActionBtn && !firstActionBtn.disabled) {
                    firstActionBtn.click();
                }
            }
        });
    });

    // Keyboard navigation for table
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.closest('.table tbody tr')) {
            const row = e.target.closest('tr');
            const firstBtn = row.querySelector('.btn-group .btn:not([disabled])');
            if (firstBtn) {
                firstBtn.click();
            }
        }
    });
});

// Helper function to format file sizes
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Helper function to get file icon class
function getFileIcon(extension) {
    const iconMap = {
        'pdf': 'fas fa-file-pdf text-danger',
        'doc': 'fas fa-file-word text-primary',
        'docx': 'fas fa-file-word text-primary',
        'xls': 'fas fa-file-excel text-success',
        'xlsx': 'fas fa-file-excel text-success',
        'jpg': 'fas fa-file-image text-info',
        'jpeg': 'fas fa-file-image text-info',
        'png': 'fas fa-file-image text-info',
        'zip': 'fas fa-file-archive text-warning'
    };

    return iconMap[extension.toLowerCase()] || 'fas fa-file text-secondary';
}
</script>
@endsection
