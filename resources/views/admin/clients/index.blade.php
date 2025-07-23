@extends('layouts.app')
@section('title', 'Clients')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Clients Management</h1>
    <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Create Client</a>
</div>

<table class="table">
    <thead>
        <tr><th>ID</th><th>Company</th><th>Contact Person</th><th>Email</th><th>Phone</th><th>Actions</th></tr>
    </thead>
    <tbody>
        @forelse($clients as $client)
        <tr>
            <td>{{ $client->id }}</td>
            <td>{{ $client->company_name }}</td>
            <td>{{ $client->contact_person }}</td>
            <td>{{ $client->user->email }}</td>
            <td>{{ $client->phone }}</td>
            <td>
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-info">View</a>
                <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-warning">Edit</a>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center">No clients found</td></tr>
        @endforelse
    </tbody>
</table>

{{ $clients->links() }}
@endsection
