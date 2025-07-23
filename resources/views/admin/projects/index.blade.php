@extends('layouts.app')
@section('title', 'Projects')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Projects Management</h1>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">Create Project</a>
</div>

<!-- Search Form -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search projects..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </div>
</form>

<!-- Projects Table -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Status</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($projects as $project)
        <tr>
            <td>{{ $project->id }}</td>
            <td>{{ $project->name }}</td>
            <td>{{ Str::limit($project->description, 50) }}</td>
            <td>
                <span class="badge bg-{{ $project->status == 'active' ? 'success' : ($project->status == 'completed' ? 'primary' : 'warning') }}">
                    {{ ucfirst($project->status) }}
                </span>
            </td>
            <td>{{ $project->start_date ? $project->start_date->format('M d, Y') : 'N/A' }}</td>
            <td>{{ $project->end_date ? $project->end_date->format('M d, Y') : 'N/A' }}</td>
            <td>
                <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View</a>
                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm" style="background-color: #ffc107; color: black;">Edit</a>
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete project?')">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center">No projects found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $projects->links() }}
@endsection
