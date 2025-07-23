@extends('layouts.app')
@section('title', 'My Progress')

@section('content')
<h1>My Progress</h1>

<!-- Progress Table -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Project</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Due Date</th>
            <th>Items Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($requests as $request)
        <tr>
            <td>{{ $request->id }}</td>
            <td>{{ $request->title }}</td>
            <td>{{ $request->project->name }}</td>
            <td>
                <span class="badge bg-{{
                    $request->status == 'completed' ? 'success' :
                    ($request->status == 'in_progress' ? 'warning' :
                    ($request->status == 'overdue' ? 'danger' : 'secondary'))
                }}">
                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                </span>
            </td>
            <td>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar bg-{{ $request->getProgressPercentage() == 100 ? 'success' : 'info' }}"
                         style="width: {{ $request->getProgressPercentage() }}%">
                        {{ $request->getProgressPercentage() }}%
                    </div>
                </div>
            </td>
            <td>
                {{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}
                @if($request->isOverdue())
                    <br><small class="text-danger">Overdue</small>
                @endif
            </td>
            <td>
                @php
                    $details = $request->getProgressDetails();
                @endphp
                <small>
                    <span class="badge bg-secondary">{{ $details['pending'] }} pending</span>
                    <span class="badge bg-warning">{{ $details['uploaded'] }} uploaded</span>
                    <span class="badge bg-success">{{ $details['approved'] }} approved</span>
                    @if($details['rejected'] > 0)
                        <span class="badge bg-danger">{{ $details['rejected'] }} rejected</span>
                    @endif
                </small>
            </td>
            <td>
                <a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View & Upload</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center">No requests found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $requests->links() }}
@endsection
