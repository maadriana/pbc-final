@extends('layouts.app')
@section('title', 'Edit Client')

@section('content')
<h1>Edit Client</h1>

<form method="POST" action="{{ route('admin.clients.update', $client) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-6">
            <h4>User Account Details</h4>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $client->user->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $client->user->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
        </div>

        <div class="col-md-6">
            <h4>Company Details</h4>
            <div class="mb-3">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                       value="{{ old('company_name', $client->company_name) }}" required>
                @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control"
                       value="{{ old('contact_person', $client->contact_person) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="{{ old('phone', $client->phone) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $client->address) }}</textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Update Client</button>
    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
