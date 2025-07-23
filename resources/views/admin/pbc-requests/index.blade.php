@extends('layouts.app')
@section('title', 'PBC Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>PBC Requests</h1>
    <a href="{{ route('admin.pbc-requests.create') }}" class="btn btn-primary">Create Request</a>
</div>

<!-- Search Form -->
<form method="GET" class="mb-3">
    <div class="row">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search requests..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="client_id" class="form-control">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->company_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </div>
</form>

<!-- Requests Table -->
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
            <th>Actions</th>
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
            <td>{{ $request->getProgressPercentage() }}%</td>
            <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
            <td>
                <a href="{{ route('admin.pbc-requests.show', $request) }}" class="btn btn-sm" style="background-color: #17a2b8; color: white;">View</a>
                @if(!$request->sent_at)
                    <a href="{{ route('admin.pbc-requests.edit', $request) }}" class="btn btn-sm" style="background-color: #ffc107; color: black;">Edit</a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center">No requests found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $requests->links() }}
@endsection
