@extends('layouts.app')
@section('title', 'Create User')

@section('content')
<h1>Create New User</h1>

<form method="POST" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role *</label>
                <select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required>
                    <option value="">Select Role</option>
                    <option value="system_admin" {{ old('role') == 'system_admin' ? 'selected' : '' }}>
                        System Admin
                    </option>
                    <option value="engagement_partner" {{ old('role') == 'engagement_partner' ? 'selected' : '' }}>
                        Engagement Partner
                    </option>
                    <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>
                        Manager
                    </option>
                    <option value="associate" {{ old('role') == 'associate' ? 'selected' : '' }}>
                        Associate
                    </option>
                    <option value="client" {{ old('role') == 'client' ? 'selected' : '' }}>
                        Client
                    </option>
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="mt-2">
                    <small class="text-muted">
                        <strong>System Admin:</strong> Full system access<br>
                        <strong>Engagement Partner:</strong> High-level project access<br>
                        <strong>Manager:</strong> Medium-level project access<br>
                        <strong>Associate:</strong> Limited project access<br>
                        <strong>Client:</strong> Document upload only
                    </small>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password *</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">Minimum 8 characters</small>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm Password *</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
            </div>

            <div class="alert alert-info">
                <small>
                    <strong>Note:</strong> If you create a Client user, you'll be redirected to complete their company information.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h6>Role Permissions</h6></div>
                <div class="card-body">
                    <div id="role-description">
                        <p class="text-muted">Select a role to see permissions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Dynamic role description
document.getElementById('role').addEventListener('change', function() {
    const role = this.value;
    const descriptionDiv = document.getElementById('role-description');

    const descriptions = {
        'system_admin': `
            <h6 class="text-danger">System Administrator</h6>
            <ul class="mb-0">
                <li>Full access to all system features</li>
                <li>Can create and manage all users</li>
                <li>Can create and assign projects</li>
                <li>Can create PBC requests</li>
                <li>Can review and approve documents</li>
                <li>Can access all client data</li>
            </ul>
        `,
        'engagement_partner': `
            <h6 class="text-primary">Engagement Partner</h6>
            <ul class="mb-0">
                <li>Can create and manage projects</li>
                <li>Can create PBC requests</li>
                <li>Can review and approve documents</li>
                <li>Access limited to assigned projects</li>
                <li>Cannot create system users</li>
            </ul>
        `,
        'manager': `
            <h6 class="text-info">Manager</h6>
            <ul class="mb-0">
                <li>Can create and manage projects</li>
                <li>Can create PBC requests</li>
                <li>Can review and approve documents</li>
                <li>Access limited to assigned projects</li>
                <li>Cannot create users</li>
            </ul>
        `,
        'associate': `
            <h6 class="text-warning">Associate</h6>
            <ul class="mb-0">
                <li>Can create PBC requests</li>
                <li>Can review and approve documents</li>
                <li>Access limited to assigned projects</li>
                <li>Cannot create projects or users</li>
            </ul>
        `,
        'client': `
            <h6 class="text-success">Client</h6>
            <ul class="mb-0">
                <li>Can upload documents to PBC requests</li>
                <li>Can view own company's projects</li>
                <li>Can track document status</li>
                <li>Cannot create projects or users</li>
                <li>Read-only access to most features</li>
            </ul>
        `
    };

    if (role && descriptions[role]) {
        descriptionDiv.innerHTML = descriptions[role];
    } else {
        descriptionDiv.innerHTML = '<p class="text-muted">Select a role to see permissions.</p>';
    }
});
</script>
@endsection
