@extends('layouts.app')
@section('title', 'Client Dashboard')

@section('content')
<h1>Client Dashboard</h1>
<p>Welcome, {{ auth()->user()->client->company_name }}!</p>

<!-- Simple Metrics -->
<div class="row mb-4">
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['total_requests'] }}</h5><small>Total Requests</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['pending_requests'] }}</h5><small>Pending</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['in_progress_requests'] }}</h5><small>In Progress</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['completed_requests'] }}</h5><small>Completed</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['overdue_requests'] }}</h5><small>Overdue</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['total_documents'] }}</h5><small>Documents</small></div></div></div>
</div>

<!-- Recent Requests -->
<h3>Recent PBC Requests</h3>
<table class="table">
    <thead><tr><th>ID</th><th>Title</th><th>Project</th><th>Status</th><th>Due Date</th><th>Action</th></tr></thead>
    <tbody>
        @forelse($recent_requests as $request)
        <tr>
            <td>{{ $request->id }}</td>
            <td>{{ $request->title }}</td>
            <td>{{ $request->project->name }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($request->status) }}</span></td>
            <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
            <td><a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">View</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center">No requests found</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
