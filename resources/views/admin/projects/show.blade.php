@extends('layouts.app')
@section('title', 'Project Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $project->name }}</h1>
    <div>
        <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-warning">Edit Project</a>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary">Back to Projects</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Project Details</h5></div>
            <div class="card-body">
                <p><strong>Description:</strong> {{ $project->description ?? 'N/A' }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : 'warning') }}">
                        {{ ucfirst($project->status) }}
                    </span>
                </p>
                <p><strong>Start Date:</strong> {{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</p>
                <p><strong>End Date:</strong> {{ $project->end_date ? $project->end_date->format('M d, Y') : 'N/A' }}</p>
                <p><strong>Created By:</strong> {{ $project->creator->name }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Assigned Clients</h5></div>
            <div class="card-body">
                @forelse($project->clients as $client)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ $client->company_name }}</span>
                        <form method="POST" action="{{ route('admin.projects.remove-client', [$project, $client]) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove client?')">Remove</button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted">No clients assigned</p>
                @endforelse

                @if($availableClients->count() > 0)
                    <hr>
                    <form method="POST" action="{{ route('admin.projects.assign-client', $project) }}">
                        @csrf
                        <div class="input-group">
                            <select name="client_id" class="form-control" required>
                                <option value="">Select Client</option>
                                @foreach($availableClients as $client)
                                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">Assign</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <h3>PBC Requests</h3>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Title</th><th>Client</th><th>Status</th><th>Due Date</th><th>Action</th></tr>
        </thead>
        <tbody>
            @forelse($project->pbcRequests as $request)
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
</div>
@endsection
