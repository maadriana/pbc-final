@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<h1>Edit User</h1>

<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required>
                    <option value="system_admin" {{ old('role', $user->role) == 'system_admin' ? 'selected' : '' }}>System Admin</option>
                    <option value="engagement_partner" {{ old('role', $user->role) == 'engagement_partner' ? 'selected' : '' }}>Engagement Partner</option>
                    <option value="manager" {{ old('role', $user->role) == 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="associate" {{ old('role', $user->role) == 'associate' ? 'selected' : '' }}>Associate</option>
                    <option value="client" {{ old('role', $user->role) == 'client' ? 'selected' : '' }}>Client</option>
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">
                    Current role: <strong>{{ $user->getRoleDisplayName() }}</strong>
                </small>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
