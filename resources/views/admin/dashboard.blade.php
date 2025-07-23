@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<h1>Admin Dashboard</h1>

<!-- Simple Metrics Cards -->
<div class="row mb-4">
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['total_users'] }}</h5><small>Users</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['total_clients'] }}</h5><small>Clients</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['total_projects'] }}</h5><small>Projects</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['active_requests'] }}</h5><small>Active Requests</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['pending_documents'] }}</h5><small>Pending Review</small></div></div></div>
    <div class="col-md-2"><div class="card text-center"><div class="card-body"><h5>{{ $metrics['completed_requests'] }}</h5><small>Completed</small></div></div></div>
</div>

<!-- Recent Requests -->
<h3>Recent PBC Requests</h3>
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Title</th><th>Client</th><th>Status</th><th>Due Date</th><th>Action</th></tr></thead>
    <tbody>
        @forelse($recent_requests as $request)
        <tr>
            <td>{{ $request->id }}</td>
            <td>{{ $request->title }}</td>
            <td>{{ $request->client->company_name }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($request->status) }}</span></td>
            <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
            <td><a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">View</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center">No requests found</td></tr>
        @endforelse
    </tbody>
</table>

<!-- Quick Actions -->
<div class="mt-4">
    <h3>Quick Actions</h3>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Create User</a>
    <a href="{{ route('admin.clients.create') }}" class="btn btn-success">Create Client</a>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-info">Create Project</a>
    <a href="{{ route('admin.pbc-templates.create') }}" class="btn btn-warning">Create Template</a>
</div>
@endsection
