@extends('layouts.app')
@section('title', 'Progress Tracking')

@section('content')
<h1>Progress Tracking</h1>

<!-- Progress Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5>{{ $stats['total_requests'] }}</h5>
                <small>Total Requests</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5>{{ $stats['pending'] }}</h5>
                <small>Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5>{{ $stats['in_progress'] }}</h5>
                <small>In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5>{{ $stats['completed'] }}</h5>
                <small>Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h5>{{ $stats['overdue'] }}</h5>
                <small>Overdue</small>
            </div>
        </div>
    </div>
</div>

<!-- Requests Progress Table -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Client</th>
            <th>Project</th>
            <th>Status</th>
            <th>Progress</th>
            <th>Due Date</th>
            <th>Days Outstanding</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($requests as $request)
        <tr>
            <td>{{ $request->id }}</td>
            <td>{{ $request->title }}</td>
            <td>{{ $request->client->company_name }}</td>
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
            <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
            <td>{{ $request->getDaysOutstanding() }} days</td>
            <td>
                <a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-center">No requests found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $requests->links() }}
@endsection
