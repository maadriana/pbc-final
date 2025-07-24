@extends('layouts.app')
@section('title', 'User Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>User Details</h1>
    <div>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">Edit User</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>User Information</h5></div>
            <div class="card-body">
                <p><strong>ID:</strong> {{ $user->id }}</p>
                <p><strong>Name:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Role:</strong>
                    <span class="badge bg-{{ $user->role == 'system_admin' ? 'danger' : 'primary' }}">
                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                    </span>
                </p>
                <p><strong>Created:</strong> {{ $user->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Last Updated:</strong> {{ $user->updated_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Activity Summary</h5></div>
            <div class="card-body">
                @if($user->isClient() && $user->client)
                    <p><strong>Company:</strong> {{ $user->client->company_name }}</p>
                    <p><strong>Contact Person:</strong> {{ $user->client->contact_person ?? 'N/A' }}</p>
                    <p><strong>Phone:</strong> {{ $user->client->phone ?? 'N/A' }}</p>
                    <p><strong>PBC Requests:</strong> {{ $user->client->pbcRequests->count() }}</p>
                @elseif($user->isAdmin())
                    <p><strong>Clients Created:</strong> {{ $user->createdClients->count() }}</p>
                    <p><strong>Projects Created:</strong> {{ $user->createdProjects->count() }}</p>
                @endif
                <p><strong>Documents Uploaded:</strong> {{ $user->uploadedDocuments->count() }}</p>
            </div>
        </div>
    </div>
</div>

@if($user->isClient() && $user->client && $user->client->pbcRequests->count() > 0)
<div class="mt-4">
    <h3>Recent PBC Requests</h3>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Title</th><th>Project</th><th>Status</th><th>Due Date</th><th>Action</th></tr>
        </thead>
        <tbody>
            @foreach($user->client->pbcRequests->take(10) as $request)
            <tr>
                <td>{{ $request->id }}</td>
                <td>{{ $request->title }}</td>
                <td>{{ $request->project->name }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($request->status) }}</span></td>
                <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
                <td><a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">View</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
