@extends('layouts.app')
@section('title', 'My PBC Requests')

@section('content')
<h1>My PBC Requests</h1>

<table class="table">
    <thead>
        <tr><th>ID</th><th>Title</th><th>Project</th><th>Status</th><th>Due Date</th><th>Progress</th><th>Action</th></tr>
    </thead>
    <tbody>
        @forelse($requests as $request)
        <tr>
            <td>{{ $request->id }}</td>
            <td>{{ $request->title }}</td>
            <td>{{ $request->project->name }}</td>
            <td><span class="badge bg-secondary">{{ ucfirst($request->status) }}</span></td>
            <td>{{ $request->due_date ? $request->due_date->format('M d, Y') : 'N/A' }}</td>
            <td>{{ $request->getProgressPercentage() }}%</td>
            <td><a href="{{ route('client.pbc-requests.show', $request) }}" class="btn btn-sm btn-primary">View & Upload</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center">No requests assigned</td></tr>
        @endforelse
    </tbody>
</table>

{{ $requests->links() }}
@endsection
