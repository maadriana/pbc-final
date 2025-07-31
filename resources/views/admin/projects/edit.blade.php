@extends('layouts.app')
@section('title', 'Edit Project')

@section('content')
<h1>Edit Project: {{ $project->name }}</h1>

<form method="POST" action="{{ route('admin.projects.update', $project) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Left Column - Project Details -->
        <div class="col-md-6">
            <h4 class="mb-3">Project Information</h4>

            <div class="mb-3">
                <label class="form-label">Project Name *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $project->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                          rows="3">{{ old('description', $project->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Client *</label>
                <select name="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $project->client_id) == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }} ({{ $client->user->email }})
                        </option>
                    @endforeach
                </select>
                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Engagement Type *</label>
                <select name="engagement_type" class="form-control @error('engagement_type') is-invalid @enderror" required>
                    <option value="">Select Engagement Type</option>
                    <option value="audit" {{ old('engagement_type', $project->engagement_type) == 'audit' ? 'selected' : '' }}>Audit</option>
                    <option value="accounting" {{ old('engagement_type', $project->engagement_type) == 'accounting' ? 'selected' : '' }}>Accounting</option>
                    <option value="tax" {{ old('engagement_type', $project->engagement_type) == 'tax' ? 'selected' : '' }}>Tax</option>
                    <option value="special_engagement" {{ old('engagement_type', $project->engagement_type) == 'special_engagement' ? 'selected' : '' }}>Special Engagement</option>
                    <option value="others" {{ old('engagement_type', $project->engagement_type) == 'others' ? 'selected' : '' }}>Others</option>
                </select>
                @error('engagement_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Project Start Date</label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date', $project->start_date ? $project->start_date->format('Y-m-d') : '') }}">
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Project End Date</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date', $project->end_date ? $project->end_date->format('Y-m-d') : '') }}">
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Engagement Period Start</label>
                        <input type="date" name="engagement_period_start" class="form-control @error('engagement_period_start') is-invalid @enderror"
                               value="{{ old('engagement_period_start', $project->engagement_period_start ? $project->engagement_period_start->format('Y-m-d') : '') }}">
                        @error('engagement_period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Engagement Period End</label>
                        <input type="date" name="engagement_period_end" class="form-control @error('engagement_period_end') is-invalid @enderror"
                               value="{{ old('engagement_period_end', $project->engagement_period_end ? $project->engagement_period_end->format('Y-m-d') : '') }}">
                        @error('engagement_period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status', $project->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="on_hold" {{ old('status', $project->status) == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('status', $project->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <!-- Right Column - Team Assignment -->
        <div class="col-md-6">
            <h4 class="mb-3">Team Assignment</h4>

            @if(isset($staffByRole) && $staffByRole)
                @php
                    $currentAssignments = $project->assignments->keyBy('role');
                @endphp

                <div class="mb-3">
                    <label class="form-label">Engagement Partner</label>
                    <select name="engagement_partner" class="form-control">
                        <option value="">Select Engagement Partner</option>
                        @foreach($staffByRole['engagement_partner'] ?? [] as $user)
                            <option value="{{ $user->id }}" {{
                                old('engagement_partner', $currentAssignments->get('engagement_partner')?->user_id) == $user->id ? 'selected' : ''
                            }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Manager</label>
                    <select name="manager" class="form-control">
                        <option value="">Select Manager</option>
                        @foreach($staffByRole['manager'] ?? [] as $user)
                            <option value="{{ $user->id }}" {{
                                old('manager', $currentAssignments->get('manager')?->user_id) == $user->id ? 'selected' : ''
                            }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Associate 1</label>
                    <select name="associate_1" class="form-control">
                        <option value="">Select Associate 1</option>
                        @foreach($staffByRole['associate'] ?? [] as $user)
                            <option value="{{ $user->id }}" {{
                                old('associate_1', $currentAssignments->get('associate_1')?->user_id) == $user->id ? 'selected' : ''
                            }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Associate 2</label>
                    <select name="associate_2" class="form-control">
                        <option value="">Select Associate 2</option>
                        @foreach($staffByRole['associate'] ?? [] as $user)
                            <option value="{{ $user->id }}" {{
                                old('associate_2', $currentAssignments->get('associate_2')?->user_id) == $user->id ? 'selected' : ''
                            }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="alert alert-info">
                    <p>No staff members available for assignment.</p>
                </div>
            @endif

            <div class="alert alert-warning">
                <small><strong>Note:</strong> Changing team assignments will affect access to this project's PBC requests.</small>
            </div>

            @if($project->assignments->count() > 0)
                <div class="card mt-3">
                    <div class="card-header"><small>Current Team</small></div>
                    <div class="card-body">
                        @foreach($project->assignments as $assignment)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><strong>{{ $assignment->role_display_name }}:</strong> {{ $assignment->user->name }}</span>
                                <small class="text-muted">{{ $assignment->created_at->format('M d, Y') }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <hr>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Update Project</button>
        <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
