@extends('layouts.admin')

@section('title', 'Preview Import Data')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Preview Import Data</h1>
                    <p class="text-muted">Review the data before final import</p>
                </div>
                <div>
                    <a href="{{ route('admin.pbc-requests.import') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Import
                    </a>
                </div>
            </div>

            <!-- Import Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Import Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary">{{ $parsedData['stats']['total_requests'] }}</h3>
                                <small class="text-muted">PBC Requests</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center border-left">
                                <h3 class="text-success">{{ $parsedData['stats']['total_items'] }}</h3>
                                <small class="text-muted">Total Items</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center border-left">
                                <h3 class="text-info">{{ $parsedData['stats']['cf_items'] }}</h3>
                                <small class="text-muted">Current File (CF)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center border-left">
                                <h3 class="text-secondary">{{ $parsedData['stats']['pf_items'] }}</h3>
                                <small class="text-muted">Permanent File (PF)</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Project:</strong> {{ $project->job_id }} - {{ $project->engagement_name }}<br>
                            <strong>Client:</strong> {{ $client->company_name }}<br>
                            <strong>Engagement Type:</strong> {{ ucfirst($project->engagement_type) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Import Date:</strong> {{ now()->format('M d, Y H:i') }}<br>
                            <strong>Imported By:</strong> {{ auth()->user()->name }}<br>
                            <strong>Status:</strong> <span class="badge badge-warning">Pending Confirmation</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Data -->
            @foreach($parsedData['requests'] as $index => $requestData)
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-file-alt text-primary"></i>
                            Request {{ $index + 1 }}: {{ $requestData['title'] }}
                        </h6>
                        <span class="badge badge-info">{{ count($requestData['items']) }} items</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Request Details -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <strong>Description:</strong> {{ $requestData['description'] }}<br>
                            <strong>Due Date:</strong> {{ \Carbon\Carbon::parse($requestData['due_date'])->format('M d, Y') }}
                        </div>
                        <div class="col-md-4">
                            <strong>Header Info:</strong><br>
                            @if(isset($requestData['header_info']['engagement_partner']))
                                EP: {{ $requestData['header_info']['engagement_partner'] }}<br>
                            @endif
                            @if(isset($requestData['header_info']['manager']))
                                Manager: {{ $requestData['header_info']['manager'] }}
                            @endif
                        </div>
                    </div>

                    <!-- Request Items -->
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%">Category</th>
                                    <th style="width: 50%">Particulars</th>
                                    <th style="width: 15%">Date Requested</th>
                                    <th style="width: 10%">Required</th>
                                    <th style="width: 15%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requestData['items'] as $item)
                                <tr>
                                    <td>
                                        <span class="badge {{ $item['category'] === 'CF' ? 'badge-primary' : 'badge-secondary' }}">
                                            {{ $item['category'] }}
                                        </span>
                                    </td>
                                    <td>{{ $item['particulars'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item['date_requested'])->format('M d, Y') }}</td>
                                    <td>
                                        @if($item['is_required'])
                                            <i class="fas fa-check text-success"></i> Yes
                                        @else
                                            <i class="fas fa-times text-muted"></i> No
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $item['remarks'] ?? '-' }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Confirmation Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Ready to Import?</h6>
                            <small class="text-muted">
                                This will create {{ $parsedData['stats']['total_requests'] }} PBC requests
                                with {{ $parsedData['stats']['total_items'] }} items total.
                            </small>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('admin.pbc-requests.import') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <form action="{{ route('admin.pbc-requests.import.execute') }}"
                                  method="POST"
                                  style="display: inline;">
                                @csrf
                                <button type="submit"
                                        class="btn btn-success"
                                        onclick="return confirm('Are you sure you want to import {{ $parsedData['stats']['total_requests'] }} PBC requests?')">
                                    <i class="fas fa-check"></i> Confirm Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Warnings (if any) -->
            @if(isset($parsedData['warnings']) && count($parsedData['warnings']) > 0)
            <div class="card mt-3">
                <div class="card-header bg-warning text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Import Warnings
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach($parsedData['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.border-left {
    border-left: 1px solid #dee2e6 !important;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
}

.card-header h6 {
    font-weight: 600;
}

.btn-group .btn {
    margin-left: 0.5rem;
}
</style>
@endsection
