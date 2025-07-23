@extends('layouts.app')
@section('title', 'PBC Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>PBC Templates</h1>
    <a href="{{ route('admin.pbc-templates.create') }}" class="btn btn-warning">Create Template</a>
</div>

<!-- Search Form -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search templates..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </div>
</form>

<!-- Templates Table -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Items Count</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($templates as $template)
        <tr>
            <td>{{ $template->id }}</td>
            <td>{{ $template->name }}</td>
            <td>{{ Str::limit($template->description, 50) }}</td>
            <td>{{ $template->templateItems()->count() }} items</td>
            <td>
                <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td>{{ $template->created_at->format('M d, Y') }}</td>
            <td>
                <a href="{{ route('admin.pbc-templates.show', $template) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View</a>
                <a href="{{ route('admin.pbc-templates.edit', $template) }}" class="btn btn-sm" style="background-color: #ffc107; color: black;">Edit</a>
                <form method="POST" action="{{ route('admin.pbc-templates.destroy', $template) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete template?')">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center">No templates found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $templates->links() }}
@endsection
