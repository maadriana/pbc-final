@extends('layouts.app')
@section('title', 'PBC Request Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $pbcRequest->title }}</h1>
    <span class="badge bg-{{ $pbcRequest->status == 'completed' ? 'success' : 'secondary' }} fs-6">{{ ucfirst($pbcRequest->status) }}</span>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <p><strong>Project:</strong> {{ $pbcRequest->project->name }}</p>
        <p><strong>Due Date:</strong> {{ $pbcRequest->due_date ? $pbcRequest->due_date->format('M d, Y') : 'N/A' }}</p>
    </div>
    <div class="col-md-6">
        <p><strong>Progress:</strong> {{ $pbcRequest->getProgressPercentage() }}%</p>
        <p><strong>Description:</strong> {{ $pbcRequest->description ?? 'N/A' }}</p>
    </div>
</div>

<!-- Request Items -->
<h3>Required Documents</h3>
<table class="table">
    <thead>
        <tr><th>Category</th><th>Document Required</th><th>Status</th><th>Upload</th></tr>
    </thead>
    <tbody>
        @foreach($pbcRequest->items as $item)
        <tr>
            <td>{{ $item->category ?? 'General' }}</td>
            <td>{{ $item->particulars }}</td>
            <td>
                <span class="badge bg-{{ $item->status == 'approved' ? 'success' : ($item->status == 'uploaded' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($item->status) }}
                </span>
            </td>
            <td>
                @if($item->status == 'pending' || $item->status == 'rejected')
                    <form method="POST" action="{{ route('client.pbc-requests.upload', [$pbcRequest, $item]) }}" enctype="multipart/form-data" class="d-flex gap-2">
                        @csrf
                        <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip,.txt" required>
                        <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                    </form>
                @elseif($item->documents->count() > 0)
                    @foreach($item->documents as $doc)
                        <div class="small">
                            <i class="fas fa-file"></i> {{ $doc->original_filename }}
                            <span class="badge bg-{{ $doc->status == 'approved' ? 'success' : 'warning' }}">{{ $doc->status }}</span>
                        </div>
                    @endforeach
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<a href="{{ route('client.pbc-requests.index') }}" class="btn btn-secondary">Back to Requests</a>
@endsection
