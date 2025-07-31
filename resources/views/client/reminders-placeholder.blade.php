@extends('layouts.app')
@section('title', 'Reminders')

@section('content')
<h1>Reminders</h1>

<div class="alert alert-info">
    <h5><i class="fas fa-bell"></i> Reminder System</h5>
    <p>Your admin team can send you reminders about PBC requests. When they send reminders, they will appear here.</p>

    <p><strong>How it works:</strong></p>
    <ul>
        <li>Admin sends reminder from PBC request page</li>
        <li>You receive notification here on your reminders page</li>
        <li>Click to view the request and upload documents</li>
        <li>Mark reminders as read when completed</li>
    </ul>
</div>

<div class="text-center py-5">
    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
    <h5>No reminders at the moment</h5>
    <p class="text-muted">Check back later for reminders from your admin team.</p>
    <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-primary">
        View PBC Requests
    </a>
</div>
@endsection
