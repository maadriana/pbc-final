@extends('layouts.app')
@section('title', 'Client Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $client->company_name }}</h1>
    <div>
        <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-warning">Edit Client</a>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Back to Clients</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Company Information</h5></div>
            <div class="card-body">
                <p><strong>Company Name:</strong> {{ $client->company_name }}</p>
                <p><strong>Contact Person:</strong> {{ $client->contact_person ?? 'N/A' }}</p>
                <p><strong>Phone:</strong> {{ $client->phone ?? 'N/A' }}</p>
                <p><strong>Address:</strong> {{ $client->address ?? 'N/A' }}</p>
                <p><strong>Created:</strong> {{ $client->created_at->format('M d, Y') }}</p>
                <p><strong>Created By:</strong> {{ $client->creator->name }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>User Account</h5></div>
            <div class="card-body">
                <p><strong>User Name:</strong> {{ $client->user->name }}</p>
                <p><strong>Email:</strong> {{ $client->user->email }}</p>
                <p><strong>Role:</strong>
                    <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $client->user->role)) }}</span>
                </p>
                <p><strong>Account Created:</strong> {{ $client->user->created_at->format('M d, Y') }}</p>
                <div class="mt-3">
                    <a href="{{ route('admin.users.show', $client->user) }}" class="btn btn-sm btn-info">View User Account</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assigned Projects -->
<div class="mt-4">
    <h3>Assigned Projects</h3>
    @if($client->projects->count() > 0)
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Project Name</th><th>Status</th><th>Start Date</th><th>End Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($client->projects as $project)
                <tr>
                    <td>{{ $project->id }}</td>
                    <td>{{ $project->name }}</td>
                    <td><span class="badge bg-success">{{ ucfirst($project->status) }}</span></td>
                    <td>{{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $project->end_date ? $project->end_date->format('M d, Y') : 'N/A' }}</td>
                    <td><a href="{{ route('admin.projects.show', $project) }}" class="btn btn-sm btn-primary">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted">No projects assigned to this client.</p>
    @endif
</div>

<!-- PBC Requests -->
<div class="mt-4">
    <h3>PBC Requests</h3>
    @if($client->pbcRequests->count() > 0)
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Title</th><th>Project</th><th>Status</th><th>Progress</th><th>Due Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($client->pbcRequests as $request)
                <tr>
                    <td>{{ $request->id }}</td>
                    <td>{{ $request->title }}</td>
                    <td>{{ $request->project->name }}</td>
                    <td><span class="badge bg-secondary">{{ ucfirst($request->status) }}</span></td>
                    <td>{{ $request->getProgressPercentage() }}%</td>
                    <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
                    <td><a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted">No PBC requests for this client.</p>
        <a href="{{ route('admin.pbc-requests.create') }}" class="btn btn-primary">Create PBC Request</a>
    @endif
</div>
@endsection
