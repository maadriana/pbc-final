@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Users Management</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Create User</a>
</div>

<!-- Simple Search -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="role" class="form-control">
                <option value="">All Roles</option>
                <option value="system_admin" {{ request('role') == 'system_admin' ? 'selected' : '' }}>Admin</option>
                <option value="client" {{ request('role') == 'client' ? 'selected' : '' }}>Client</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </div>
</form>

<!-- Users Table -->
<table class="table">
    <thead>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @forelse($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td><span class="badge bg-{{ $user->role == 'system_admin' ? 'danger' : 'primary' }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span></td>
            <td>{{ $user->created_at->format('M d, Y') }}</td>
            <td>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-info">View</a>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning">Edit</a>
                @if($user->id !== auth()->id())
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center">No users found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $users->links() }}
@endsection
